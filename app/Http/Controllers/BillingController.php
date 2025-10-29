<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\DisbursementLog;
use Illuminate\Support\Facades\DB;
class BillingController extends Controller
{
    /**
     * Che số tài khoản theo format 12********12
     */
    private function maskAccount(?string $number): string
    {
        if (!$number)
            return '';

        // chỉ giữ chữ số
        $digits = preg_replace('/\D+/', '', (string) $number);
        $len = strlen($digits);

        if ($len <= 4)
            return $digits; // quá ngắn thì khỏi che

        $first = substr($digits, 0, 2);
        $last = substr($digits, -2);

        return $first . str_repeat('*', max(0, $len - 4)) . $last;
    }

    /**
     * GET /settings/billing
     */
    public function index()
    {
        $user = Auth::user();

        // Chủ sở hữu: ưu tiên account_id nếu có, fallback user->id
        $ownerId = $user?->account_id ?? $user->id;

        // Danh sách thẻ
        $cards = BankAccount::where('account_id', $ownerId)
            ->orderByDesc('is_default')
            ->get()
            ->map(function (BankAccount $c) {
                $masked = $this->maskAccount($c->account_number);
                return [
                    'id' => $c->id,
                    'bank_name' => $c->bank_name,
                    'bank' => $c->bank_name,        // tương thích blade
                    'account_holder' => $c->account_holder,
                    'account_number' => $c->account_number,
                    'card_number' => $c->account_number,   // tương thích blade
                    'masked' => $masked,
                    'masked_account' => $masked,
                    'is_default' => (bool) $c->is_default,
                ];
            });

        // (Tuỳ) dữ liệu mẫu giao dịch cũ

        // Số dư: đang lấy từ users.balance_cents
        $balanceCents = (int) ($user->balance_cents ?? 0);

        // Lịch sử cộng tiền (disbursement_logs) – receiver_account_id = $ownerId
        $creditLogs = DisbursementLog::query()
            ->where('receiver_account_id', $ownerId)
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'job_id', 'amount_cents', 'currency', 'type', 'note', 'meta', 'created_at']);

        // Lịch sử rút (withdrawal_logs)
        $withdrawLogs = DB::table('withdrawal_logs')
            ->where('account_id', $ownerId)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'bank_account_number', 'amount_cents', 'fee_cents', 'currency', 'status', 'note', 'created_at']);

        // View expects $account -> gán tạm bằng $user (hoặc lấy từ bảng accounts nếu bạn có model riêng)
        $account = $user;
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        // Tổng đã rút trong tháng (đếm processing + completed)
        $usedThisMonth = (int) DB::table('withdrawal_logs')
            ->where('account_id', $ownerId)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereIn('status', ['processing', 'completed'])
            ->sum('amount_cents');
        $typeRow = DB::table('account_types')
            ->where('account_type_id', $account->account_type_id)
            ->first();

        // Lấy hạn mức từ cột withdraw_month_limit_cents trong account_types
        $limit = DB::table('accounts as a')
            ->join('account_types as t', 'a.account_type_id', '=', 't.account_type_id')
            ->where('a.account_id', $user->account_id)
            ->value('t.withdraw_monthly_limit_cents');

        if ($typeRow && isset($typeRow->withdraw_month_limit_cents)) {
            $limit = (int) $typeRow->withdraw_month_limit_cents;
        }
      
        // Nếu bảng không có hoặc bị null → fallback theo mặc định
        if ($limit <= 0) {
            $code = strtoupper((string) ($typeRow?->code ?? $typeRow?->name ?? 'F_BASIC'));
            $defaults = [
                'F_BASIC' => 4_000_000,
                'F_PLUS' => 40_000_000,
            ];
            $limit = $defaults[$code] ?? $defaults['F_BASIC'];
        }


        $remain = max(0, $limit - $usedThisMonth);
        $percent = $limit > 0 ? min(100, (int) round($usedThisMonth / $limit * 100)) : 0;
        return view('settings.billing', [
            'cards' => $cards,  // bỏ nếu không dùng nữa
            'account' => $account,        // ✅ thêm biến này
            'balance_cents' => $balanceCents,   // ✅ tên key khớp blade
            'creditLogs' => $creditLogs,
            'withdrawLogs' => $withdrawLogs,   // ✅ truyền ra để hiển thị lịch sử rút
            'user' => $user,
            'withdraw_meter' => [
                'limit_cents' => $limit,
                'used_cents' => $usedThisMonth,
                'remain_cents' => $remain,
                'percent' => $percent,
                'month_label' => $now->format('m/Y'),
            ],
        ]);
    }

    /**
     * POST /settings/billing/add-card
     * route name: settings.billing.addCard
     */
    public function addCard(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'card_number' => 'required|string|max:50',
            // optional
            'bank_code' => 'nullable|string|max:20',
            'bank_short' => 'nullable|string|max:100',
            'bank_bin' => 'nullable|string|max:32',
        ]);

        $user = Auth::user();
        $userId = $user?->account_id ?? Auth::id();
        if (!$userId) {
            return $request->ajax() || $request->wantsJson()
                ? response()->json(['message' => 'Bạn cần đăng nhập để liên kết thẻ.'], 401)
                : back()->with('error', 'Bạn cần đăng nhập để liên kết thẻ.');
        }

        // Chống trùng
        $exists = \App\Models\BankAccount::where('account_id', $userId)
            ->where('account_number', $validated['card_number'])
            ->exists();
        if ($exists) {
            return $request->ajax() || $request->wantsJson()
                ? response()->json([
                    'message' => 'Thẻ ngân hàng này đã được liên kết trước đó!',
                    'errors' => ['card_number' => ['Bạn đã liên kết thẻ này rồi']],
                ], 422)
                : back()->with('error', 'Số tài khoản này đã được bạn thêm trước đó.');
        }

        // Hợp nhất hiển thị: "Tên đầy đủ (Short / CODE)"
        $display = $validated['bank_name'];                 // client đã gửi label đầy đủ
        $short = (string) $request->input('bank_short', '');
        $code = (string) $request->input('bank_code', '');
        if ($display && !str_contains($display, '(')) {
            // nếu client chưa gửi label đầy đủ, tự ghép
            $parts = array_filter([$short ?: null, $code ?: null]);
            if (!empty($parts)) {
                $display = $display . ' (' . implode(' / ', $parts) . ')';
            }
        }

        $card = \App\Models\BankAccount::create([
            'account_id' => $userId,
            'bank_name' => $display,                     // <-- lưu label đầy đủ
            'account_number' => $validated['card_number'],
            'account_holder' => $user->fullname ?? $user->name ?? 'Người dùng',
            'is_default' => false,
            // Nếu bạn có cột riêng, có thể lưu thêm bên dưới:
            // 'bank_code'   => $code,
            // 'bank_bin'    => $request->input('bank_bin'),
            // 'bank_short'  => $short,
        ]);

        $masked = $this->maskAccount($card->account_number);

        return $request->ajax() || $request->wantsJson()
            ? response()->json([
                'message' => 'Đã thêm thẻ ngân hàng thành công!',
                'card' => [
                    'id' => $card->id,
                    'bank_name' => $card->bank_name,
                    'bank' => $card->bank_name,
                    'account_number' => $card->account_number,
                    'card_number' => $card->account_number,
                    'masked' => $masked,
                    'masked_account' => $masked,
                    'is_default' => (bool) $card->is_default,
                ],
            ])
            : back()->with('success', 'Đã thêm thẻ ngân hàng thành công!');
    }


    // app/Http/Controllers/Settings/BillingController.php

    public function deleteCard(Request $request)
    {
        $request->validate([
            'account_number' => 'required|string|max:50',
        ]);

        // Lấy account_id đúng guard (ưu tiên account_id)
        $user = \Auth::user();
        $userId = $user?->account_id ?? \Auth::id();

        if (!$userId) {
            return $request->ajax() || $request->wantsJson()
                ? response()->json(['message' => 'Bạn cần đăng nhập.'], 401)
                : back()->with('error', 'Bạn cần đăng nhập.');
        }

        // Tìm thẻ trong phạm vi user hiện tại
        $card = \App\Models\BankAccount::where('account_id', $userId)
            ->where('account_number', $request->account_number)
            ->first();

        if (!$card) {
            return $request->ajax() || $request->wantsJson()
                ? response()->json(['message' => 'Không tìm thấy thẻ cần xóa.'], 404)
                : back()->with('error', 'Không tìm thấy thẻ cần xóa.');
        }

        // (tùy chọn) Chặn xóa nếu là thẻ mặc định và vẫn còn thẻ khác
        // if ($card->is_default && \App\Models\BankAccount::where('account_id',$userId)->count() > 1) {
        //     return response()->json(['message' => 'Hãy đặt thẻ khác làm mặc định trước khi xóa thẻ này.'], 422);
        // }

        $card->delete();

        return $request->ajax() || $request->wantsJson()
            ? response()->json(['message' => 'Đã xóa thẻ ngân hàng.'])
            : back()->with('success', 'Đã xóa thẻ ngân hàng.');
    }
    //api danh sách ngân hàng
    public function bankcodes()
    {
        try {
            $data = Cache::remember('momo_bankcodes_v2', 60 * 60 * 24, function () {
                $res = Http::timeout(10)->get('https://test-payment.momo.vn/v2/gateway/api/bankcodes');
                if (!$res->ok()) {
                    throw new \Exception('MoMo trả về lỗi: ' . $res->status());
                }
                return $res->json();
            });

            return response()->json($data, 200);
        } catch (\Throwable $e) {
            // Fallback: trả list trống hoặc dữ liệu tĩnh tối thiểu
            return response()->json([
                'error' => true,
                'message' => 'Không lấy được danh sách ngân hàng từ MoMo.',
            ], 502);
        }
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:10000',
            'to_account_number' => 'required|string|max:50',
        ]);

        $user = \Auth::user();
        $userId = $user?->account_id ?? \Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Bạn cần đăng nhập.'], 401);
        }

        try {
            $result = DB::transaction(function () use ($userId, $data, $user) {

                // 1) Lấy thẻ thuộc user
                $card = DB::table('bank_accounts')
                    ->where('account_id', $userId)
                    ->where('account_number', $data['to_account_number'])
                    ->first();

                if (!$card) {
                    abort(422, 'Không tìm thấy thẻ nhận tiền thuộc tài khoản của bạn.');
                }

                $bankName = $card->bank_name ?? null;
                $bankShort = $card->bank_short ?? null;
                $bankCode = $card->bank_code ?? null;

                // 2) Lock account & lấy số dư + hạn mức
                $account = DB::table('accounts')
                    ->where('account_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (!$account)
                    abort(404, 'Không tìm thấy tài khoản.');

                $balance = (int) ($account->balance_cents ?? 0);
                $amount = (int) $data['amount'];
                $fee = (int) round($amount * 0.10); // 10%
                $currency = 'VND';

                // 3) Hạn mức tháng
                $now = now();
                $monthStart = $now->copy()->startOfMonth()->toDateTimeString();
                $monthEnd = $now->copy()->endOfMonth()->toDateTimeString();

                // Đã dùng trong tháng từ log (processing + completed)
                $usedThisMonth = (int) DB::table('withdrawal_logs')
                    ->where('account_id', $userId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('status', ['processing', 'completed'])
                    ->sum('amount_cents');

                // Hạn mức từ cột accounts.withdraw_month_limit_cents
                $configuredLimit = (int) ($account->withdraw_month_limit_cents ?? 0);

                // Nếu cột chưa có giá trị, đặt mặc định theo loại tài khoản
                // Giả định: F_BASIC = 5,000,000đ/tháng, F_PLUS = 50,000,000đ/tháng
                // Bạn có thể điều chỉnh cho khớp thực tế hệ thống
                if ($configuredLimit <= 0) {
                    // thử lấy type code nếu có (qua account_types)
                    $typeRow = DB::table('account_types')
                        ->where('account_type_id', $account->account_type_id)
                        ->first();

                    $code = strtoupper((string) ($typeRow->code ?? $typeRow->name ?? 'F_BASIC'));
                    $defaults = [
                        'F_BASIC' => 5_000_000 * 100,  // nếu đang lưu đơn vị cents VND (x100)
                        'F_PLUS' => 50_000_000 * 100,
                    ];
                    $monthlyLimit = $defaults[$code] ?? $defaults['F_BASIC'];
                } else {
                    $monthlyLimit = $configuredLimit;
                }

                // 4) Validate số dư & hạn mức tháng
                if ($amount > $balance) {
                    abort(422, 'Số dư không đủ để rút.');
                }

                if (($usedThisMonth + $amount) > $monthlyLimit) {
                    $remain = max(0, $monthlyLimit - $usedThisMonth);
                    $msg = 'Vượt hạn mức rút trong tháng. ';
                    $msg .= 'Đã dùng: ' . number_format($usedThisMonth / 100, 0, ',', '.') . 'đ / ';
                    $msg .= 'Hạn mức: ' . number_format($monthlyLimit / 100, 0, ',', '.') . 'đ. ';
                    $msg .= 'Phần còn lại có thể rút: ' . number_format($remain / 100, 0, ',', '.') . 'đ.';
                    abort(422, $msg);
                }

                // 5) Trừ số dư
                $newBalance = $balance - $amount;

                DB::table('accounts')
                    ->where('account_id', $userId)
                    ->update(['balance_cents' => $newBalance]);

                // 6) Ghi log rút tiền
                $withdrawId = DB::table('withdrawal_logs')->insertGetId([
                    'account_id' => $userId,
                    'bank_account_number' => $data['to_account_number'],
                    'bank_name' => $bankName,
                    'bank_short' => $bankShort,
                    'bank_code' => $bankCode,
                    'amount_cents' => $amount,
                    'fee_cents' => $fee, // phí 10%
                    'currency' => $currency,
                    'status' => 'processing',
                    'note' => 'Yêu cầu rút tiền (phí 10%)',
                    'meta' => json_encode([
                        'by' => $user->email ?? $user->name ?? 'user',
                        'ip' => request()->ip(),
                        'ua' => substr((string) request()->userAgent(), 0, 190),
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [$newBalance, $withdrawId, $fee, $monthlyLimit, $usedThisMonth];
            });

            [$newBalance, $withdrawId, $fee, $monthlyLimit, $usedThisMonth] = $result;

            return response()->json([
                'message' => "Tạo yêu cầu rút tiền thành công. Phí giao dịch: " . number_format($fee / 100, 0, ',', '.') . "đ",
                'withdraw_id' => $withdrawId,
                'new_balance_cents' => $newBalance,
                'fee_cents' => $fee,
                'month_used_cents' => $usedThisMonth,   // đã dùng (trước khi tính lệnh này)
                'month_limit_cents' => $monthlyLimit,    // hạn mức tháng
            ], 200);

        } catch (\Throwable $e) {
            $code = ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException)
                ? $e->getStatusCode() : 500;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }



}
