<?php

namespace App\Http\Controllers;
use App\Models\AccountType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\Payment;

class CheckoutController extends Controller
{
    public function createPaymentLink(Request $request)
    {
        $plan = MembershipPlan::find($request->input('plan_id'));
        $account_type_id = $plan->account_type_id;
        $account = Account::with(['type', 'profile'])->find(auth()->id());
        $username = $account->profile?->username;
        $accountType = AccountType::with(['membership'])->find($account_type_id);
        $account_code = $accountType->code;
        $username = $account->profile?->username;
        $price = $plan->price;
        $discount = $plan->discount_percent;
        if ($discount > 0) {
            $price = ($price * ((100 - $discount) / 100));
        }
        $price = (int) $price;
        $YOUR_DOMAIN = $request->getSchemeAndHttpHost();
        $orderCode = (int) (time() . random_int(100, 999));

        Payment::create([
            'account_id' => $account->account_id,
            'plan_id' => $plan->plan_id,
            'order_code' => $orderCode,
            'amount' => $price,
            'status' => 'pending',
            'description' => 'Khởi tạo thanh toán gói #' . $plan->plan_id,
        ]);

        $data = [
            "orderCode" => $orderCode,
            "amount" => $price,
            "description" => $username . " " . $account_code,
            "returnUrl" => route('payment.success'),
            "cancelUrl" => route('payment.cancel'),
        ];



        error_log($data['orderCode']);

        try {
            $response = $this->payOS->createPaymentLink($data);
            dd($response);
        } catch (\Throwable $th) {
            dd('PayOS error', $th->getMessage(), $th->getTraceAsString(), $data);
        }

    }
    public function paymentSuccess(Request $request)
    {
        // Lấy dữ liệu từ PayOS redirect (bạn cần check chính xác key trong docs PayOS)
        $orderCode = $request->input('orderCode');
        $status = $request->input('status'); // "PAID"...

        $payment = Payment::where('order_code', $orderCode)->first();

        if ($payment) {
            // Cập nhật trạng thái payment
            $payment->update([
                'status' => $status === 'PAID' ? 'success' : ($status ?? 'success'),
                'description' => 'Thanh toán thành công gói #' . $payment->plan_id,
            ]);

            $planId = $payment->plan_id;
            $amount = $payment->amount;

            // Update account_type_id cho account
            $plan = MembershipPlan::find($planId);
            if ($plan) {
                Account::where('account_id', $payment->account_id)
                    ->update(['account_type_id' => $plan->account_type_id]);
            }

            // Trả về view thông báo
            return view('checkout.success', compact('orderCode', 'amount'));
        }
    }


    public function paymentCancel(Request $request)
    {
        $orderCode = $request->input('orderCode');

        $payment = Payment::where('order_code', $orderCode)->first();

        if (!$payment) {
            return view('checkout.cancel')->with('error', 'Không tìm thấy giao dịch.');
        }

        // Nếu chưa success thì coi cancel = failed
        if ($payment->status !== 'success') {
            $payment->update([
                'status' => 'failed',
                'description' => 'Thanh toán thất bại #' . $payment->plan_id,
            ]);
        }

        return view('checkout.cancel', [
            'orderCode' => $orderCode,
            'amount' => $payment->amount,
            'status' => $payment->status,
        ]);
    }


}
