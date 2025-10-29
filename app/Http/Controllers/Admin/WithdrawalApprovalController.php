<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DisbursementLog;
class WithdrawalApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString(); // '', processing, approved, rejected, paid
        $q      = trim((string)$request->input('q',''));

        $rows = WithdrawalLog::with(['account.profile'])
            ->when($status !== '', fn($qr) => $qr->where('status', $status))
            ->when($q !== '', function($qr) use ($q) {
                $qr->where(function($w) use ($q) {
                    $w->where('bank_account_number','like',"%{$q}%")
                      ->orWhere('bank_name','like',"%{$q}%")
                      ->orWhere('note','like',"%{$q}%")
                      ->orWhereHas('account', function($w2) use ($q){
                          $w2->where('email','like',"%{$q}%")
                             ->orWhere('name','like',"%{$q}%");
                      })
                      ->orWhereHas('account.profile', function($w3) use ($q){
                          $w3->where('fullname','like',"%{$q}%")
                             ->orWhere('username','like',"%{$q}%");
                      });
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        // Thống kê nhanh
        $stats = [
            'pending'  => WithdrawalLog::where('status','processing')->count(),
            'approved' => WithdrawalLog::where('status','complete')->count(),
            'rejected' => WithdrawalLog::where('status','failed')->count(),
            'paid'     => WithdrawalLog::where('status','paid')->count(),
            'total'    => WithdrawalLog::count(),
        ];

        return view('admin.withdrawals.index', compact('rows','status','q','stats'));
    }

    public function show(int $id)
{
    $w = WithdrawalLog::with(['account:account_id,name,email,account_id', 'account.profile:account_id,fullname'])
        ->findOrFail($id);

    // Lấy lịch sử cộng tiền vào ví của tài khoản này (receiver)
    $history = DisbursementLog::with(['job'])
        ->where('receiver_account_id', $w->account_id)
        ->orderByDesc('id')
        ->limit(20)
        ->get(['id','job_id','receiver_account_id','amount_cents','currency','type','note','created_at']);

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $w->id,
            'account' => $w->account,
            'bank_name' => $w->bank_name,
            'bank_account_number' => $w->bank_account_number,
            'amount_cents' => $w->amount_cents,
            'fee_cents' => $w->fee_cents,
            'status' => $w->status,
            'note' => $w->note,
            'created_at' => optional($w->created_at)->format('d/m/Y H:i'),
            // 👇 thêm lịch sử
            'history' => $history->map(function($d){
                return [
                    'id' => $d->id,
                    'job_id' => $d->job_id,
                    'job_title' => optional($d->job)->title,
                    'amount_cents' => $d->amount_cents,   // GIỮ NGUYÊN, không chia /100
                    'currency' => $d->currency,
                    'type' => $d->type,                   // vd: payout_release
                    'note' => $d->note,
                    'created_at' => optional($d->created_at)->format('d/m/Y H:i'),
                ];
            }),
        ],
    ]);
}

    public function approve(Request $request, $id)
    {
        $w = WithdrawalLog::find($id);
        if (!$w) return response()->json(['success'=>false,'message'=>'Không tìm thấy yêu cầu'],404);
        if ($w->status !== 'processing') {
            return response()->json(['success'=>false,'message'=>'Chỉ duyệt yêu cầu đang ở trạng thái processing'], 422);
        }

        DB::beginTransaction();
        try {
            // Cập nhật trạng thái
            $w->status = 'completed';
            $meta = $w->meta ?? [];
            $meta['approved_by'] = $request->user()->email ?? 'system';
            $meta['approved_at'] = now()->toDateTimeString();
            $w->meta = $meta;
            $w->save();

            DB::commit();
            return response()->json(['success'=>true,'message'=>'Đã duyệt yêu cầu rút tiền.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()],500);
        }
    }

    public function reject(Request $request, $id)
{
    return DB::transaction(function () use ($request, $id) {
        // Khóa bản ghi rút tiền để tránh 2 admin thao tác cùng lúc
        $w = WithdrawalLog::whereKey($id)->lockForUpdate()->first();
        if (!$w) {
            return response()->json(['success'=>false,'message'=>'Không tìm thấy yêu cầu'],404);
        }

        if ($w->status !== 'processing') {
            // Không cộng tiền lại nếu không ở trạng thái processing
            return response()->json(['success'=>false,'message'=>'Chỉ từ chối yêu cầu đang ở trạng thái processing'], 422);
        }

        $reason = trim((string)$request->input('reason',''));

        // 1) Cập nhật trạng thái + meta
        $meta = $w->meta ?? [];
        $meta['rejected_by'] = $request->user()->email ?? 'system';
        $meta['rejected_at'] = now()->toDateTimeString();
        if ($reason) $meta['reject_reason'] = $reason;

        $w->status = 'failed';        // hoặc 'rejected' nếu bạn dùng enum khác
        $w->meta   = $meta;
        $w->save();

        // 2) Cộng lại số dư ví cho tài khoản
        // Lưu ý: Account có PK là account_id
        $refundCents = (int) $w->amount_cents;  // chỉ cộng lại số tiền rút; nếu muốn cộng cả phí thì + (int)$w->fee_cents

        // Khóa row account để tránh cộng đúp
        DB::table('accounts')
            ->where('account_id', $w->account_id)
            ->lockForUpdate()
            ->increment('balance_cents', $refundCents);

        // (Tùy chọn) Lấy số dư mới để trả về
        $newBalance = DB::table('accounts')
            ->where('account_id', $w->account_id)
            ->value('balance_cents');

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối yêu cầu rút tiền và hoàn số dư vào ví.',
            'data' => [
                'withdrawal_id' => $w->id,
                'status' => $w->status,
                'refunded_cents' => $refundCents,
                'balance_cents' => (int) $newBalance,
            ]
        ]);
    });
}

    
}
