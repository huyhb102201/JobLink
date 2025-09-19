<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRole()
    {
        return view('auth.register-role'); // view chọn role (bước 3)
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', 'in:CLIENT,F_BASIC'],
        ]);

        // Lưu vào session để dùng cho form register & social login
        session(['register_role' => $data['role']]);

        // Cho phép chuyển thẳng đến form đăng ký
        return redirect()->route('register.show');
    }

    public function showForm(Request $request)
    {
        // Lấy role từ session (nếu có) để hiển thị trên form
        $role = session('register_role'); // CLIENT/FREELANCER hoặc null
        return view('auth.register', ['role' => $role]);
    }

    public function register(Request $request)
    {
        // Lấy role: ưu tiên từ POST hidden, fallback từ session
        $role = $request->input('role', session('register_role'));

        // 0) Bắt buộc đã chọn role trước
        if (!in_array($role, ['CLIENT', 'F_BASIC'])) {
            return redirect()->route('register.role.show')->withErrors([
                'role' => 'Hãy chọn vai trò trước khi đăng ký.',
            ]);
        }

        // 1) Validate dữ liệu
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:accounts,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:CLIENT,F_BASIC'], // xác nhận lại role
        ], [
            'email.unique' => 'Email đã tồn tại trong hệ thống.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        // 2) Map role -> account_type_id theo code trong bảng account_types
        // code: CLIENT/FREELANCER (bạn có thể đổi theo hệ thống của bạn)
        $accountTypeId = AccountType::where('code', $role)->value('account_type_id');

        if (!$accountTypeId) {
            // fallback (tùy bạn): dùng GUEST nếu không tìm thấy
            $accountTypeId = AccountType::where('code', 'GUEST')->value('account_type_id');
        }

        // 3) Tạo account + profile trong transaction
        $account = DB::transaction(function () use ($data, $accountTypeId) {
            $acc = Account::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'account_type_id' => $accountTypeId,
                'provider' => 'register',
                'status' => 1,
            ]);

            // LẤY KHÓA CHÍNH ĐÚNG:
            $pk = $acc->getKey(); // tương đương $acc->account_id do đã set $primaryKey

            Profile::create([
                'account_id' => $pk,
                'fullname' => $data['name'],
                'email' => $data['email'],
            ]);

            return $acc;
        });


        // 4) Đăng nhập luôn & redirect
        Auth::login($account);
        session()->forget('register_role');
        // Gửi mail xác minh
        $account->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
