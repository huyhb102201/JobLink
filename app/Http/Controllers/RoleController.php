<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    // Hiển thị trang chọn role
    public function show()
    {
        $user = Auth::user();

        // Nếu đã có role khác Guest (5) thì về home
        if ((int)($user->account_type_id ?? 5) !== 5) {
            return redirect()->route('home');
        }

        // Lấy account_type_id theo code, không hardcode số
        $types = DB::table('account_types')
            ->whereIn('code', ['CLIENT', 'F_BASIC'])
            ->pluck('account_type_id', 'code'); // ['CLIENT' => 3, 'F_BASIC' => 1] ... ví dụ

        return view('auth.select-role', [
            'clientTypeId'     => $types['CLIENT']  ?? null,
            'freelancerTypeId' => $types['F_BASIC'] ?? null,
        ]);
    }

    // Lưu role
    public function store(Request $request)
    {
        // Nhận trực tiếp account_type_id (an toàn hơn: check tồn tại trong DB)
        $data = $request->validate([
            'account_type_id' => 'required|integer|exists:account_types,account_type_id',
        ]);

        $user = Auth::user();
        $user->account_type_id = $data['account_type_id'];
        $user->save();

        // RoleController@store (chỉ sửa dòng redirect)
    return redirect()->route('onb.name.show')->with('status','Chọn vai trò thành công!');
    }
}
