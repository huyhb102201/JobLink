<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\MembershipPlan;

class UpgradeController extends Controller
{
    public function show()
    {
        // Account hiện tại (đọc fresh + load type)
        $account = Account::with('type')->find(auth()->id());
        $currentTypeId = $account?->account_type_id;           // ví dụ: 1
        $currentTypeCode = $account?->type?->code ?? 'F_BASIC'; // ví dụ: F_BASIC

        // Lấy tất cả plan + join account_types (để có name/code)
        $plans = MembershipPlan::with('accountType')
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
