<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobPaymentController extends Controller
{
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
}
