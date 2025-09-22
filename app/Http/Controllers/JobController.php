<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class JobController extends Controller
{
public function index(Request $request)
{
    $jobs = Job::with('account','jobCategory')
        ->orderBy('created_at','desc')
        ->paginate(6);

    if ($request->ajax()) {
        // Đây phải là partial chứa #jobs-list và #pagination-wrapper
        return view('jobs.partials.jobs-list', compact('jobs'))->render();
    }

    return view('jobs.index', compact('jobs'));
}

    public function show(Job $job)
    {
        $job->load('jobDetails');

        return view('jobs.show', compact('job'));
    }

    public function apply($jobId)
{
    if(!auth()->check()) {
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
    if(in_array($userId, $applyIds)) {
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
