<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->loadMissing('type'); // cần có quan hệ type() trong Account model

            // Nếu là admin → đưa vào trang admin (ví dụ: danh sách tài khoản)
            if ($user->type?->code === 'ADMIN') {
                // Nếu trước đó user bị chặn ở /admin, intended sẽ trả về admin dashboard
                return redirect()->intended(route('admin.accounts.index'));
            }

            // User thường → về trang chủ (hoặc intended)
            return redirect()->intended('/');
        }

        return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
            ->onlyInput('email');
    }

}
