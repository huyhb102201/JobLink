<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class JobController extends Controller
{
    /**
     * Hiển thị danh sách các công việc đang chờ duyệt.
     */
    public function pendingJobs(Request $request)
    {
        // Load tất cả jobs một lần với đầy đủ thông tin để preload modal
        $jobs = Job::with([
                'client:account_id,email,name',
                'client.profile:account_id,fullname',
                'category:category_id,name'
            ])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // Preload job details cho JavaScript
        $jobDetails = [];
        foreach ($jobs as $job) {
            $jobDetails[$job->job_id] = [
                'job_id' => $job->job_id,
                'title' => $job->title ?? 'Không có tiêu đề',
                'description' => $job->description ?? 'Không có mô tả',
                'budget' => $job->budget ? '$' . number_format($job->budget) : 'Thỏa thuận',
                'payment_type' => $job->payment_type ?? 'Không xác định',
                'deadline' => $job->deadline ? $job->deadline->format('d/m/Y') : 'Không có',
                'status' => ucfirst($job->status ?? 'pending'),
                'created_at' => $job->created_at ? $job->created_at->format('d/m/Y H:i') : 'N/A',
                'client_name' => $job->client->profile->fullname ?? $job->client->name ?? $job->client->email ?? 'Không có thông tin',
                'client_email' => $job->client->email ?? 'Không có email',
                'category_name' => $job->category->name ?? 'Chưa phân loại'
            ];
        }

        // Lấy thống kê với cache để tối ưu hiệu suất
        $statistics = \Cache::remember('admin_job_statistics', 60, function() {
            $inProgress = Job::where('status', 'in_progress')->count();
            $completed = Job::where('status', 'completed')->count();
            
            return [
                'total_jobs' => Job::count(), // Tổng tất cả jobs
                'approved_jobs' => Job::where('status', 'open')->count(), // Đã duyệt
                'rejected_jobs' => Job::where('status', 'cancelled')->count(), // Từ chối
                'pending_jobs' => Job::where('status', 'pending')->count(), // Chờ duyệt
                'other_jobs' => $inProgress + $completed, // Job riêng (đang thực hiện + hoàn thành)
            ];
        });

        return view('admin.jobs.pending', compact('jobs', 'jobDetails', 'statistics'));
    }

    /**
     * Lấy chi tiết công việc để hiển thị inline.
     */
    public function getJobDetails(Job $job)
    {
        try {
            $job->load([
                'client:account_id,email,name',
                'client.profile:account_id,fullname'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading job details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải thông tin công việc: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'job' => [
                'job_id' => $job->job_id,
                'title' => $job->title ?? 'Không có tiêu đề',
                'description' => $job->description ?? 'Không có mô tả',
                'budget' => $job->budget ? '$' . number_format($job->budget) : 'Thỏa thuận',
                'payment_type' => $job->payment_type ?? 'Không xác định',
                'deadline' => $job->deadline ? $job->deadline->format('d/m/Y') : 'Không có',
                'status' => ucfirst($job->status ?? 'pending'),
                'created_at' => $job->created_at->format('d/m/Y H:i'),
                'client_name' => $job->client->profile->fullname ?? $job->client->name ?? $job->client->email ?? 'Không có thông tin',
                'client_email' => $job->client->email ?? 'Không có email',
                'category_name' => 'Chưa phân loại'
            ]
        ]);
    }

    /**
     * Chấp thuận (approve) một công việc.
     */
    public function approve(Job $job)
    {
        if ($job->status !== 'pending') {
            return back()->with('error', 'Công việc này không ở trạng thái chờ duyệt hoặc đã được xử lý.');
        }

        \DB::update("UPDATE jobs SET status = ?, updated_at = NOW() WHERE job_id = ?", ['open', $job->job_id]);

        // Xóa cache thống kê
        \Cache::forget('admin_job_statistics');

        return back()->with('success', 'Đã duyệt công việc thành công!');
    }

    /**
     * Từ chối (reject) một công việc.
     */
    public function reject(Job $job)
    {
        if ($job->status !== 'pending') {
            return back()->with('error', 'Công việc này không ở trạng thái chờ duyệt hoặc đã được xử lý.');
        }

        \DB::update("UPDATE jobs SET status = ?, updated_at = NOW() WHERE job_id = ?", ['cancelled', $job->job_id]);

        // Xóa cache thống kê
        \Cache::forget('admin_job_statistics');

        return back()->with('success', 'Đã từ chối công việc!');
    }

    /**
     * Chấp thuận (approve) nhiều công việc cùng lúc.
     */
    public function batchApprove(Request $request)
    {
        // Lấy danh sách job_id từ request
        $jobIds = $request->input('job_ids');
        
        // Nếu là JSON string, decode nó
        if (is_string($jobIds)) {
            $jobIds = json_decode($jobIds, true);
        }

        if (empty($jobIds) || !is_array($jobIds)) {
            return back()->with('error', 'Không có công việc nào được chọn để duyệt.');
        }

        // Sử dụng raw SQL để tránh lỗi ENUM
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $count = \DB::update(
            "UPDATE jobs SET status = ?, updated_at = NOW() WHERE job_id IN ($placeholders) AND status = ?",
            array_merge(['open'], $jobIds, ['pending'])
        );

        if ($count > 0) {
            // Xóa cache thống kê
            \Cache::forget('admin_job_statistics');
            return back()->with('success', "Đã duyệt thành công $count công việc!");
        } else {
            return back()->with('error', 'Không có công việc nào hợp lệ để duyệt.');
        }
    }

    /**
     * Từ chối nhiều công việc cùng lúc.
     */
    public function batchReject(Request $request)
    {
        $jobIds = $request->input('job_ids');
        
        // Nếu là JSON string, decode nó
        if (is_string($jobIds)) {
            $jobIds = json_decode($jobIds, true);
        }

        if (empty($jobIds) || !is_array($jobIds)) {
            return back()->with('error', 'Không có công việc nào được chọn để từ chối.');
        }

        // Sử dụng raw SQL để tránh lỗi ENUM
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $count = \DB::update(
            "UPDATE jobs SET status = ?, updated_at = NOW() WHERE job_id IN ($placeholders) AND status = ?",
            array_merge(['cancelled'], $jobIds, ['pending'])
        );

        if ($count > 0) {
            // Xóa cache thống kê
            \Cache::forget('admin_job_statistics');
            return back()->with('success', "Đã từ chối $count công việc!");
        } else {
            return back()->with('error', 'Không có công việc nào hợp lệ để từ chối.');
        }
    }
}