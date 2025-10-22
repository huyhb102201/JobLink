<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Job;
use App\Models\Payment;
use App\Models\JobPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        // Cache dashboard data for 5 minutes to improve performance
        $cacheKey = 'dashboard_stats';
        $cacheDuration = 300; // 5 minutes

        $data = Cache::remember($cacheKey, $cacheDuration, function () {
            // 1. Tổng số người dùng (tất cả tài khoản)
            $totalUsers = Account::count();

            // 2. Số tài khoản đang kích hoạt (status = 1)
            $activeUsers = Account::where('status', 1)->count();

            // 3. Số tài khoản bị khóa (status = 0)
            $lockedUsers = Account::where('status', 0)->count();

            // 4. Số tài khoản chưa xác minh email
            $unverifiedUsers = Account::whereNull('email_verified_at')->count();

            // 5. Tổng số job được đăng (tối ưu query)
            $totalJobs = Job::whereNotIn('status', ['pending'])->count();

            // 6. Job đang mở (tối ưu query)
            $openJobs = Job::whereNotIn('status', ['pending', 'cancelled'])->count();

            // 7. Số job đã được thanh toán thành công (tối ưu với COUNT DISTINCT)
            $paidJobs = DB::table('job_payments')
                ->where('status', 'paid')
                ->distinct()
                ->count('job_id');

            // 8. Doanh thu hệ thống (từ bảng payments - thanh toán membership)
            $totalRevenue = Payment::whereIn('status', ['PAID', 'success'])
                ->whereNotNull('account_id')
                ->whereHas('account')
                ->sum('amount') ?? 0;
            
            // Doanh thu từ job payments (status = 'paid' chữ thường)
            $jobRevenue = JobPayment::where('status', 'paid')->sum('amount') ?? 0;
            
            // Tổng doanh thu
            $systemRevenue = $totalRevenue + $jobRevenue;

            // 9. Job đang còn mở hoặc trong tiến trình (cho thống kê khác)
            $activeJobs = Job::whereIn('status', ['OPEN', 'IN_PROGRESS'])->count();

            // 6. Thống kê theo vai trò (Users Breakdown) - Hiển thị tất cả account types kể cả 0 user
            $usersByRole = DB::table('account_types')
                ->leftJoin('accounts', 'account_types.account_type_id', '=', 'accounts.account_type_id')
                ->select('account_types.name as type_name', DB::raw('COUNT(accounts.account_id) as total'))
                ->groupBy('account_types.account_type_id', 'account_types.name')
                ->orderBy('total', 'desc')
                ->get();

            // Thống kê job theo trạng thái (cho biểu đồ)
            $jobsByStatus = Job::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            // Thống kê thanh toán theo trạng thái
            $paymentsByStatus = Payment::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            // Thống kê job theo tháng (6 tháng gần nhất)
            $jobsByMonth = Job::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('count(*) as total')
                )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get();

            // Thống kê doanh thu theo tháng (6 tháng gần nhất)
            $revenueByMonth = Payment::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(amount) as total')
                )
                ->whereIn('status', ['PAID', 'success'])
                ->whereNotNull('account_id')
                ->whereHas('account')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get();

            return [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'lockedUsers' => $lockedUsers,
                'unverifiedUsers' => $unverifiedUsers,
                'totalJobs' => $totalJobs,
                'openJobs' => $openJobs,
                'paidJobs' => $paidJobs,
                'systemRevenue' => $systemRevenue,
                'activeJobs' => $activeJobs,
                'usersByRole' => $usersByRole,
                'jobsByStatus' => $jobsByStatus,
                'paymentsByStatus' => $paymentsByStatus,
                'jobsByMonth' => $jobsByMonth,
                'revenueByMonth' => $revenueByMonth,
            ];
        });

        // Extract data from cache
        extract($data);

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'lockedUsers',
            'unverifiedUsers',
            'totalJobs',
            'openJobs',
            'paidJobs',
            'systemRevenue',
            'activeJobs',
            'usersByRole',
            'jobsByStatus',
            'paymentsByStatus',
            'jobsByMonth',
            'revenueByMonth'
        ));
    }

    // API endpoint để lấy dữ liệu thống kê real-time
    public function getStats()
    {
        $totalUsers = Account::count();
        $activeUsers = Account::where('status', 1)->count();
        $lockedUsers = Account::where('status', 0)->count();
        $unverifiedUsers = Account::whereNull('email_verified_at')->count();
        $totalJobs = Job::whereNull('deleted_at')->whereNotIn('status', ['pending'])->count();
        $openJobs = Job::whereNull('deleted_at')->whereNotIn('status', ['pending', 'cancelled'])->count();
        $paidJobs = JobPayment::where('status', 'paid')->distinct('job_id')->count('job_id');
        $totalRevenue = Payment::whereIn('status', ['PAID', 'success'])
            ->whereNotNull('account_id')
            ->whereHas('account')
            ->sum('amount');
        $jobRevenue = JobPayment::where('status', 'paid')->sum('amount');
        $systemRevenue = $totalRevenue + $jobRevenue;

        $usersByRole = DB::table('account_types')
            ->leftJoin('accounts', 'account_types.account_type_id', '=', 'accounts.account_type_id')
            ->select('account_types.name as type_name', DB::raw('COUNT(accounts.account_id) as total'))
            ->groupBy('account_types.account_type_id', 'account_types.name')
            ->orderBy('total', 'desc')
            ->get();

        $jobsByStatus = Job::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $paymentsByStatus = Payment::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'lockedUsers' => $lockedUsers,
            'unverifiedUsers' => $unverifiedUsers,
            'totalJobs' => $totalJobs,
            'openJobs' => $openJobs,
            'paidJobs' => $paidJobs,
            'systemRevenue' => $systemRevenue,
            'usersByRole' => $usersByRole,
            'jobsByStatus' => $jobsByStatus,
            'paymentsByStatus' => $paymentsByStatus,
        ]);
    }
}
