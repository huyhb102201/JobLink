<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobView;
use App\Models\JobApply;
use App\Models\Comment;
use App\Models\JobReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Events\CommentNotificationBroadcasted;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::with('account.profile', 'jobCategory')
            ->whereNotIn('status', ['pending', 'cancelled']);

        // Lọc theo payment_type
        if ($request->has('payment_type') && is_array($request->payment_type)) {
            $jobs->whereIn('payment_type', $request->payment_type);
        }

        // Lọc theo status
        if ($request->has('status') && is_array($request->status)) {
            $jobs->whereIn('status', $request->status);
        }

        // Lọc theo category
        if ($request->has('category') && is_array($request->category)) {
            $jobs->whereIn('category_id', $request->category);
        }

        $jobs = $jobs->orderBy('created_at', 'desc')->paginate(6);

        if ($request->ajax()) {
            return response()->json([
                'jobs' => view('jobs.partials.jobs-list', compact('jobs'))->render(),
                'pagination' => view('components.pagination', [
                    'paginator' => $jobs,
                    'elements' => $jobs->links()->elements ?? []
                ])->render(),
            ]);
        }

        return view('jobs.index', compact('jobs'));
    }

    public function show(Job $job, Request $request)
    {
        if (in_array($job->status, ['pending', 'cancelled'])) {
            abort(404);
        }

        $job->load('jobDetails', 'comments.account');

        $accountId = auth()->check() ? auth()->id() : null;
        $ip = auth()->check() ? null : $request->ip();

        $conditions = [
            'job_id' => $job->job_id,
            'account_id' => $accountId,
            'ip_address' => $ip,
            'action' => 'view',
        ];

        $jobView = JobView::where($conditions)->first();

        if ($jobView) {
            $jobView->increment('view');
        } else {
            JobView::create(array_merge($conditions, ['view' => 1]));
        }

        // --- Lấy bài viết liên quan ---
        $relatedJobs = Job::with('account.profile')
            ->where('job_id', '!=', $job->job_id)
            ->where('category_id', $job->category_id)
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->latest()
            ->take(5)
            ->get();

        // Nếu chưa đủ 5 thì lấy thêm theo account_id
        if ($relatedJobs->count() < 5) {
            $moreJobs = Job::with('account.profile')
                ->where('job_id', '!=', $job->job_id)
                ->where('account_id', $job->account_id)
                ->whereNotIn('status', ['pending', 'cancelled'])
                ->latest()
                ->take(5 - $relatedJobs->count())
                ->get();

            $relatedJobs = $relatedJobs->concat($moreJobs);
        }


        return view('jobs.show', compact('job', 'relatedJobs'));
    }


    public function apply($jobId)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'login_required' => true,
                'message' => 'Bạn cần đăng nhập để ứng tuyển.'
            ]);
        }

        $userId = auth()->id();
        $job = Job::findOrFail($jobId);

        // Kiểm tra nếu user đã apply
        $exists = JobApply::where('job_id', $jobId)->where('user_id', $userId)->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'login_required' => false,
                'message' => 'Bạn đã ứng tuyển công việc này trước đó.'
            ]);
        }

        // Thêm vào bảng job_apply
        JobApply::create([
            'job_id' => $jobId,
            'user_id' => $userId,
            'status' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ứng tuyển thành công!',
            'statusLabel' => 'Chờ duyệt'
        ]);
    }


    public function store(Request $request, Job $job)
    {
        try {
            $validated = $request->validate([
                'content' => ['required', 'string', 'max:5000'],
                'parent_id' => ['nullable', 'exists:comments,id'],
            ]);

            // Tạo comment
            $comment = Comment::create([
                'account_id' => Auth::id(),
                'job_id' => $job->job_id,
                'content' => $validated['content'],
                'parent_id' => $validated['parent_id'] ?? null,
            ]);

            $comment->load('account'); // để lấy avatar, name

            // Xác định người nhận notification
            if ($validated['parent_id']) {
                $parentComment = Comment::find($validated['parent_id']);
                $receiverId = $parentComment ? $parentComment->account_id : null;
            } else {
                $receiverId = $job->account_id;
            }

            $user = Auth::user();

            if ($receiverId && $receiverId !== Auth::id()) {
                // Tạo notification
                $notification = app(NotificationService::class)->create(
                    userId: $receiverId,
                    type: Notification::TYPE_COMMENT,
                    title: $validated['parent_id'] ? 'Trả lời bình luận của bạn' : 'Bình luận mới trên bài đăng của bạn',
                    body: "{$user->name} vừa bình luận: \"{$validated['content']}\"",
                    meta: [
                        'job_id' => $job->job_id,
                        'comment_id' => $comment->id,
                    ],
                    actorId: $user->account_id,
                    severity: 'low'
                );

                // Broadcast realtime
                try {
                    broadcast(new CommentNotificationBroadcasted($notification, $receiverId))->toOthers();
                    Log::info('📡 Broadcast bình luận mới thành công', [
                        'notification_id' => $notification->id,
                        'receiver_id' => $receiverId
                    ]);
                } catch (\Exception $e) {
                    Log::error('❌ Broadcast bình luận thất bại', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'id' => $comment->id,
                'content' => $comment->content,
                'parent_id' => $comment->parent_id,
                'account_id' => $comment->account_id,
                'account_name' => $comment->account->name ?? 'Khách',
                'avatar_url' => $comment->account->avatar_url ?? asset('assets/img/blog/comments-1.jpg'),
                'created_at' => $comment->created_at->format('d M, Y H:i'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Lỗi khi gửi bình luận: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $job = Job::findOrFail($id);

        // Kiểm tra quyền sở hữu nếu cần
        if (auth()->id() !== $job->account_id) {
            abort(403, 'Bạn không có quyền xóa job này.');
        }

        $job->delete();

        return redirect()->route('client.jobs.mine')->with('success', 'Đã xóa công việc thành công.');
    }


    public function report(Request $request, $jobId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để báo cáo.']);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'message' => 'nullable|string',
            'images.*' => 'nullable|image|max:2048',
        ]);

        $paths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $paths[] = $file->store('reports', 'public');
            }
        }

        JobReport::create([
            'job_id' => $jobId,
            'user_id' => auth()->id(),
            'reason' => $request->reason,
            'message' => $request->message,
            'img' => $paths ? implode(',', $paths) : null,
        ]);

        return response()->json(['success' => true]);
    }
}