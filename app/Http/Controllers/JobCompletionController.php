<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Job;
use Illuminate\Support\Facades\Schema;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Events\CommentNotificationBroadcasted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class JobCompletionController extends Controller
{
    public function complete(Request $request, Job $job)
    {
        $user = $request->user();

        // 1) Quyền
        if ((int) $job->account_id !== (int) $user->account_id) {
            return $this->resp($request, 403, 'Bạn không có quyền xác nhận job này.');
        }

        // 2) Trạng thái kết thúc/hủy thì bỏ qua
        if (in_array($job->status, ['completed', 'cancelled'], true)) {
            return $this->resp($request, 200, 'Job đã ở trạng thái kết thúc.', [
                'job_id' => $job->job_id,
                'status' => $job->status,
                'escrow_status' => $job->escrow_status,
            ]);
        }

        // 3) Phải funded & có freelancer đã được nhận
        if (($job->escrow_status ?? 'pending') !== 'funded') {
            return $this->resp($request, 422, 'Job chưa ở trạng thái ĐÃ THANH TOÁN (funded).');
        }

        $accepted = $job->applicants()->wherePivot('status', 2)->get(['accounts.account_id']);
        if ($accepted->isEmpty()) {
            return $this->resp($request, 422, 'Chưa có freelancer nào được nhận để giải ngân.');
        }

        // 4) Xác định số tiền
        $sourceField = (!is_null($job->total_budget) && $job->total_budget !== '')
            ? 'total_budget' : 'budget';
        $totalAmount = (int) round((float) ($job->{$sourceField} ?? 0));
        if ($totalAmount <= 0) {
            return $this->resp($request, 422, 'Ngân sách/tiền cọc không hợp lệ để giải ngân.');
        }

        // Sẽ lưu số tiền từng freelancer nhận để gửi thông báo sau commit
        $payouts = []; // [receiver_account_id => amount_cents]

        DB::beginTransaction();
        try {
            $n = $accepted->count();

            if ($n === 1) {
                $acc = $accepted->first();
                DB::table('accounts')
                    ->where('account_id', $acc->account_id)
                    ->update(['balance_cents' => DB::raw('COALESCE(balance_cents,0) + ' . $totalAmount)]);

                DB::table('disbursement_logs')->insert([
                    'job_id' => $job->job_id,
                    'payer_account_id' => $user->account_id,
                    'receiver_account_id' => $acc->account_id,
                    'amount_cents' => $totalAmount,
                    'currency' => 'VND',
                    'type' => 'payout_release',
                    'note' => 'Giải ngân toàn bộ cho freelancer (1 người)',
                    'meta' => json_encode([
                        'split' => '1/1',
                        'total_amount' => $totalAmount,
                        'source' => $sourceField,
                    ]),
                    'created_at' => now(),
                ]);

                $payouts[$acc->account_id] = $totalAmount;

            } else {
                $each = intdiv($totalAmount, $n);
                $remainder = $totalAmount - ($each * $n);

                foreach ($accepted as $idx => $acc) {
                    $amount = $each + ($idx === 0 ? $remainder : 0);

                    DB::table('accounts')
                        ->where('account_id', $acc->account_id)
                        ->update(['balance_cents' => DB::raw('COALESCE(balance_cents,0) + ' . $amount)]);

                    DB::table('disbursement_logs')->insert([
                        'job_id' => $job->job_id,
                        'payer_account_id' => $user->account_id,
                        'receiver_account_id' => $acc->account_id,
                        'amount_cents' => $amount,
                        'currency' => 'VND',
                        'type' => 'payout_release',
                        'note' => 'Giải ngân chia đều',
                        'meta' => json_encode([
                            'split' => ($idx + 1) . '/' . $n,
                            'total_amount' => $totalAmount,
                            'source' => $sourceField,
                        ]),
                        'created_at' => now(),
                    ]);

                    $payouts[$acc->account_id] = $amount;
                }
            }

            // Cập nhật job_apply: status=2 -> 3 (completed)
            $acceptedIds = array_keys($payouts);
            DB::table('job_apply')
                ->where('job_id', $job->job_id)
                ->whereIn('user_id', $acceptedIds)   // cột user_id đang lưu account_id
                ->where('status', 2)
                ->update([
                    'status' => 3,
                    'updated_at' => now(),
                ]);

            // 6) Cập nhật job
            $job->status = 'completed';
            $job->escrow_status = 'released';
            if (\Illuminate\Support\Facades\Schema::hasColumn($job->getTable(), 'released_at')) {
                $job->released_at = now();
            }
            $job->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->resp($request, 500, 'Có lỗi khi giải ngân: ' . $e->getMessage());
        }

        // 🔔 Gửi thông báo cho TẤT CẢ freelancer đã được nhận (vừa được giải ngân)
        foreach ($payouts as $receiverId => $amountCents) {
            try {
                $amountFmt = number_format($amountCents, 0, ',', '.'); // VND không cần .00
                $notification = app(NotificationService::class)->create(
                    userId: $receiverId,
                    type: Notification::TYPE_NOTIFICATION, // hoặc TYPE_JOB_COMPLETED nếu bạn có hằng này
                    title: 'Job đã hoàn thành & đã giải ngân',
                    body: "Công việc \"{$job->title}\" đã hoàn thành. Bạn vừa được giải ngân {$amountFmt}₫.",
                    meta: [
                        'job_id' => $job->job_id,
                        'amount' => $amountCents,
                        'currency' => 'VND',
                        'released_at' => (string) ($job->released_at ?? now()),
                    ],
                    actorId: $user->account_id, // chủ job
                    severity: 'medium'
                );

                // Broadcast realtime từng người
                broadcast(new CommentNotificationBroadcasted($notification, $receiverId))->toOthers();
                Cache::forget("header_json_{$receiverId}");
            } catch (\Throwable $e) {
                // Không làm hỏng response nếu 1 người fail notify
                Log::error('Broadcast hoàn thành job thất bại', [
                    'user_id' => $receiverId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->resp($request, 200, 'Đã hoàn thành job và giải ngân thành công.', [
            'job_id' => $job->job_id,
            'status' => $job->status,
            'escrow_status' => $job->escrow_status,
            'released_at' => $job->released_at ?? null,
        ]);
    }


    /**
     * Trả JSON nếu AJAX, ngược lại redirect kèm flash.
     */
    protected function resp(Request $request, int $status, string $message, array $data = [])
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'data' => $data,
            ], $status);
        }

        if ($status >= 400) {
            return back()->withErrors(['msg' => $message]);
        }
        return back()->with('success', $message);
    }


}
