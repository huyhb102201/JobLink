<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\MembershipPlan;

class UpgradeController extends Controller
{
    public function show()
{
    // Lấy user hiện tại + quan hệ type (an toàn nhất là dùng auth()->user())
    $account = auth()->user()->loadMissing('type');

    $currentTypeId   = $account?->account_type_id;
    $currentTypeCode = $account?->type?->code ?? 'F_BASIC';

    // Quy ước hiển thị plan theo role hiện tại
    $visibleCodes = match ($currentTypeCode) {
        // Nếu đang là CLIENT → chỉ thấy gói Business (BUSS)
        'CLIENT' => ['BUSS'],

        // Ví dụ cho Agency (tuỳ bạn có plan nào)
        'AGENCY' => ['AGENCY'],

        // Mặc định: freelancer
        default => ['F_BASIC', 'F_PLUS', 'F_PRO'],
    };

    $plans = \App\Models\MembershipPlan::with('accountType')
        // nếu có cột status thì mở dòng sau
        //->where('status', 1)
        ->whereHas('accountType', fn($q) => $q->whereIn('code', $visibleCodes))
        ->orderBy('sort_order')
        ->get();

    return view('settings.upgrade', compact('plans', 'currentTypeId', 'currentTypeCode'));
}


    public function upgrade(\Illuminate\Http\Request $request)
    {
        $request->validate(['plan' => 'required|integer']); // plan = plan_id

        $plan = MembershipPlan::with('accountType')->findOrFail($request->integer('plan'));

        // Cập nhật account sang loại của plan đã chọn
        $account = \App\Models\Account::findOrFail(auth()->id());
        $account->update(['account_type_id' => $plan->account_type_id]);

        return back()->with('ok', 'Đã chuyển sang gói ' . ($plan->accountType->name ?? ''));
    }
}
