<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutController extends Controller
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * B1: Tạo Checkout Session và redirect sang Stripe
     * Input: plan_id
     */
    public function createCheckout(Request $request)
    {
        $request->validate(['plan_id' => 'required|integer']);

        $plan = MembershipPlan::findOrFail($request->integer('plan_id'));
        $account = Account::with('profile')->findOrFail(auth()->id());

        // Giá sau giảm (VND)
        $priceVnd = (int) round(
            (float) $plan->price * (1 - max(0, (int) $plan->discount_percent) / 100)
        );

        // order_code: dùng STRING để tránh overflow nếu DB đang là INT
        $orderCode = (string) (time() . random_int(100, 999));

        // Tạo bản ghi payment (pending)
        $payment = Payment::create([
            'account_id' => $account->account_id,
            'plan_id' => $plan->plan_id,
            'order_code' => $orderCode,
            'amount' => $priceVnd,
            'status' => 'pending',
            'description' => 'Khởi tạo thanh toán Stripe cho gói #' . $plan->plan_id,
        ]);

        // Quy đổi amount/currency theo Stripe
        [$amount, $currency] = $this->amountForStripe($priceVnd);
        $account = Account::with(['type', 'profile'])->find(auth()->id());
        $username = $account->profile?->username ?? $account->profile?->full_name ?? 'User';
        $productName = 'Membership ' . ($plan->accountType->name ?? 'Plan');

        // thêm username vào sau
        $productName .= ' - ' . $username;
        try {
            $session = $this->stripe->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => $productName, // <--- ở đây Stripe sẽ hiển thị "Membership Plan - username"
                                'description' => 'Thanh toán gói thành viên dành cho ' . $username,
                            ],
                            'unit_amount' => $amount,
                        ],
                        'quantity' => 1,
                    ]
                ],
                'metadata' => [
                    'order_code' => $orderCode,
                    'plan_id' => (string) $plan->plan_id,
                    'account_id' => (string) $account->account_id,
                    'username' => $username, // lưu thêm để truy vết
                    'amount_vnd' => (string) $priceVnd,
                ],
                'success_url' => route('stripe.success') . '?orderCode=' . $orderCode . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.cancel') . '?orderCode=' . $orderCode,
            ]);

            if ($request->expectsJson()) {
                return response()->json(['url' => $session->url], 200);
            }
            return redirect()->away($session->url);

        } catch (\Throwable $e) {
            Log::error('Stripe createCheckout error', ['msg' => $e->getMessage()]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Không tạo được phiên Stripe: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['msg' => 'Không tạo được phiên Stripe: ' . $e->getMessage()]);
        }
    }

    /**
     * B2: Trang success (chốt đơn nếu paid).
     * Không phụ thuộc webhook; vẫn tương thích nếu webhook đã chốt trước đó.
     */
    public function success(Request $request)
    {
        $orderCode = $request->query('orderCode');
        $sessionId = $request->query('session_id');

        if (!$orderCode || !$sessionId) {
            abort(400, 'Thiếu tham số xác minh thanh toán.');
        }

        /** @var \App\Models\Payment|null $payment */
        $payment = Payment::where('order_code', $orderCode)->first();
        if (!$payment) {
            abort(404, 'Không tìm thấy đơn thanh toán.');
        }

        // Nếu đã success (do webhook / lần trước) thì hiển thị luôn
        if ($payment->status === 'success') {
            return view('checkout.success', [
                'orderCode' => $orderCode,
                'amount' => $payment->amount,
                'status' => 'success',
                'sessionId' => $sessionId,
            ]);
        }

        try {
            // Lấy lại phiên từ Stripe
            $session = $this->stripe->checkout->sessions->retrieve(
                $sessionId,
                ['expand' => ['payment_intent']]
            );

            // Điều kiện đủ để chốt đơn (vừa đủ chặt)
            $paidOk = ($session->payment_status ?? '') === 'paid';
            $orderOk = ($session->metadata->order_code ?? null) === (string) $orderCode;

            // (Tùy chọn) kiểm tra thêm cho chắc
            $acctOk = ($session->metadata->account_id ?? null) === (string) $payment->account_id;
            $planOk = ($session->metadata->plan_id ?? null) === (string) $payment->plan_id;

            // Không ép so khớp amount kỹ (tránh rớt do rounding/thuế); chỉ check currency khớp
            $currency = strtolower($session->currency ?? '');
            [, $expectedCurrency] = $this->amountForStripe((int) $payment->amount);
            $currencyOk = $currency === $expectedCurrency;

            if ($paidOk && $orderOk && $currencyOk /* && $acctOk && $planOk */) {
                DB::transaction(function () use ($orderCode) {
                    // Lock theo order_code (không phụ thuộc tên PK)
                    /** @var \App\Models\Payment|null $p */
                    $p = Payment::where('order_code', $orderCode)->lockForUpdate()->first();
                    if ($p && $p->status !== 'success') {
                        $p->update([
                            'status' => 'success',
                            'description' => 'Stripe: thanh toán thành công (verified tại success)',
                        ]);

                        // Nâng cấp account_type theo plan
                        if ($plan = MembershipPlan::find($p->plan_id)) {
                            Account::where('account_id', $p->account_id)
                                ->update(['account_type_id' => $plan->account_type_id]);
                        }
                    }
                });

                $payment->refresh();
            } else {
                Log::warning('Stripe success() verification not passed', [
                    'paidOk' => $paidOk,
                    'orderOk' => $orderOk,
                    'acctOk' => $acctOk,
                    'planOk' => $planOk,
                    'currencyOk' => $currencyOk,
                    'currency' => $currency,
                    'sessionId' => $sessionId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Stripe success() fallback failed: ' . $e->getMessage(), [
                'orderCode' => $orderCode,
                'sessionId' => $sessionId,
            ]);
        }

        return view('checkout.success', [
            'orderCode' => $orderCode,
            'amount' => $payment->amount,
            'status' => $payment->status, // kỳ vọng 'success' sau update
            'sessionId' => $sessionId,
        ]);
    }

    /**
     * B3: Trang cancel (nếu user hủy). Không đổi trạng thái nếu webhook đã success.
     */
    public function cancel(Request $request)
    {
        $orderCode = $request->query('orderCode');
        $payment = Payment::where('order_code', $orderCode)->first();

        if ($payment && $payment->status !== 'success') {
            $payment->update([
                'status' => 'failed',
                'description' => 'Stripe: người dùng hủy thanh toán',
            ]);
        }

        return view('checkout.cancel', [
            'orderCode' => $orderCode,
            'amount' => $payment?->amount,
            'status' => $payment?->status ?? 'failed',
        ]);
    }

    /**
     * B4: Webhook Stripe — CHỐT trạng thái (optional, khuyên dùng ở production)
     * Nhớ set STRIPE_WEBHOOK_SECRET trong .env
     */
    public function webhook(Request $request): Response
    {
        $sig = $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($request->getContent(), $sig, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook verify failed: ' . $e->getMessage());
            return new Response('Invalid', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderCode = $session->metadata->order_code ?? null;

            if ($orderCode) {
                DB::transaction(function () use ($orderCode) {
                    $payment = Payment::where('order_code', $orderCode)->lockForUpdate()->first();
                    if (!$payment || $payment->status === 'success')
                        return;

                    $payment->update([
                        'status' => 'success',
                        'description' => 'Stripe: thanh toán thành công gói #' . $payment->plan_id,
                    ]);

                    if ($plan = MembershipPlan::find($payment->plan_id)) {
                        Account::where('account_id', $payment->account_id)
                            ->update(['account_type_id' => $plan->account_type_id]);
                    }
                });
            }
        }

        return new Response('ok', 200);
    }

    /**
     * Helper: Quy đổi VND -> (amount, currency) hợp lệ cho Stripe.
     * - Nếu VND >= ~14,000đ: dùng VND.
     * - Nếu nhỏ hơn: đổi sang USD cents, tối thiểu 50 cents.
     */
    private function amountForStripe(int $priceVnd): array
    {
        $MIN_VND = 14000;          // ~ $0.50
        $RATE_VND_PER_USD = 27355; // có thể đưa vào config

        if ($priceVnd >= $MIN_VND) {
            // Stripe chấp nhận VND dạng số nguyên
            return [$priceVnd, 'vnd'];
        }

        $cents = (int) round(($priceVnd / $RATE_VND_PER_USD) * 100);
        if ($cents < 50)
            $cents = 50; // tối thiểu 0.50 USD
        return [$cents, 'usd'];
    }
}
