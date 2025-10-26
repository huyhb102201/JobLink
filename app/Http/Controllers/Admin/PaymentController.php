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
use App\Services\AdminLogService;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // Load tất cả payments và group theo account
        $payments = Payment::with([
                'account:account_id,email,avatar_url,name',
                'account.profile:profile_id,account_id,fullname',
                'plan:plan_id,name,tagline,price,duration_days'
            ])
            ->select('payment_id', 'account_id', 'plan_id', 'order_code', 'amount', 'status', 'description', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group payments by name + email (to consolidate duplicate accounts)
        $groupedPayments = [];
        foreach ($payments as $payment) {
            $account = $payment->account;
            
            if (!$account) {
                continue; // Skip if account is missing
            }
            
            $profile = $account->profile;
            $accountName = $profile->fullname ?? $account->name ?? 'N/A';
            $accountEmail = $account->email ?? 'N/A';
            
            // Create unique key based on name and email
            $groupKey = $accountEmail . '|' . $accountName;
            
            if (!isset($groupedPayments[$groupKey])) {
                $groupedPayments[$groupKey] = [
                    'account_name' => $accountName,
                    'account_email' => $accountEmail,
                    'account_avatar' => $account->avatar_url ?? asset('images/man.jpg'),
                    'total_amount' => 0,
                    'payment_count' => 0,
                    'payments' => []
                ];
            }
            
            // Add payment to group
            $statusBadge = match ($payment->status) {
                'success', 'PAID' => '<span class="badge bg-success">Thành công</span>',
                'failed' => '<span class="badge bg-danger">Thất bại</span>',
                default => '<span class="badge bg-warning">Đang chờ</span>',
            };
            
            $plan = $payment->plan;
            
            $groupedPayments[$groupKey]['payments'][] = [
                'payment_id' => $payment->payment_id,
                'order_code' => $payment->order_code ?? 'N/A',
                'amount' => $payment->amount,
                'amount_formatted' => number_format($payment->amount ?? 0, 0, ',', '.'),
                'status' => $payment->status ?? 'pending',
                'status_badge' => $statusBadge,
                'created_at' => $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : 'N/A',
                'description' => $payment->description,
                'plan_name' => $plan ? ($plan->name ?? $plan->tagline) : 'N/A',
                'payment_method' => $payment->payment_method ?? 'Chưa xác định',
            ];
            
            // Chỉ tính tổng doanh thu cho giao dịch thành công
            if (in_array($payment->status, ['success', 'PAID'])) {
                $groupedPayments[$groupKey]['total_amount'] += $payment->amount ?? 0;
            }
            $groupedPayments[$groupKey]['payment_count']++;
        }
        
        // Convert to indexed array
        $groupedPayments = array_values($groupedPayments);

        // Lấy thống kê real-time (không cache để cập nhật ngay lập tức)
        $today = Carbon::today();
        $stats = [
            'totalRevenueToday' => Payment::whereIn('status', ['success', 'PAID'])
                ->whereNotNull('account_id')
                ->whereHas('account')
                ->whereDate('created_at', $today)
                ->sum('amount'),
            'totalOrdersToday' => Payment::whereDate('created_at', $today)->count(),
            'totalRevenue' => Payment::whereIn('status', ['success', 'PAID'])
                ->whereNotNull('account_id')
                ->whereHas('account')
                ->sum('amount'),
            'totalSuccessTransactions' => Payment::whereIn('status', ['success', 'PAID'])
                ->whereNotNull('account_id')
                ->whereHas('account')
                ->count(),
        ];
        
        $membershipPlans = MembershipPlan::with('accountType:account_type_id,name')
            ->select('plan_id', 'account_type_id', 'name', 'tagline', 'price', 'duration_days', 'discount_percent', 'is_popular', 'is_active', 'description')
            ->orderBy('price')
            ->get();
        
        $accountTypes = AccountType::select('account_type_id', 'name')->orderBy('name')->get();

        return view('admin.payments.payment', compact(
            'groupedPayments',
            'membershipPlans',
            'accountTypes'
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

        // Log before delete
        AdminLogService::logDelete(
            'Payment',
            $payment->payment_id,
            "Xóa giao dịch thanh toán: {$payment->order_code}",
            ['order_code' => $payment->order_code, 'amount' => $payment->amount]
        );

        $payment->delete();
        return back()->with('success', 'Đã xóa giao dịch thành công.');
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
            'account_type_id' => 'required|integer|exists:account_types,account_type_id',
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
            
            // Sử dụng account_type_id từ form
            $plan = MembershipPlan::create([
                'account_type_id' => $request->account_type_id,
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

            // Log admin action
            AdminLogService::logCreate(
                'MembershipPlan',
                $plan->plan_id,
                "Tạo gói membership mới: {$plan->name}",
                ['name' => $plan->name, 'price' => $plan->price]
            );

            return response()->json([
                'success' => true,
                'message' => 'Thêm gói membership thành công!',
                'plan' => $plan->load('accountType')
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
            'is_popular' => 'nullable|in:on,off,true,false,1,0',
            'is_active' => 'nullable|in:on,off,true,false,1,0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $plan = MembershipPlan::find($id);
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy gói membership.'
                ], 404);
            }

            // Convert checkbox values to boolean
            $isPopular = in_array($request->input('is_popular'), ['on', 'true', '1', 1, true]) ? 1 : 0;
            $isActive = in_array($request->input('is_active'), ['on', 'true', '1', 1, true]) ? 1 : 0;

            // Chỉ cho phép cập nhật 4 trường: price, discount_percent, is_popular, is_active
            $plan->update([
                'price' => $request->price,
                'discount_percent' => $request->discount_percent,
                'is_popular' => $isPopular,
                'is_active' => $isActive,
            ]);

            // Log admin action
            AdminLogService::logUpdate(
                'MembershipPlan',
                $plan->plan_id,
                "Cập nhật gói membership: {$plan->name}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật gói membership thành công!',
                'plan' => $plan
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật gói membership: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
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