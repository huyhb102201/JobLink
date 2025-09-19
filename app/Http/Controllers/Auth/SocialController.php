<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\AccountType;   // thêm dòng này trên đầu file

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
        if ((int) $account->account_type_id === $this->typeIdByCode('GUEST')) {
            return redirect()->route('role.select');
        }                          // session login
        return redirect()->intended('/');                      // về trang chủ
    }

    // --- FACEBOOK ---
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
        $acc = Account::where('provider', $p['provider'])
            ->where('provider_id', $p['provider_id'])
            ->first();

        if (!$acc && !empty($p['email'])) {
            $acc = Account::where('email', $p['email'])->first();
        }

        if (!$acc) {
            $acc = new Account();
            $acc->account_type_id = $this->typeIdByCode('GUEST'); // <-- thay vì 5 cứng
            $acc->status = 1;
        }

        $acc->provider = $p['provider'];
        $acc->provider_id = $p['provider_id'];
        $acc->name = $p['name'];
        $acc->email = $p['email'];
        $acc->avatar_url = $p['avatar_url'];
        $acc->email_verified_at = $p['email'] ? now() : null;
        $acc->last_login_at = now();
        $acc->login_provider_last = $p['provider'];
        $acc->last_login_ip = $request->ip();

        // nếu còn mới (chưa save lần nào) đảm bảo vẫn set là GUEST
        if (!$acc->exists) {
            $acc->account_type_id = $this->typeIdByCode('GUEST');
            $acc->status = 1;
            $acc->password = null;
        }

        $acc->save();
        return $acc;
    }

    public function store(Request $request)
    {
        $data = $request->validate(['role' => 'required|in:client,freelancer']);
        $user = Auth::user();

        $mapCode = [
            'client' => 'CLIENT',
            'freelancer' => 'F_BASIC', // hoặc code nào bạn quy ước cho freelancer mặc định
        ];

        $user->account_type_id = $this->typeIdByCode($mapCode[$data['role']]);
        $user->save();

        return redirect()->intended(route('home'))->with('status', 'Chọn vai trò thành công!');
    }

    public function githubRedirect()
    {
        return Socialite::driver('github')->redirect();
    }

    public function githubCallback(Request $request)
    {
        $gh = Socialite::driver('github')->stateless()->user();

        // Fallback email nếu user ẩn email trên GitHub
        $email = $gh->getEmail()
            ?? ($gh->user['email'] ?? null)
            ?? ($gh->getNickname() ? $gh->getNickname() . '@users.noreply.github.com' : null);

        $payload = [
            'provider' => 'github',
            'provider_id' => (string) $gh->getId(),
            'email' => $email,
            'name' => $gh->getName() ?: $gh->getNickname() ?: 'GitHub User',
            'avatar_url' => $gh->getAvatar(),
            'username' => $gh->getNickname(),   // nếu muốn lưu username
        ];

        $account = $this->upsertAccountFromOAuth($payload, $request);

        Auth::login($account, true);

        if ((int) $account->account_type_id === $this->typeIdByCode('GUEST')) {
            return redirect()->route('role.select');
        }

        return redirect()->intended('/');
    }

    private function typeIdByCode(string $code): int
    {
        $code = strtoupper($code);

        // cache 1h để đỡ query lại nhiều lần
        return cache()->remember("acct_type_id_$code", 3600, function () use ($code) {
            return (int) AccountType::where('code', $code)->value('account_type_id');
        }) ?: 5; // fallback cuối cùng nếu bảng rỗng hoặc thiếu code
    }
}
