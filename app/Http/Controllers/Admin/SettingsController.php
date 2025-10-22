<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function index()
    {
        // System info
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_type' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ];

        // Database stats
        $dbStats = [
            'total_accounts' => DB::table('accounts')->count(),
            'total_jobs' => DB::table('jobs')->count(),
            'total_payments' => DB::table('payments')->count(),
            'total_logs' => DB::table('admin_logs')->count(),
            'database_size' => $this->getDatabaseSize(),
        ];

        // Cache stats
        $cacheKeys = [
            'admin_total_accounts',
            'admin_active_accounts',
            'admin_locked_accounts',
            'admin_unverified_accounts',
            'admin_account_types',
            'admin_job_statistics',
            'admin_total_verifications',
            'admin_pending_verifications',
            'admin_approved_verifications',
            'admin_rejected_verifications',
            'admin_membership_plans',
        ];

        $activeCaches = 0;
        foreach ($cacheKeys as $key) {
            if (Cache::has($key)) {
                $activeCaches++;
            }
        }

        return view('admin.settings.index', [
            'systemInfo' => $systemInfo,
            'dbStats' => $dbStats,
            'activeCaches' => $activeCaches,
            'totalCacheKeys' => count($cacheKeys),
        ]);
    }

    public function clearCache(Request $request)
    {
        try {
            $type = $request->input('type', 'all');

            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    $message = 'Đã xóa cache cấu hình thành công.';
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    $message = 'Đã xóa cache route thành công.';
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    $message = 'Đã xóa cache view thành công.';
                    break;
                case 'application':
                    Cache::flush();
                    $message = 'Đã xóa cache ứng dụng thành công.';
                    break;
                case 'all':
                default:
                    Artisan::call('optimize:clear');
                    $message = 'Đã xóa toàn bộ cache thành công.';
                    break;
            }

            Log::info("Admin cleared cache: {$type}", ['admin_id' => auth()->id()]);

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error clearing cache: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa cache: ' . $e->getMessage());
        }
    }

    public function optimizeApp(Request $request)
    {
        try {
            // Clear all caches
            Artisan::call('optimize:clear');
            
            // Optimize
            Artisan::call('optimize');
            
            // Cache config
            Artisan::call('config:cache');
            
            // Cache routes
            Artisan::call('route:cache');
            
            // Cache views
            Artisan::call('view:cache');

            Log::info('Admin optimized application', ['admin_id' => auth()->id()]);

            return back()->with('success', 'Đã tối ưu hóa ứng dụng thành công!');
        } catch (\Exception $e) {
            Log::error('Error optimizing app: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi tối ưu hóa: ' . $e->getMessage());
        }
    }

    public function clearLogs(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            
            $deleted = DB::table('admin_logs')
                ->where('created_at', '<', now()->subDays($days))
                ->delete();

            Log::info("Admin cleared old logs: {$deleted} records older than {$days} days", ['admin_id' => auth()->id()]);

            return back()->with('success', "Đã xóa {$deleted} bản ghi log cũ hơn {$days} ngày.");
        } catch (\Exception $e) {
            Log::error('Error clearing logs: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa logs: ' . $e->getMessage());
        }
    }

    private function getDatabaseSize()
    {
        try {
            $database = config('database.connections.mysql.database');
            $result = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$database]);

            return $result[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
