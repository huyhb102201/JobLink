<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BillingController extends Controller
{
    /**
     * Che số tài khoản theo format 12********12
     */
    private function maskAccount(?string $number): string
    {
        if (!$number) return '';

        // chỉ giữ chữ số
        $digits = preg_replace('/\D+/', '', (string)$number);
        $len    = strlen($digits);

        if ($len <= 4) return $digits; // quá ngắn thì khỏi che

        $first = substr($digits, 0, 2);
        $last  = substr($digits, -2);

        return $first . str_repeat('*', max(0, $len - 4)) . $last;
    }

    /**
     * GET /settings/billing
     */
    public function index()
    {
        $userId = Auth::id();

        // Lấy thẻ theo user, sort thẻ mặc định lên đầu
        $cards = BankAccount::where('account_id', $userId)
            ->orderByDesc('is_default')
            ->get()
            ->map(function (BankAccount $c) {
                $masked = $this->maskAccount($c->account_number);
                // Trả về dạng array để Blade của bạn dùng ['key']
                return [
                    'id'              => $c->id,
                    'bank_name'       => $c->bank_name,
                    'bank'            => $c->bank_name,        // để tương thích với {{ $card['bank'] ?? $card['bank_name'] }}
                    'account_holder'  => $c->account_holder,
                    'account_number'  => $c->account_number,
                    'card_number'     => $c->account_number,   // để tương thích với {{ $card['card_number'] }}
                    'masked'          => $masked,
                    'masked_account'  => $masked,
                    'is_default'      => (bool) $c->is_default,
                ];
            });

        // Demo transactions
        $transactions = [
            ['id' => 1, 'type' => 'Nạp tiền',                 'amount' => 500000, 'status' => 'Thành công',  'date' => '2025-10-12'],
            ['id' => 2, 'type' => 'Thanh toán gói Premium',   'amount' => 299000, 'status' => 'Đang xử lý', 'date' => '2025-10-14'],
        ];

        return view('settings.billing', compact('cards', 'transactions'));
    }

    /**
     * POST /settings/billing/add-card
     * route name: settings.billing.addCard
     */
    public function addCard(Request $request)
{
    $validated = $request->validate([
        'bank_name'   => 'required|string|max:255',
        'card_number' => 'required|string|max:50',
        // optional
        'bank_code'   => 'nullable|string|max:20',
        'bank_short'  => 'nullable|string|max:100',
        'bank_bin'    => 'nullable|string|max:32',
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
                'errors'  => ['card_number' => ['Bạn đã liên kết thẻ này rồi']],
            ], 422)
            : back()->with('error', 'Số tài khoản này đã được bạn thêm trước đó.');
    }

    // Hợp nhất hiển thị: "Tên đầy đủ (Short / CODE)"
    $display = $validated['bank_name'];                 // client đã gửi label đầy đủ
    $short   = (string) $request->input('bank_short', '');
    $code    = (string) $request->input('bank_code', '');
    if ($display && !str_contains($display, '(')) {
        // nếu client chưa gửi label đầy đủ, tự ghép
        $parts = array_filter([$short ?: null, $code ?: null]);
        if (!empty($parts)) {
            $display = $display . ' (' . implode(' / ', $parts) . ')';
        }
    }

    $card = \App\Models\BankAccount::create([
        'account_id'     => $userId,
        'bank_name'      => $display,                     // <-- lưu label đầy đủ
        'account_number' => $validated['card_number'],
        'account_holder' => $user->fullname ?? $user->name ?? 'Người dùng',
        'is_default'     => false,
        // Nếu bạn có cột riêng, có thể lưu thêm bên dưới:
        // 'bank_code'   => $code,
        // 'bank_bin'    => $request->input('bank_bin'),
        // 'bank_short'  => $short,
    ]);

    $masked = $this->maskAccount($card->account_number);

    return $request->ajax() || $request->wantsJson()
        ? response()->json([
            'message' => 'Đã thêm thẻ ngân hàng thành công!',
            'card'    => [
                'id'             => $card->id,
                'bank_name'      => $card->bank_name,
                'bank'           => $card->bank_name,
                'account_number' => $card->account_number,
                'card_number'    => $card->account_number,
                'masked'         => $masked,
                'masked_account' => $masked,
                'is_default'     => (bool) $card->is_default,
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
    $user   = \Auth::user();
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
            $data = Cache::remember('momo_bankcodes_v2', 60*60*24, function () {
                $res = Http::timeout(10)->get('https://test-payment.momo.vn/v2/gateway/api/bankcodes');
                if (!$res->ok()) {
                    throw new \Exception('MoMo trả về lỗi: '.$res->status());
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
}
