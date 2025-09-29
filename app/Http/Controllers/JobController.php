<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobView;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::with('account.profile', 'jobCategory')
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        if ($request->ajax()) {
            return view('jobs.partials.jobs-list', compact('jobs'))->render();
        }

        return view('jobs.index', compact('jobs'));
    }

    public function show(Job $job, Request $request)
    {
        $job->load('jobDetails');

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
            ->latest()
            ->take(5)
            ->get();

        // Nếu chưa đủ 5 thì lấy thêm theo account_id
        if ($relatedJobs->count() < 5) {
            $moreJobs = Job::with('account.profile')
                ->where('job_id', '!=', $job->job_id)
                ->where('account_id', $job->account_id)
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
                'message' => 'Bạn cần đăng nhập để ứng tuyển.',
                'login_required' => true
            ]);
        }

        $userId = auth()->id();
        $job = Job::findOrFail($jobId);

        // Lấy danh sách apply_id hiện tại
        $applyIds = $job->apply_id ? explode(',', $job->apply_id) : [];

        // Kiểm tra nếu user đã ứng tuyển
        if (in_array($userId, $applyIds)) {
            return response()->json([
                'message' => 'Bạn đã ứng tuyển công việc này.',
                'login_required' => false
            ]);
        }

        // Thêm user_id và lưu
        $applyIds[] = $userId;
        $job->apply_id = implode(',', $applyIds);
        $job->save();

        return response()->json([
            'message' => 'Ứng tuyển thành công!',
            'login_required' => false
        ]);
    }


}
