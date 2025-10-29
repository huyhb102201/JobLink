<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Services\AdminLogService;
use App\Services\NotificationService;
use App\Models\Notification;

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

        // Preload job details cho JavaScript - sử dụng indexed array
        $jobDetails = [];
        foreach ($jobs as $job) {
            // Tạo status badge
            $statusBadge = '';
            switch ($job->status) {
                case 'pending':
                    $statusBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Chờ duyệt</span>';
                    break;
                case 'open':
                    $statusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Đã duyệt</span>';
                    break;
                case 'cancelled':
                    $statusBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Từ chối</span>';
                    break;
                default:
                    $statusBadge = '<span class="badge bg-secondary">' . ucfirst($job->status) . '</span>';
            }

            $jobDetails[] = [
                'job_id' => $job->job_id,
                'title' => $job->title ?? 'Không có tiêu đề',
                'description' => $job->description ?? 'Không có mô tả',
                'requirements' => $job->requirements ?? null,
                'budget' => $job->budget,
                'payment_type' => $job->payment_type ?? 'Không xác định',
                'deadline' => $job->deadline ? $job->deadline->format('d/m/Y') : null,
                'status' => $job->status ?? 'pending',
                'status_badge' => $statusBadge,
                'created_at' => $job->created_at ? $job->created_at->format('d/m/Y H:i') : 'N/A',
                'client_name' => $job->client->profile->fullname ?? $job->client->name ?? $job->client->email ?? 'Không có thông tin',
                'client_email' => $job->client->email ?? 'Không có email',
                'category_name' => $job->category->name ?? 'Chưa phân loại',
                'skills' => []
            ];
        }

        // Preload history data (jobs đã duyệt/từ chối)
        $historyJobs = Job::with([
            'account:account_id,email,name',
            'account.profile:account_id,fullname'
        ])
            ->whereIn('status', ['open', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $historyData = [];
        foreach ($historyJobs as $job) {
            $statusBadge = $job->status === 'open'
                ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Đã duyệt</span>'
                : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Từ chối</span>';

            $historyData[] = [
                'job_id' => $job->job_id,
                'title' => $job->title,
                'description' => $job->description,
                'budget' => $job->budget,
                'status' => $job->status,
                'status_badge' => $statusBadge,
                'client_name' => $job->account->profile->fullname ?? 'N/A',
                'client_email' => $job->account->email ?? 'N/A',
                'payment_type' => $job->payment_type ?? 'Không xác định',
                'deadline' => $job->deadline ? $job->deadline->format('d/m/Y') : 'Không có',
                'category_name' => 'Chưa phân loại',
                'updated_at' => $job->updated_at->format('d/m/Y H:i'),
                'created_at' => $job->created_at->format('d/m/Y H:i'),
            ];
        }

        // Lấy thống kê real-time (không cache để cập nhật ngay lập tức)
        $statistics = [
            'total_jobs' => Job::whereNull('deleted_at')->whereNotIn('status', ['pending'])->count(), // Tổng bài đăng (trừ pending và soft delete)
            'approved_jobs' => Job::whereNull('deleted_at')->whereNotIn('status', ['pending', 'cancelled'])->count(), // Đã duyệt (bao gồm open, in_progress, completed)
            'rejected_jobs' => Job::whereNull('deleted_at')->where('status', 'cancelled')->count(), // Từ chối
            'pending_jobs' => Job::whereNull('deleted_at')->where('status', 'pending')->count(), // Chờ duyệt
        ];

        return view('admin.jobs.pending', compact('jobs', 'jobDetails', 'historyData', 'statistics'));
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

        // Tạo status badge
        $statusBadge = '';
        switch ($job->status) {
            case 'pending':
                $statusBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Chờ duyệt</span>';
                break;
            case 'open':
                $statusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Đã duyệt</span>';
                break;
            case 'cancelled':
                $statusBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Từ chối</span>';
                break;
            default:
                $statusBadge = '<span class="badge bg-secondary">' . ucfirst($job->status) . '</span>';
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
                'status' => $job->status ?? 'pending', // Raw status
                'status_badge' => $statusBadge, // HTML badge
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
        try {
            $clientId = $job->account_id ?? $job->client_id ?? null;
            if ($clientId && $clientId !== auth()->id()) {
                $notification = app(NotificationService::class)->create(
                    userId: $clientId,
                    type: Notification::TYPE_SYSTEM,
                    title: 'Công việc của bạn đã được duyệt',
                    body: "Bài đăng '{$job->title}' của bạn đã được duyệt và đang hiển thị trên hệ thống.",
                    meta: [
                        'job_id' => $job->job_id,
                    ],
                    actorId: auth()->id(),
                    severity: 'low'
                );

                // Broadcast realtime
                broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $clientId))->toOthers();
                \Cache::forget("header_json_{$clientId}");
            }
        } catch (\Exception $e) {
            \Log::error('Gửi thông báo duyệt công việc thất bại', ['error' => $e->getMessage()]);
        }
        // Log admin action
        AdminLogService::logApprove('Job', $job->job_id, "Duyệt công việc: {$job->title}");

        return back()->with('success', 'Đã duyệt công việc thành công!');
    }

    /**
     * Từ chối (reject) một công việc.
     */
    public function reject(Request $request, Job $job)
    {
        if ($job->status !== 'pending') {
            return back()->with('error', 'Công việc này không ở trạng thái chờ duyệt hoặc đã được xử lý.');
        }

        $rejectReason = $request->input('reject_reason', 'Không đủ tiêu chuẩn');

        \DB::update("UPDATE jobs SET status = ?, updated_at = NOW() WHERE job_id = ?", ['cancelled', $job->job_id]);

        try {
            $clientId = $job->account_id ?? $job->client_id ?? null;
            if ($clientId && $clientId !== auth()->id()) {
                $notification = app(NotificationService::class)->create(
                    userId: $clientId,
                    type: Notification::TYPE_SYSTEM,
                    title: 'Công việc của bạn bị từ chối',
                    body: "Bài đăng '{$job->title}' đã bị từ chối. Lý do: {$rejectReason}",
                    meta: [
                        'job_id' => $job->job_id,
                    ],
                    actorId: auth()->id(),
                    severity: 'low'
                );

                broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $clientId))->toOthers();
                \Cache::forget("header_json_{$clientId}");
            }
        } catch (\Exception $e) {
            \Log::error('Gửi thông báo từ chối công việc thất bại', ['error' => $e->getMessage()]);
        }

        // Log admin action with reason
        AdminLogService::logReject('Job', $job->job_id, "Từ chối công việc: {$job->title}. Lý do: {$rejectReason}");

        return back()->with('success', 'Đã từ chối công việc!');
    }

    /**
     * Chấp thuận (approve) nhiều công việc cùng lúc.
     */
    public function batchApprove(Request $request)
    {
        // Lấy danh sách job_id từ request
        $jobIds = $request->input('job_ids');

        // Nếu là string (comma-separated hoặc JSON), xử lý
        if (is_string($jobIds)) {
            // Thử decode JSON trước
            $decoded = json_decode($jobIds, true);
            if ($decoded !== null) {
                $jobIds = $decoded;
            } else {
                // Nếu không phải JSON, split by comma
                $jobIds = explode(',', $jobIds);
                $jobIds = array_map('intval', array_filter($jobIds));
            }
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
            $jobs = Job::whereIn('job_id', $jobIds)->get();
            foreach ($jobs as $job) {
                try {
                    $clientId = $job->account_id ?? $job->client_id ?? null;
                    if ($clientId && $clientId !== auth()->id()) {
                        $notification = app(NotificationService::class)->create(
                            userId: $clientId,
                            type: Notification::TYPE_SYSTEM,
                            title: 'Công việc của bạn đã được duyệt',
                            body: "Bài đăng '{$job->title}' của bạn đã được duyệt.",
                            meta: ['job_id' => $job->job_id],
                            actorId: auth()->id(),
                            severity: 'low'
                        );
                        broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $clientId))->toOthers();
                    }
                } catch (\Exception $e) {
                    \Log::error('Gửi thông báo duyệt hàng loạt thất bại', ['error' => $e->getMessage()]);
                }
            }
            // Log bulk approve
            AdminLogService::logBulk('approve', 'Job', $jobIds, "Duyệt hàng loạt $count công việc");

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
        $rejectReason = $request->input('reject_reason', 'Không đủ tiêu chuẩn');

        // Nếu là string (comma-separated hoặc JSON), xử lý
        if (is_string($jobIds)) {
            // Thử decode JSON trước
            $decoded = json_decode($jobIds, true);
            if ($decoded !== null) {
                $jobIds = $decoded;
            } else {
                // Nếu không phải JSON, split by comma
                $jobIds = explode(',', $jobIds);
                $jobIds = array_map('intval', array_filter($jobIds));
            }
        }

        if (empty($jobIds) || !is_array($jobIds)) {
            return back()->with('error', 'Không có công việc nào được chọn để từ chối.');
        }

        // Validate reject reason
        if (empty(trim($rejectReason))) {
            return back()->with('error', 'Vui lòng nhập lý do từ chối.');
        }

        // Sử dụng raw SQL để tránh lỗi ENUM
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $count = \DB::update(
            "UPDATE jobs SET status = ?, updated_at = NOW() WHERE job_id IN ($placeholders) AND status = ?",
            array_merge(['cancelled'], $jobIds, ['pending'])
        );

        if ($count > 0) {
            $jobs = Job::whereIn('job_id', $jobIds)->get();
            foreach ($jobs as $job) {
                try {
                    $clientId = $job->account_id ?? $job->client_id ?? null;
                    if ($clientId && $clientId !== auth()->id()) {
                        $notification = app(NotificationService::class)->create(
                            userId: $clientId,
                            type: Notification::TYPE_SYSTEM,
                            title: 'Công việc của bạn bị từ chối',
                            body: "Bài đăng '{$job->title}' đã bị từ chối. Lý do: {$rejectReason}",
                            meta: ['job_id' => $job->job_id],
                            actorId: auth()->id(),
                            severity: 'low'
                        );
                        broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $clientId))->toOthers();
                    }
                } catch (\Exception $e) {
                    \Log::error('Gửi thông báo từ chối hàng loạt thất bại', ['error' => $e->getMessage()]);
                }
            }

            // Log bulk reject with reason
            AdminLogService::logBulk('reject', 'Job', $jobIds, "Từ chối hàng loạt $count công việc. Lý do: {$rejectReason}");

            return back()->with('success', "Đã từ chối $count công việc!");
        } else {
            return back()->with('error', 'Không có công việc nào hợp lệ để từ chối.');
        }
    }

    /**
     * Hiển thị lịch sử duyệt (các công việc đã duyệt hoặc từ chối)
     */
    public function history(Request $request)
    {
        $jobs = Job::whereIn('status', ['open', 'cancelled'])
            ->with(['account.profile', 'account.accountType'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // If AJAX request, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            $jobsData = $jobs->map(function ($job) {
                return [
                    'job_id' => $job->job_id,
                    'title' => $job->title,
                    'description' => $job->description,
                    'budget' => $job->budget,
                    'status' => $job->status,
                    'client_name' => $job->account->profile->fullname ?? 'N/A',
                    'client_email' => $job->account->email,
                    'updated_at' => $job->updated_at->format('d/m/Y H:i'),
                    'created_at' => $job->created_at->format('d/m/Y H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'jobs' => $jobsData
            ]);
        }

        // Otherwise return view
        return view('admin.jobs.history', compact('jobs'));
    }

    /**
     * Reset tất cả jobs về 0 (xóa hết)
     */
    public function resetAllJobs()
    {
        try {
            $count = \DB::table('jobs')->whereNull('deleted_at')->count();

            // Xóa tất cả jobs (bao gồm cả soft delete)
            \DB::table('jobs')->delete();

            // Xóa admin logs
            \DB::table('admin_logs')->delete();

            // Log action
            AdminLogService::log(
                'reset_all',
                'Job',
                null,
                "Reset hệ thống: Đã xóa tất cả {$count} công việc"
            );

            return back()->with('success', "Đã reset thành công! Xóa {$count} công việc.");
        } catch (\Exception $e) {
            \Log::error('Lỗi khi reset jobs: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi reset: ' . $e->getMessage());
        }
    }
}