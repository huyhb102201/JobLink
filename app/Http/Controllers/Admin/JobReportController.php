<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobReport;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobReportController extends Controller
{
    /**
     * Hiển thị danh sách báo cáo job
     */
    public function index(Request $request)
    {
        // Lấy danh sách job_id và số lượng báo cáo
        $reportCounts = JobReport::select('job_id', DB::raw('COUNT(*) as report_count'))
            ->groupBy('job_id')
            ->orderBy('report_count', 'desc')
            ->pluck('report_count', 'job_id');

        if ($reportCounts->isEmpty()) {
            return view('admin.job-reports.index', [
                'reportsData' => [],
                'totalReports' => 0,
                'totalJobsReported' => 0,
                'jobsReportedThisWeek' => 0,
                'jobsReportedThisMonth' => 0,
            ]);
        }

        $jobIds = $reportCounts->keys()->toArray();

        // Load tất cả jobs cùng lúc với eager loading
        $jobs = Job::with('account.profile')
            ->whereIn('job_id', $jobIds)
            ->get()
            ->keyBy('job_id');

        // Load tất cả reports cùng lúc với eager loading
        $allJobReports = JobReport::with('user.profile')
            ->whereIn('job_id', $jobIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('job_id');

        // Lấy trạng thái locked cho tất cả jobs (status = 2)
        $lockedJobs = JobReport::whereIn('job_id', $jobIds)
            ->where('status', 2)
            ->distinct()
            ->pluck('job_id')
            ->toArray();

        // Xử lý dữ liệu
        $reportsData = [];
        foreach ($reportCounts as $jobId => $reportCount) {
            $job = $jobs->get($jobId);
            
            // Thông tin job
            $jobTitle = $job ? $job->title : '[Job đã bị xóa]';
            $jobOwner = 'N/A';
            $jobOwnerEmail = 'N/A';
            
            if ($job && $job->account) {
                if ($job->account->profile && !empty($job->account->profile->username)) {
                    $jobOwner = $job->account->profile->username;
                } elseif (!empty($job->account->name)) {
                    $jobOwner = $job->account->name;
                }
                $jobOwnerEmail = $job->account->email ?? 'N/A';
            }
            
            // Nhóm báo cáo theo người dùng
            $reportsByUser = [];
            $jobReports = $allJobReports->get($jobId, collect());
            
            foreach ($jobReports as $jobReport) {
                $userId = $jobReport->user_id;
                if (!isset($reportsByUser[$userId])) {
                    $username = 'N/A';
                    if ($jobReport->user && $jobReport->user->profile && !empty($jobReport->user->profile->username)) {
                        $username = $jobReport->user->profile->username;
                    } elseif ($jobReport->user && !empty($jobReport->user->name)) {
                        $username = $jobReport->user->name;
                    }
                    
                    $reportsByUser[$userId] = [
                        'username' => $username,
                        'email' => $jobReport->user->email ?? 'N/A',
                        'report_count' => 0,
                        'reports' => []
                    ];
                }
                $reportsByUser[$userId]['report_count']++;
                
                // Xử lý ảnh
                $images = [];
                if ($jobReport->img) {
                    $imageUrls = explode(',', $jobReport->img);
                    $images = array_slice($imageUrls, 0, 5);
                }
                
                $reportsByUser[$userId]['reports'][] = [
                    'reason' => $jobReport->reason,
                    'message' => $jobReport->message,
                    'images' => $images,
                    'created_at' => $jobReport->created_at->format('d/m/Y H:i'),
                ];
            }
            
            $reportsData[] = [
                'job_id' => $jobId,
                'job_title' => $jobTitle,
                'job_owner' => $jobOwner,
                'job_owner_email' => $jobOwnerEmail,
                'report_count' => $reportCount,
                'status' => in_array($jobId, $lockedJobs) ? 'locked' : 'active',
                'reporters' => array_values($reportsByUser),
            ];
        }

        // Thống kê
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return view('admin.job-reports.index', [
            'reportsData' => $reportsData,
            'totalReports' => JobReport::count(),
            'totalJobsReported' => count($reportsData),
            'jobsReportedThisWeek' => JobReport::whereBetween('created_at', [$startOfWeek, $endOfWeek])->distinct('job_id')->count('job_id'),
            'jobsReportedThisMonth' => JobReport::whereBetween('created_at', [$startOfMonth, $endOfMonth])->distinct('job_id')->count('job_id'),
        ]);
    }

    /**
     * Lấy chi tiết báo cáo của một job
     */
    public function getDetails($jobId)
    {
        $reports = JobReport::with(['user.profile', 'job'])
            ->where('job_id', $jobId)
            ->orderBy('created_at', 'desc')
            ->get();

        $job = Job::with('account.profile')->find($jobId);

        if (!$job) {
            return response()->json(['error' => 'Job không tồn tại'], 404);
        }

        // Nhóm báo cáo theo người dùng
        $reportsByUser = [];
        foreach ($reports as $report) {
            $userId = $report->user_id;
            if (!isset($reportsByUser[$userId])) {
                // Lấy username, nếu rỗng thì lấy name
                $username = 'N/A';
                if ($report->user && $report->user->profile && !empty($report->user->profile->username)) {
                    $username = $report->user->profile->username;
                } elseif ($report->user && !empty($report->user->name)) {
                    $username = $report->user->name;
                }
                
                $reportsByUser[$userId] = [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $report->user->email ?? 'N/A',
                    'avatar' => $report->user->avatar_url ?? null,
                    'report_count' => 0,
                    'reports' => []
                ];
            }
            $reportsByUser[$userId]['report_count']++;
            
            // Xử lý ảnh - split theo dấu phẩy và giới hạn 5 ảnh
            $images = [];
            if ($report->img) {
                $imageUrls = explode(',', $report->img);
                $images = array_slice($imageUrls, 0, 5); // Giới hạn tối đa 5 ảnh
            }
            
            $reportsByUser[$userId]['reports'][] = [
                'id' => $report->id,
                'reason' => $report->reason,
                'message' => $report->message,
                'images' => $images,
                'created_at' => $report->created_at->format('d/m/Y H:i'),
            ];
        }

        // Lấy tên chủ job
        $jobOwner = 'N/A';
        if ($job->account) {
            if ($job->account->profile && !empty($job->account->profile->username)) {
                $jobOwner = $job->account->profile->username;
            } elseif (!empty($job->account->name)) {
                $jobOwner = $job->account->name;
            }
        }
        
        return response()->json([
            'job' => [
                'id' => $job->job_id,
                'title' => $job->title,
                'owner' => $jobOwner,
                'owner_email' => $job->account->email ?? 'N/A',
            ],
            'total_reports' => $reports->count(),
            'reporters' => array_values($reportsByUser),
        ]);
    }

    /**
     * Xóa một báo cáo
     */
    public function destroy($id)
    {
        try {
            $report = JobReport::findOrFail($id);
            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa báo cáo thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa tất cả báo cáo của một job
     */
    public function destroyByJob($jobId)
    {
        try {
            JobReport::where('job_id', $jobId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa tất cả báo cáo của job này'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle khóa/mở khóa tất cả báo cáo của một job
     */
    public function toggleLockByJob($jobId)
    {
        try {
            // Kiểm tra trạng thái hiện tại (2 = locked)
            $currentStatus = JobReport::where('job_id', $jobId)
                ->where('status', 2)
                ->exists();
            
            // Toggle status: 2 (locked) <-> 1 (active)
            $newStatus = $currentStatus ? 1 : 2;
            $actionText = $currentStatus ? 'mở khóa' : 'khóa';
            
            JobReport::where('job_id', $jobId)->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Đã {$actionText} tất cả báo cáo của job này",
                'new_status' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Khóa hàng loạt báo cáo
     */
    public function bulkLock(Request $request)
    {
        try {
            $jobIds = $request->input('job_ids', []);
            
            if (empty($jobIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có job nào được chọn'
                ], 400);
            }

            // Cập nhật status = 2 (đã khóa)
            JobReport::whereIn('job_id', $jobIds)->update(['status' => 2]);

            return response()->json([
                'success' => true,
                'message' => 'Đã khóa thành công báo cáo của ' . count($jobIds) . ' job',
                'count' => count($jobIds)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mở khóa hàng loạt báo cáo
     */
    public function bulkUnlock(Request $request)
    {
        try {
            $jobIds = $request->input('job_ids', []);
            
            if (empty($jobIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có job nào được chọn'
                ], 400);
            }

            // Cập nhật status = 1 (chờ xử lý)
            JobReport::whereIn('job_id', $jobIds)->update(['status' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'Đã mở khóa thành công báo cáo của ' . count($jobIds) . ' job',
                'count' => count($jobIds)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
