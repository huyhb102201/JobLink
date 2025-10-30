<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobReport;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JobFavorite;
use App\Models\Task;
use App\Models\Comment;
use App\Models\JobApply;
use App\Models\JobDetail;
use App\Models\JobView;
use App\Models\Account;
class JobReportController extends Controller
{
    /**
     * Trang liệt kê các job bị report (có tìm kiếm nhẹ).
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        // Gom báo cáo theo job_id trước
        $agg = JobReport::query()
            ->select([
                'job_id',
                DB::raw('COUNT(*) AS report_count'),
                DB::raw('MAX(status) AS status') // 1/2
            ])
            ->groupBy('job_id');

        // Join sang jobs → accounts → profiles
        $rows = DB::query()
            ->fromSub($agg, 'jr') // jr: (job_id, report_count, status)
            ->Join('jobs', 'jobs.job_id', '=', 'jr.job_id')
            ->Join('accounts', 'accounts.account_id', '=', 'jobs.account_id')   // <= dùng account_id như bạn
            ->leftJoin('profiles', 'profiles.account_id', '=', 'accounts.account_id')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('jobs.title', 'like', "%{$q}%")
                        ->orWhere('profiles.fullname', 'like', "%{$q}%")
                        ->orWhere('profiles.username', 'like', "%{$q}%")
                        ->orWhere('accounts.name', 'like', "%{$q}%")
                        ->orWhere('accounts.email', 'like', "%{$q}%")
                        ->orWhere('jr.job_id', '=', (int) $q);
                });
            })
            ->select([
                'jr.job_id',
                'jobs.title AS job_title',
                // Ưu tiên fullname → username → accounts.name
                DB::raw("COALESCE(profiles.fullname, profiles.username, accounts.name, 'N/A') AS owner_name"),
                'accounts.email AS owner_email',
                'accounts.account_id AS owner_id',
                'jr.report_count',
                'jr.status'
            ])
            ->orderByDesc('jr.report_count')
            ->paginate(12)
            ->withQueryString();

        // Thống kê nhanh
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $reportsPending = JobReport::where('status', 1)->distinct('job_id')->count('job_id');
        $reportsResolved = JobReport::where('status', 2)->distinct('job_id')->count('job_id');
        return view('admin.job-reports.index', [
            'rows' => $rows,
            'totalReports' => JobReport::count(),
            'totalJobsReported' => JobReport::distinct('job_id')->count('job_id'),
            'jobsReportedThisWeek' => JobReport::whereBetween('created_at', [$startOfWeek, $endOfWeek])->distinct('job_id')->count('job_id'),
            'jobsReportedThisMonth' => JobReport::whereBetween('created_at', [$startOfMonth, $endOfMonth])->distinct('job_id')->count('job_id'),
            'q' => $q,
            'reportsPending' => $reportsPending,
            'reportsResolved' => $reportsResolved,
        ]);
    }
    /**
     * AJAX: Lấy người report + chi tiết trong 1 job.
     */
    public function fetchReporters(int $jobId)
    {
        $reports = JobReport::with(['reporter.profile'])
            ->where('job_id', $jobId)
            ->latest('id')
            ->get();

        if ($reports->isEmpty()) {
            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'reporters' => [],
            ]);
        }

        $grouped = $reports->groupBy('user_id')->map(function ($items) {
            $first = $items->first();
            $acc = optional($first->reporter);
            $profile = optional($acc->profile);

            return [
                'fullname' => $profile->fullname ?? $acc->name ?? 'N/A',
                'email' => $profile->email ?? $acc->email ?? 'N/A',
                'report_count' => $items->count(),
                'reports' => $items->map(function (JobReport $r) {
                    return [
                        'reason' => $r->reason,
                        'message' => $r->message,
                        'created_at' => optional($r->created_at)->format('d/m/Y H:i'),
                        'images' => $this->parseImages($r->img),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'job_id' => $jobId,
            'reporters' => $grouped,
        ]);
    }

    private function parseImages(?string $img): array
    {
        if (!$img)
            return [];
        // tách bằng dấu phẩy, bỏ rỗng, giới hạn 5 ảnh
        return array_slice(array_values(array_filter(array_map('trim', explode(',', $img)))), 0, 5);
    }

    public function deleteJob(int $jobId)
    {
        $job = Job::find($jobId);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job không tồn tại hoặc đã bị xóa.'
            ], 404);
        }

        try {
            DB::transaction(function () use ($jobId, $job) {
                // 🟢 1) Cập nhật trạng thái các báo cáo sang "đã xử lý" (2)
                JobReport::where('job_id', $jobId)->update(['status' => 2]);

                // 🟢 2) Dọn toàn bộ bảng liên quan (nếu không dùng FK cascade)
                JobFavorite::where('job_id', $jobId)->delete();
                JobApply::where('job_id', $jobId)->delete();
                JobDetail::where('job_id', $jobId)->delete();
                JobView::where('job_id', $jobId)->delete();
                Task::where('job_id', $jobId)->delete();
                Comment::where('job_id', $jobId)->delete();

                // 🟢 3) Xóa Job chính
                $job->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa Job và cập nhật trạng thái báo cáo thành công.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa job: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function lockAndPurge(int $accountId, Request $request)
    {
        // 1) Tìm account
        $account = Account::find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tài khoản.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($account, $accountId) {

                // 2) Khóa tài khoản
                // (giả sử cột 'status' kiểu int: 1=active, 0=locked)
                $account->status = 0;
                $account->save();

                // 3) Lấy các job thuộc tài khoản
                $jobIds = Job::where('account_id', $accountId)->pluck('job_id')->all();

                if (!empty($jobIds)) {

                    // 3.1) Đánh dấu toàn bộ báo cáo của các job này là đã xử lý
                    JobReport::whereIn('job_id', $jobIds)->update(['status' => 2]);

                    // 3.2) Xóa dữ liệu liên quan (nếu không dùng FK cascade)
                    JobFavorite::whereIn('job_id', $jobIds)->delete();
                    JobApply::whereIn('job_id', $jobIds)->delete();
                    JobDetail::whereIn('job_id', $jobIds)->delete();
                    JobView::whereIn('job_id', $jobIds)->delete();
                    Task::whereIn('job_id', $jobIds)->delete();
                    Comment::whereIn('job_id', $jobIds)->delete();

                    // 3.3) Xóa job
                    // Nếu Job đang dùng SoftDeletes, forceDelete để xóa hẳn:
                    $jobsQuery = Job::whereIn('job_id', $jobIds);
                    $uses = class_uses(Job::class);
                    if ($uses && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses)) {
                        $jobsQuery->forceDelete();   // xóa vĩnh viễn
                    } else {
                        $jobsQuery->delete();        // xóa thường
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Đã khóa tài khoản và dọn toàn bộ job liên quan.',
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khóa tài khoản: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function reject(int $jobId)
    {
        $affected = JobReport::where('job_id', $jobId)->update(['status' => 0]);

        // Tạm ghi log để kiểm tra
        \Log::info("[reject] job_id=$jobId, affected=$affected, db=" . \DB::connection()->getDatabaseName());

        if ($affected === 0) {
            return response()->json(['success' => false, 'message' => 'Không có bản ghi nào được cập nhật. Kiểm tra job_id hoặc điều kiện WHERE.'], 422);
        }

        return response()->json(['success' => true, 'affected' => $affected]);
    }



}
