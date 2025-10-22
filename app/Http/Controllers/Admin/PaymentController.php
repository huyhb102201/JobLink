<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // Tối ưu: Chỉ load 100 payments gần nhất thay vì tất cả
        $payments = Payment::with([
                'account:account_id,email,avatar_url,name',
                'account.profile:profile_id,account_id,fullname',
                'plan:plan_id,name,tagline,price,duration_days'
            ])
            ->select('payment_id', 'account_id', 'plan_id', 'order_code', 'amount', 'status', 'description', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $paymentDetails = [];
        foreach ($payments as $payment) {
            $account = $payment->account;
            $profile = $account ? $account->profile : null;
            $plan = $payment->plan;

            $statusBadge = match ($payment->status) {
                'success' => '<span class="badge bg-success">Thành công</span>',
                'failed' => '<span class="badge bg-danger">Thất bại</span>',
                default => '<span class="badge bg-warning">Đang chờ</span>',
            };

            $paymentDetails[$payment->payment_id] = [
                'payment_id' => $payment->payment_id,
                'order_code' => $payment->order_code ?? 'N/A',
                'amount' => $payment->amount,
                'amount_formatted' => number_format($payment->amount ?? 0, 0, ',', '.'),
                'status' => $payment->status ?? 'pending',
                'status_badge' => $statusBadge,
                'created_at' => $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : 'N/A',
                'description' => $payment->description,
                'account' => [
                    'id' => $payment->account_id,
                    'fullname' => $profile->fullname ?? null,
                    'name' => $account->name ?? null,
                    'email' => $account->email ?? null,
                ],
                'plan' => $plan ? [
                    'name' => $plan->name ?? $plan->tagline,
                    'price_formatted' => number_format($plan->price ?? 0, 0, ',', '.'),
                    'duration_days' => $plan->duration_days,
                ] : null,
            ];
        }

        // Tối ưu hóa queries với cache - tăng thòi gian cache
        $today = Carbon::today();
        $cacheKey = 'admin_payment_stats_' . $today->format('Y-m-d');
        
        $stats = \Cache::remember($cacheKey, 1800, function() use ($today) {
            return [
                'totalRevenueToday' => Payment::where('status', 'success')->whereDate('created_at', $today)->sum('amount'),
                'totalOrdersToday' => Payment::whereDate('created_at', $today)->count(),
                'totalRevenue' => Payment::where('status', 'success')->sum('amount'),
                'totalSuccessTransactions' => Payment::where('status', 'success')->count(),
            ];
        });
        
        $membershipPlans = \Cache::remember('admin_membership_plans', 1800, function() {
            return MembershipPlan::select('plan_id', 'account_type_id', 'name', 'tagline', 'price', 'duration_days', 'discount_percent', 'is_popular', 'is_active', 'description')
                ->orderBy('price')
                ->get();
        });

        return view('admin.payments.payment', compact(
            'payments',
            'membershipPlans',
            'paymentDetails'
        ) + $stats);
    }

    public function show($id)
    {
        try {
            $payment = Payment::with([
                'account:account_id,email,avatar_url,name',
                'account.profile:profile_id,account_id,fullname',
                'plan:plan_id,name,tagline,price,duration_days'
            ])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy chi tiết payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return back()->with('error', 'Không tìm thấy giao dịch.');
        }
        $payment->delete();
        return back()->with('success', 'Đã xóa giao dịch thành công.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        if (!empty($ids)) {
            Payment::whereIn('payment_id', $ids)->delete();
            return back()->with('success', 'Đã xóa các giao dịch đã chọn thành công.');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất một giao dịch để xóa.');
    }

    public function export(Request $request)
    {
        try {
            // Load payments với relationships
            $payments = Payment::with(['account:account_id,email,name', 'account.profile:profile_id,account_id,fullname'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Tạo tên file với timestamp
            $fileName = 'payments_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

            return \Excel::download(new \App\Exports\PaymentExport($payments), $fileName);
        } catch (\Exception $e) {
            Log::error('Lỗi khi export payments: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xuất file Excel. Vui lòng thử lại.');
        }
    }

    // THÊM CHỨC NĂNG QUẢN LÝ MEMBERSHIP PLAN
    public function getMembershipPlans()
    {
        $plans = MembershipPlan::orderBy('price')->get();
        return response()->json($plans);
    }

    public function getMembershipPlan($id)
    {
        $plan = MembershipPlan::with('accountType:account_type_id,name')->findOrFail($id);
        return response()->json($plan);
    }

    public function storeMembershipPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Tìm sort_order lớn nhất và tăng lên 1
            $maxSortOrder = MembershipPlan::max('sort_order') ?? 0;
            
            // Sử dụng account_type_id mặc định = 1 (Free/Basic)
            $plan = MembershipPlan::create([
                'account_type_id' => 1,
                'name' => $request->name,
                'description' => $request->description,
                'duration_days' => $request->duration_days,
                'tagline' => $request->name, // Cũng lưu vào tagline để tương thích
                'price' => $request->price,
                'sort_order' => $maxSortOrder + 1,
                'features' => $request->features ? json_encode($request->features) : null,
                'discount_percent' => 0,
                'is_popular' => 0,
                'is_active' => $request->has('is_active') ? 1 : 1, // Default to active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thêm gói membership thành công!',
                'plan' => $plan
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi thêm gói membership: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateMembershipPlan(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'is_popular' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        try {
            $plan = MembershipPlan::find($id);
            if (!$plan) {
                return back()->with('error', 'Không tìm thấy gói membership.');
            }

            // Chỉ cho phép cập nhật 4 trường: price, discount_percent, is_popular, is_active
            $plan->update([
                'price' => $request->price,
                'discount_percent' => $request->discount_percent,
                'is_popular' => $request->has('is_popular') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ]);

            return back()->with('success', 'Cập nhật gói membership thành công!');
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật gói membership: ' . $e->getMessage());
            return back()->with('error', 'Đã có lỗi xảy ra. Vui lòng thử lại.');
        }
    }

    public function deleteMembershipPlan($id)
    {
        try {
            $plan = MembershipPlan::find($id);
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy gói membership.'
                ], 404);
            }

            // Kiểm tra xem có payment nào đang sử dụng plan này không
            $paymentCount = Payment::where('plan_id', $id)->count();
            if ($paymentCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa gói membership này vì có giao dịch đang sử dụng.'
                ], 400);
            }

            $plan->delete();
            
            // Clear cache
            \Cache::forget('admin_membership_plans');
            
            return response()->json([
                'success' => true,
                'message' => 'Xóa gói membership thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa gói membership: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}