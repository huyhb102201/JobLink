<?php

namespace App\Services;

use App\Models\AdminLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdminLogService
{
    /**
     * Log an admin action
     *
     * @param string $action - create, update, delete, approve, reject, etc.
     * @param string $model - Model name (Account, Job, Payment, etc.)
     * @param int|null $modelId - ID of the affected model
     * @param string|null $description - Human-readable description
     * @param array|null $oldValues - Old values before change
     * @param array|null $newValues - New values after change
     * @return AdminLog
     */
    public static function log(
        string $action,
        string $model,
        ?int $modelId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AdminLog {
        return AdminLog::create([
            'admin_id' => Auth::id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log a create action
     */
    public static function logCreate(string $model, int $modelId, ?string $description = null, ?array $values = null): AdminLog
    {
        return self::log('create', $model, $modelId, $description, null, $values);
    }

    /**
     * Log an update action
     */
    public static function logUpdate(string $model, int $modelId, ?string $description = null, ?array $oldValues = null, ?array $newValues = null): AdminLog
    {
        return self::log('update', $model, $modelId, $description, $oldValues, $newValues);
    }

    /**
     * Log a delete action
     */
    public static function logDelete(string $model, int $modelId, ?string $description = null, ?array $oldValues = null): AdminLog
    {
        return self::log('delete', $model, $modelId, $description, $oldValues, null);
    }

    /**
     * Log an approve action
     */
    public static function logApprove(string $model, int $modelId, ?string $description = null): AdminLog
    {
        return self::log('approve', $model, $modelId, $description);
    }

    /**
     * Log a reject action
     */
    public static function logReject(string $model, int $modelId, ?string $description = null, ?string $reason = null): AdminLog
    {
        return self::log('reject', $model, $modelId, $description, null, $reason ? ['reason' => $reason] : null);
    }

    /**
     * Log a status change action
     */
    public static function logStatusChange(string $model, int $modelId, string $oldStatus, string $newStatus, ?string $description = null): AdminLog
    {
        return self::log(
            'status_change',
            $model,
            $modelId,
            $description ?? "Thay đổi trạng thái từ {$oldStatus} sang {$newStatus}",
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    /**
     * Log a bulk action
     */
    public static function logBulk(string $action, string $model, array $modelIds, ?string $description = null): AdminLog
    {
        return self::log(
            'bulk_' . $action,
            $model,
            null,
            $description ?? "Thao tác hàng loạt: {$action} trên " . count($modelIds) . " bản ghi",
            null,
            ['affected_ids' => $modelIds, 'count' => count($modelIds)]
        );
    }
}
