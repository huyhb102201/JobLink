<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    // --- GOOGLE ---
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request)
    {
        $g = Socialite::driver('google')->stateless()->user(); // stateless an toàn hơn khi có proxy

        $payload = [
            'provider' => 'google',
            'provider_id' => $g->getId(),                     // sub
            'email' => $g->getEmail(),                  // thường có
            'name' => $g->getName() ?: 'User ' . Str::random(6),
            'avatar_url' => $g->getAvatar(),
        ];

        $account = $this->upsertAccountFromOAuth($payload, $request);

        Auth::login($account, true);
        if ((int) $account->account_type_id === 5) {
            return redirect()->route('role.select');
        }                           // session login
        return redirect()->intended('/');                      // về trang chủ
    }

    // --- FACEBOOK ---
    public function facebookRedirect()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function facebookCallback(Request $request)
    {
        $f = Socialite::driver('facebook')->stateless()->user();

        $payload = [
            'provider' => 'facebook',
            'provider_id' => $f->getId(),
            'email' => $f->getEmail(),                  // có thể NULL
            'name' => $f->getName() ?: 'User ' . Str::random(6),
            'avatar_url' => $f->getAvatar(),
        ];

        $account = $this->upsertAccountFromOAuth($payload, $request);

        Auth::login($account, true);
        return redirect()->intended('/');
    }

    // --- Đăng xuất ---
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // --- Helper: tạo/cập nhật bản ghi accounts ---
    protected function upsertAccountFromOAuth(array $p, Request $request): Account
    {
        // 1) Ưu tiên tìm theo provider+provider_id
        $acc = Account::where('provider', $p['provider'])
            ->where('provider_id', $p['provider_id'])
            ->first();

        // 2) Nếu chưa có và có email -> gộp tài khoản theo email
        if (!$acc && !empty($p['email'])) {
            $acc = Account::where('email', $p['email'])->first();
        }

        if (!$acc) {
            // tạo mới
            $acc = new Account();
            $acc->account_type_id = 5;                 // ví dụ default "chưa chọn vai trò"
            $acc->status = 1;
        }

        // fill/update
        $acc->provider = $p['provider'];
        $acc->provider_id = $p['provider_id'];
        $acc->name = $p['name'];
        $acc->email = $p['email'];          // có thể null
        $acc->avatar_url = $p['avatar_url'];
        $acc->email_verified_at = $p['email'] ? now() : null;
        $acc->last_login_at = now();
        $acc->login_provider_last = $p['provider'];
        $acc->last_login_ip = $request->ip();

        // social login không cần password
        if (!$acc->exists) {
            $acc->account_type_id = 5; // guest
            $acc->status = 1;
            $acc->password = null;
        }


        $acc->save();

        return $acc;
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'role' => 'required|in:client,freelancer',
        ]);

        $user = Auth::user();

        $map = [
            'client' => 3,
            'freelancer' => 1,
        ];

        $user->account_type_id = $map[$data['role']];
        $user->save();

        return redirect()->intended(route('home'))->with('status', 'Chọn vai trò thành công!');
    }

}
