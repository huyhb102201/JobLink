<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AdminLog::with('admin.profile')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        // Preload log details for instant modal display
        $logDetails = [];
        foreach ($logs as $log) {
            $logDetails[$log->id] = [
                'id' => $log->id,
                'admin_name' => $log->admin->profile->fullname ?? $log->admin->email ?? 'System',
                'admin_email' => $log->admin->email ?? 'N/A',
                'action' => $log->action,
                'action_label' => $this->formatActionLabel($log->action),
                'model' => $log->model,
                'model_id' => $log->model_id,
                'description' => $log->description,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => optional($log->created_at)->format('d/m/Y H:i:s'),
            ];
        }

        // Statistics
        $totalLogs = AdminLog::count();
        $todayLogs = AdminLog::whereDate('created_at', today())->count();
        $uniqueAdmins = AdminLog::distinct('admin_id')->count('admin_id');
        $recentActions = AdminLog::where('created_at', '>=', now()->subHours(24))->count();

        return view('admin.logs.index', [
            'logs' => $logs,
            'logDetails' => $logDetails,
            'totalLogs' => $totalLogs,
            'todayLogs' => $todayLogs,
            'uniqueAdmins' => $uniqueAdmins,
            'recentActions' => $recentActions,
        ]);
    }

    public function show($id)
    {
        try {
            $log = AdminLog::with('admin.profile')->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy log.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'log' => [
                    'id' => $log->id,
                    'admin_name' => $log->admin->profile->fullname ?? $log->admin->email ?? 'System',
                    'admin_email' => $log->admin->email ?? 'N/A',
                    'action' => $log->action,
                    'action_label' => $this->formatActionLabel($log->action),
                    'model' => $log->model,
                    'model_id' => $log->model_id,
                    'description' => $log->description,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => optional($log->created_at)->format('d/m/Y H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi lấy thông tin log.'
            ], 500);
        }
    }

    private function formatActionLabel(string $action): string
    {
        return match ($action) {
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
            'status_change' => 'Thay đổi trạng thái',
            'bulk_delete' => 'Xóa hàng loạt',
            'bulk_approve' => 'Duyệt hàng loạt',
            'bulk_reject' => 'Từ chối hàng loạt',
            'bulk_lock' => 'Khóa hàng loạt',
            'bulk_unlock' => 'Mở khóa hàng loạt',
            default => ucfirst($action),
        };
    }
}
