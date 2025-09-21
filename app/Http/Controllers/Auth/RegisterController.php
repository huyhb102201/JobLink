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

        // 1) Validate cơ bản (chưa unique username ở đây để mình chủ động gợi ý trước)
        $baseValidated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:accounts,email'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:CLIENT,F_BASIC'],
        ], [
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'username.regex' => 'Username chỉ gồm chữ, số, dấu _ hoặc .',
        ]);

        // 2) Chuẩn hoá username & gợi ý nếu trùng
        $rawUsername = $baseValidated['username'];
        $sanitized = $this->sanitizeUsername($rawUsername);

        // Nếu người dùng gõ ký tự hợp lệ nhưng sau sanitize rỗng -> fallback từ email hoặc name
        if ($sanitized === '') {
            $local = explode('@', $baseValidated['email'])[0] ?? 'user';
            $sanitized = $this->sanitizeUsername($local) ?: 'user';
        }

        // Nếu đã tồn tại, sinh gợi ý
        $finalUsername = $this->suggestAvailableUsername($sanitized);

        // Nếu gợi ý khác với cái user gửi (tức là bị trùng), trả về form với gợi ý
        if ($finalUsername !== $sanitized) {
            // Nhét gợi ý vào old input để auto-fill ô username
            return back()
                ->withErrors(['username' => "Username đã tồn tại. Gợi ý: @$finalUsername"])
                ->withInput(array_merge($request->all(), ['username' => $finalUsername]));
        }

        // 3) Map role -> account_type_id
        $accountTypeId = AccountType::where('code', $role)->value('account_type_id')
            ?: AccountType::where('code', 'GUEST')->value('account_type_id');

        // 4) Tạo account + profile trong transaction
        $account = DB::transaction(function () use ($baseValidated, $accountTypeId, $finalUsername) {
            $acc = Account::create([
                'name' => $baseValidated['name'],
                'email' => $baseValidated['email'],
                'password' => Hash::make($baseValidated['password']),
                'account_type_id' => $accountTypeId,
                'provider' => 'register',
                'status' => 1,
            ]);

            $pk = $acc->getKey(); // account_id

            Profile::create([
                'account_id' => $pk,
                'fullname' => $baseValidated['name'],
                'email' => $baseValidated['email'],
                'username' => $finalUsername, // <- LƯU username đã đảm bảo unique
            ]);

            return $acc;
        });

        // 5) Đăng nhập & xác minh email
        Auth::login($account);
        session()->forget('register_role');
        Auth::login($account);
        session()->forget('register_role');

        try {
            $account->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            \Log::error('Verify email failed: ' . $e->getMessage());
            // có thể flash thông báo nhẹ nếu muốn
        }

        return redirect()->route('verification.notice');


    }
    /**
     * Chuẩn hoá username: bỏ dấu, về lowercase, chỉ giữ a-z0-9_. và cắt 50 ký tự.
     */
    private function sanitizeUsername(string $u): string
    {
        $u = \Illuminate\Support\Str::ascii($u);
        $u = strtolower($u);
        $u = preg_replace('/[^a-z0-9_.]+/', '', $u) ?? '';
        $u = trim($u, '._'); // bỏ ký tự chấm/gạch dưới ở đầu/cuối
        return mb_substr($u, 0, 50);
    }

    /**
     * Nếu $base đã tồn tại trong profiles.username thì thêm hậu tố số: name, name1, name2, ...
     * Trả về username khả dụng đầu tiên.
     */
    private function suggestAvailableUsername(string $base): string
    {
        // Nếu base rỗng thì dùng 'user'
        $base = $base !== '' ? $base : 'user';

        // cắt còn 30 để dành chỗ cho hậu tố số
        $baseShort = mb_substr($base, 0, 30);

        $exists = DB::table('profiles')->where('username', $baseShort)->exists();
        if (!$exists)
            return $baseShort;

        for ($i = 1; $i <= 9999; $i++) {
            $candidate = $baseShort;
            // cắt tiếp nếu cần để không vượt quá 50 ký tự khi thêm số
            $candidate = mb_substr($candidate, 0, 50 - strlen((string) $i)) . $i;
            $exists = DB::table('profiles')->where('username', $candidate)->exists();
            if (!$exists)
                return $candidate;
        }

        // fallback bất khả kháng
        return $baseShort . \Illuminate\Support\Str::random(3);
    }


}
