<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use App\Models\JobApply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Skill;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Events\CommentNotificationBroadcasted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MyJobsController extends Controller
{
    public function index(Request $r)
    {
        $ownerId = $r->user()->account_id;

        $jobs = Job::where('account_id', $ownerId)
            ->withCount('applicants')
            ->with([
                'categoryRef',
                'applicants' => fn($q) =>
                    $q->select('accounts.account_id', 'accounts.name', 'accounts.email', 'accounts.avatar_url')
                        ->orderBy('job_apply.created_at', 'desc'),
                'applicants.profile:profile_id,account_id,fullname,username,skill,description',
            ])
            ->latest('job_id')
            ->paginate(10);

        $skillMap = Skill::pluck('name', 'skill_id');

        $jobIds = $jobs->pluck('job_id')->all();
        $taskRows = DB::table('tasks')
            ->whereIn('job_id', $jobIds)
            ->select('id', 'task_id', 'job_id', 'title', 'description', 'status', 'start_date', 'due_date', 'assigned_to', 'file_url', 'created_at', 'updated_at')
            ->orderBy('task_id')->orderBy('id')
            ->get();

        $tasksByJobAndUser = [];
        foreach ($taskRows as $t)
            $tasksByJobAndUser[$t->job_id][$t->assigned_to][] = $t;

        return view('client.jobs.mine', compact('jobs', 'skillMap', 'tasksByJobAndUser'));
    }

    public function update(Request $request, int $job_id, int $user_id)
    {
        $ownerId = $request->user()->account_id;
        $job = Job::whereKey($job_id)->firstOrFail();
        abort_unless($job->account_id === $ownerId, 403);

        $status = (int) $request->input('status', 2); // 2 = chấp nhận

        DB::transaction(function () use ($job, $user_id, $status) {
            JobApply::where('job_id', $job->job_id)
                ->where('user_id', $user_id)
                ->update(['status' => $status, 'updated_at' => now()]);

            if ($status === 2 && $job->status !== 'in_progress') {
                $job->status = 'in_progress';
                $job->save();
            }

            $acceptedIds = JobApply::where('job_id', $job->job_id)
                ->where('status', 2)
                ->pluck('user_id')->unique()->values()->all();

            $job->apply_id = $acceptedIds ? implode(',', $acceptedIds) : null;
            $job->save();

            if ($status === 2) {
                $exists = DB::table('box_chat')->where('job_id', $job->job_id)->exists();
                if (!$exists) {
                    DB::table('box_chat')->insert([
                        'name' => 'Nhóm ' . Str::limit($job->title, 230),
                        'type' => 2,
                        'receiver_id' => null,
                        'sender_id' => null,
                        'job_id' => $job->job_id,
                        'org_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $notification = app(NotificationService::class)->create(
            userId: $user_id,
            type: Notification::TYPE_NOTIFICATION,
            title: 'Có người vừa duyệt đơn ứng tuyển của bạn',
            body: "Bạn vừa được duyệt đơn vào công việc: \"{$job->title}\"",
            meta: ['job_id' => $job->job_id, 'applicant_id' => $ownerId],
            actorId: $ownerId,
            severity: 'medium'
        );

        try {
            broadcast(new CommentNotificationBroadcasted($notification, $user_id))->toOthers();
            Cache::forget("header_json_{$user_id}");
        } catch (\Exception $e) {
            Log::error('Broadcast ứng tuyển thất bại', ['error' => $e->getMessage()]);
        }

        // === Trả JSON nếu là AJAX ===
        if ($request->ajax()) {
            // trả thêm số đã nhận / chỉ tiêu để FE cập nhật tức thì
            $acceptedCount = JobApply::where('job_id', $job->job_id)->where('status', 2)->count();
            return response()->json([
                'message' => 'Đã xác nhận ứng viên.',
                'data' => [
                    'job_id' => $job->job_id,
                    'accepted_count' => $acceptedCount,
                    'quantity' => (int) ($job->quantity ?? 1),
                ],
            ]);
        }

        return back()->with('success', 'Đã xác nhận Ứng viên.');
    }

    public function bulkUpdate(Request $request, int $job_id)
    {
        $ownerId = $request->user()->account_id;
        $job = Job::whereKey($job_id)->firstOrFail();
        abort_unless((int) $job->account_id === (int) $ownerId, 403);

        $action = $request->string('action')->toString();    // accept|reject
        $userIds = collect($request->input('user_ids', []))
            ->map(fn($v) => (int) $v)->filter()->unique()->all();
        if (!$userIds || !in_array($action, ['accept', 'reject'], true)) {
            return $request->ajax()
                ? response()->json(['message' => 'Dữ liệu không hợp lệ.'], 422)
                : back()->withErrors('Dữ liệu không hợp lệ.');
        }

        $status = $action === 'accept' ? 2 : 0;
        $result = $this->handleApplicants($job, $userIds, $status, $ownerId);

        if ($status === 2 && !empty($result['accepted'])) {
            foreach ($result['accepted'] as $rid) {
                $notification = app(NotificationService::class)->create(
                    userId: $rid,
                    type: Notification::TYPE_NOTIFICATION,
                    title: 'Đơn ứng tuyển của bạn đã được duyệt',
                    body: "Bạn vừa được duyệt vào công việc: \"{$job->title}\"",
                    meta: ['job_id' => $job->job_id, 'applicant_id' => $ownerId],
                    actorId: $ownerId,
                    severity: 'medium'
                );
                try {
                    broadcast(new CommentNotificationBroadcasted($notification, $rid))->toOthers();
                    Cache::forget("header_json_{$rid}");
                } catch (\Exception $e) {
                    Log::error('Broadcast duyệt đơn (bulk) thất bại', ['rid' => $rid, 'error' => $e->getMessage()]);
                }
            }
        }

        $acceptedCount = JobApply::where('job_id', $job->job_id)->where('status', 2)->count();
        $msg = $status === 2
            ? ('Đã chấp nhận ' . count($result['accepted']) . ' ứng viên.')
            : ('Đã từ chối ' . count($result['updated']) . ' ứng viên.');

        // === Trả JSON nếu là AJAX ===
        if ($request->ajax()) {
            return response()->json([
                'message' => $msg,
                'data' => [
                    'job_id' => $job->job_id,
                    'accepted_count' => $acceptedCount,
                    'accepted_ids' => $result['accepted'],
                    'updated_ids' => $result['updated'],
                    'quantity' => (int) ($job->quantity ?? 1),
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    private function handleApplicants(Job $job, array $userIds, int $status, int $ownerId): array
    {
        $acceptedIds = [];
        $updatedIds = [];
        $resetPendingIds = [];

        DB::transaction(function () use ($job, $userIds, $status, &$acceptedIds, &$updatedIds, &$resetPendingIds) {

            // chỉ những row thuộc job và nằm trong danh sách chọn
            $candidates = DB::table('job_apply')
                ->where('job_id', $job->job_id)
                ->whereIn('user_id', $userIds)
                ->get(['user_id', 'status']);

            if ($candidates->isEmpty())
                return;

            if ($status === 2) {
                $currentAccepted = JobApply::where('job_id', $job->job_id)->where('status', 2)->count();
                $remain = max(0, (int) $job->quantity - $currentAccepted);

                // các id đang chờ duyệt trong danh sách gửi lên
                $candidates = DB::table('job_apply')
                    ->where('job_id', $job->job_id)
                    ->whereIn('user_id', $userIds)
                    ->get(['user_id', 'status']);

                $pendingInSelected = $candidates->where('status', 1)->pluck('user_id')->map(fn($v) => (int) $v)->values()->all();

                if ($remain <= 0) {
                    // Hết slot: tất cả người đang chờ duyệt của JOB về 0
                    DB::table('job_apply')
                        ->where('job_id', $job->job_id)
                        ->where('status', 1)
                        ->update(['status' => 0, 'updated_at' => now()]);
                    $acceptedIds = [];
                } else {
                    // Pick tối đa theo slot trong nhóm được chọn
                    $pick = array_slice($pendingInSelected, 0, $remain);

                    if (!empty($pick)) {
                        DB::table('job_apply')
                            ->where('job_id', $job->job_id)
                            ->whereIn('user_id', $pick)
                            ->update(['status' => 2, 'updated_at' => now()]);
                        $acceptedIds = $pick;
                        $updatedIds = $pick;

                        if ($job->status !== 'in_progress') {
                            $job->status = 'in_progress';
                            $job->save();
                        }
                    }

                    // ✅ PHẦN QUAN TRỌNG: toàn bộ những người còn lại của job (đang 1) về 0
                    DB::table('job_apply')
                        ->where('job_id', $job->job_id)
                        ->where('status', 1)
                        ->when(!empty($pick), fn($q) => $q->whereNotIn('user_id', $pick))
                        ->update(['status' => 0, 'updated_at' => now()]);
                }

                // cập nhật apply_id
                $newAccepted = JobApply::where('job_id', $job->job_id)
                    ->where('status', 2)
                    ->pluck('user_id')->unique()->values()->all();
                $job->apply_id = $newAccepted ? implode(',', $newAccepted) : null;
                $job->save();

                // tạo box chat nếu có người được accept
                if (!empty($acceptedIds)) {
                    $exists = DB::table('box_chat')->where('job_id', $job->job_id)->exists();
                    if (!$exists) {
                        DB::table('box_chat')->insert([
                            'name' => 'Nhóm ' . Str::limit($job->title, 230),
                            'type' => 2,
                            'receiver_id' => null,
                            'sender_id' => null,
                            'job_id' => $job->job_id,
                            'org_id' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        return [
            'accepted' => $acceptedIds,
            'updated' => $updatedIds,
            'reset_pending' => $resetPendingIds, // (trả về để FE tự cập nhật nhanh nếu muốn)
        ];
    }


}
