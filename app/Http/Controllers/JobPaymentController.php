<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class JobPaymentController extends Controller
{

     protected StripeClient $stripe;

    public function __construct()
    {
        parent::__construct(); // 👈 RẤT QUAN TRỌNG: chạy constructor của Controller (khởi tạo PayOS)
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }
    public function createPaymentLink(Request $request, $job_id)
    {
        // Lấy job & đảm bảo job thuộc về user hiện tại
        $job = Job::where('job_id', $job_id)
            ->where('account_id', Auth::id())  // tuỳ cột PK account của bạn
            ->firstOrFail();

        // Lấy amount từ budget của job (PayOS cần integer VND)
        // Giả sử budget là decimal(10,2) => ép về int VND
        $price = (int) round($job->total_budget ?? 0);

        if ($price <= 0) {
            return back()->with('error', 'Ngân sách (budget) của job không hợp lệ.');
        }

        // Sinh orderCode duy nhất
        $orderCode = (int) (time() . random_int(100, 999));

        // Lưu giao dịch vào job_payments
        JobPayment::create([
            'job_id' => $job->job_id,
            'orderCode' => $orderCode,
            'amount' => $job->total_budget,
            'description' => 'JOB_' . $job->job_id . ' THANH TOÁN',
            'status' => 'pending', // <-- mới
        ]);

        // Dữ liệu gửi sang PayOS
        $data = [
            "orderCode" => $orderCode,
            "amount" => $price,
            "description" => 'JOB_' . $job->job_id . ' THANH TOÁN',
            "returnUrl" => route('job-payments.success'), // route bạn tạo cho success
            "cancelUrl" => route('job-payments.cancel'),  // route bạn tạo cho cancel
        ];

        try {
            $response = $this->payOS->createPaymentLink($data);
            return redirect($response['checkoutUrl']);
        } catch (\Throwable $th) {
            // Gỡ lỗi nhanh
            return back()->with('error', 'PayOS error: ' . $th->getMessage());
        }
    }

    /**
     * PayOS redirect về khi thanh toán thành công
     * Tuỳ PayOS: bạn cần đọc đúng tham số (orderCode/status)
     */
    public function paymentSuccess(Request $request)
    {
        $orderCode = $request->input('orderCode');
        $status = $request->input('status'); // ví dụ: "PAID"

        $payment = JobPayment::where('orderCode', $orderCode)->first();

        if (!$payment) {
            return view('checkout.success')->with('error', 'Không tìm thấy giao dịch.');
        }

        // Nếu bạn đã thêm cột jobs.payment_status, có thể set về 'paid'
        // (hoặc dùng cờ khác để un-block xác nhận ứng viên)
        $job = Job::where('job_id', $payment->job_id)
            ->where('account_id', Auth::id())
            ->first();

        if ($job) {
            // Nếu có cột payment_status (enum: pending/paid)
            if (\Schema::hasColumn($job->getTable(), 'escrow_status')) {
                $job->escrow_status = 'funded';
                $job->save();
            }
        }

        // Có thể cập nhật lại description nếu muốn phản ánh đã thanh toán
        $payment->update([
            'status' => 'paid',
            'description' => 'JOB_' . $payment->job_id . ' THANH TOÁN - PAID',
        ]);


        return view('checkout.success', [
            'orderCode' => $payment->orderCode,
            'amount' => $payment->amount,
        ]);
    }

    /**
     * PayOS redirect về khi huỷ/thất bại
     */
    public function paymentCancel(Request $request)
    {
        $orderCode = $request->input('orderCode');

        $payment = JobPayment::where('orderCode', $orderCode)->first();

        if ($payment) {
            $payment->update([
                'status' => 'canceled', // hoặc 'failed'
                'description' => 'JOB_' . $payment->job_id . ' THANH TOÁN - CANCELED',
            ]);

        }

        return view('checkout.cancel', [
            'orderCode' => $orderCode,
            'amount' => $payment?->amount,
        ]);
    }

    /** ✅ B1: Tạo Stripe Checkout Session cho JOB (escrow) */
    
    public function createStripeSession(Request $request, $job_id)
    {
        // Lấy job thuộc về chủ job hiện tại
        $job = Job::where('job_id', $job_id)
            ->where('account_id', Auth::id())
            ->firstOrFail();

        // Lấy số tiền: dùng total_budget (giả sử VND)
        $priceVnd = (int) round($job->total_budget ?? 0);
        if ($priceVnd <= 0) {
            return back()->with('error', 'Ngân sách (budget) của job không hợp lệ.');
        }

        // orderCode duy nhất
        $orderCode = (string) (time() . random_int(100, 999));

        // Tạo bản ghi job_payments (pending)
        $payment = JobPayment::create([
            'job_id' => $job->job_id,
            'orderCode' => $orderCode,
            'amount' => $job->total_budget,
            'description' => 'JOB_' . $job->job_id . ' THANH TOÁN (Stripe)',
            'status' => 'pending',
        ]);

        // Quy đổi amount/currency hợp Stripe
        [$amount, $currency] = $this->amountForStripe($priceVnd);

        try {
            $session = $this->stripe->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => 'Escrow for Job #' . $job->job_id,
                                'description' => 'Thanh toán cọc JOB_' . $job->job_id,
                            ],
                            'unit_amount' => $amount,
                        ],
                        'quantity' => 1,
                    ]
                ],
                'metadata' => [
                    'order_code' => $orderCode,
                    'job_id' => (string) $job->job_id,
                    'account_id' => (string) Auth::id(),
                    'amount_vnd' => (string) $priceVnd,
                ],
                'success_url' => route('job-payments.stripe.success') . '?orderCode=' . $orderCode . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('job-payments.stripe.cancel') . '?orderCode=' . $orderCode,
            ]);

            return redirect()->away($session->url);

        } catch (\Throwable $e) {
            Log::error('Stripe JOB createSession error', ['msg' => $e->getMessage()]);
            // Nếu lỗi, trả lại pending như cũ
            return back()->withErrors(['msg' => 'Không tạo được phiên Stripe: ' . $e->getMessage()]);
        }
    }

    /** ✅ B2: Success page — xác minh và CHỐT đơn (không cần webhook vẫn chạy) */
    public function stripeSuccess(Request $request)
    {
        $orderCode = $request->query('orderCode');
        $sessionId = $request->query('session_id');

        if (!$orderCode || !$sessionId)
            abort(400, 'Thiếu tham số.');

        $payment = JobPayment::where('orderCode', $orderCode)->first();
        if (!$payment)
            abort(404, 'Không tìm thấy giao dịch.');

        // Nếu đã paid (đã chốt trước đó) thì hiển thị luôn
        if ($payment->status === 'paid') {
            return view('checkout.success', [
                'orderCode' => $payment->orderCode,
                'amount' => $payment->amount,
                'status' => 'paid',
            ]);
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            $paidOk = ($session->payment_status ?? '') === 'paid';
            $orderOk = ($session->metadata->order_code ?? null) === (string) $orderCode;

            // Currency khớp (đủ chặt)
            $currency = strtolower($session->currency ?? '');
            [, $expectedCurrency] = $this->amountForStripe((int) round($payment->amount));
            $currencyOk = $currency === $expectedCurrency;

            if ($paidOk && $orderOk && $currencyOk) {
                DB::transaction(function () use ($payment) {
                    // Lock theo id
                    $p = JobPayment::where('id', $payment->id)->lockForUpdate()->first();
                    if ($p && $p->status !== 'paid') {
                        $p->update([
                            'status' => 'paid',
                            'description' => 'JOB_' . $p->job_id . ' THANH TOÁN - PAID (Stripe success)',
                        ]);

                        // Update job escrow_status = funded (nếu có cột)
                        $job = Job::where('job_id', $p->job_id)->first();
                        if ($job && \Schema::hasColumn($job->getTable(), 'escrow_status')) {
                            $job->escrow_status = 'funded';
                            $job->save();
                        }
                    }
                });

                $payment->refresh();
            } else {
                Log::warning('Stripe JOB success verify failed', compact('paidOk', 'orderOk', 'currencyOk', 'currency', 'sessionId'));
            }
        } catch (\Throwable $e) {
            Log::warning('Stripe JOB success() retrieve failed: ' . $e->getMessage(), compact('orderCode', 'sessionId'));
        }

        return view('checkout.success', [
            'orderCode' => $payment->orderCode,
            'amount' => $payment->amount,
            'status' => $payment->status, // kỳ vọng 'paid'
        ]);
    }

    /** ✅ B3: Cancel page — user hủy */
    public function stripeCancel(Request $request)
    {
        $orderCode = $request->query('orderCode');
        $payment = JobPayment::where('orderCode', $orderCode)->first();

        if ($payment && $payment->status !== 'paid') {
            $payment->update([
                'status' => 'canceled',
                'description' => 'JOB_' . $payment->job_id . ' THANH TOÁN - CANCELED (Stripe)',
            ]);
        }

        return view('checkout.cancel', [
            'orderCode' => $orderCode,
            'amount' => $payment?->amount,
            'status' => $payment?->status ?? 'canceled',
        ]);
    }

    /** ✅ B4: Webhook (khuyên dùng production) */
    public function stripeWebhook(Request $request): Response
    {
        $sig = $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($request->getContent(), $sig, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe JOB webhook verify failed: ' . $e->getMessage());
            return new Response('Invalid', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderCode = $session->metadata->order_code ?? null;

            if ($orderCode) {
                DB::transaction(function () use ($orderCode) {
                    $p = JobPayment::where('orderCode', $orderCode)->lockForUpdate()->first();
                    if (!$p || $p->status === 'paid')
                        return;

                    $p->update([
                        'status' => 'paid',
                        'description' => 'JOB_' . $p->job_id . ' THANH TOÁN - PAID (Stripe webhook)',
                    ]);

                    $job = Job::where('job_id', $p->job_id)->first();
                    if ($job && \Schema::hasColumn($job->getTable(), 'escrow_status')) {
                        $job->escrow_status = 'funded';
                        $job->save();
                    }
                });
            }
        }

        return new Response('ok', 200);
    }

    /** Helper: VND/USD cho Stripe (VND zero-decimal) */
    private function amountForStripe(int $priceVnd): array
    {
        $MIN_VND = 14000;          // ≈ $0.5
        $RATE_VND_PER_USD = 27355; // có thể đưa vào config

        if ($priceVnd >= $MIN_VND) {
            return [$priceVnd, 'vnd']; // VND là zero-decimal trên Stripe
        }

        $cents = (int) round(($priceVnd / $RATE_VND_PER_USD) * 100);
        if ($cents < 50)
            $cents = 50; // tối thiểu $0.50
        return [$cents, 'usd'];
    }

}
