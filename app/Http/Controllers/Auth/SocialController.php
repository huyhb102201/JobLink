<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\AccountType;
use App\Models\SocialAccount; // ✅ THÊM: dùng cho nhánh liên kết
use App\Http\Controllers\OAuthController;
class SocialController extends Controller
{
    // --- GOOGLE ---
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request)
    {
        $g = Socialite::driver('google')->stateless()->user();

        $payload = [
            'provider'    => 'google',
            'provider_id' => $g->getId(),
            'email'       => $g->getEmail(),
            'name'        => $g->getName() ?: 'User ' . Str::random(6),
            'avatar_url'  => $g->getAvatar(),
        ];

        $account = $this->upsertAccountFromOAuth($payload, $request);

        Auth::login($account, true);
        if ((int) $account->account_type_id === $this->typeIdByCode('GUEST')) {
            return redirect()->route('role.select');
        }
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
        $acc = Account::where('provider', $p['provider'])
            ->where('provider_id', $p['provider_id'])
            ->first();

        if (!$acc && !empty($p['email'])) {
            $acc = Account::where('email', $p['email'])->first();
        }

        if (!$acc) {
            $acc = new Account();
            $acc->account_type_id = $this->typeIdByCode('GUEST');
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
            'freelancer' => 'F_BASIC',
        ];

        $user->account_type_id = $this->typeIdByCode($mapCode[$data['role']]);
        $user->save();

        return redirect()->intended(route('home'))->with('status', 'Chọn vai trò thành công!');
    }

    // =========================
    //        G I T H U B
    // =========================

    public function githubRedirect(Request $request)
    {
        // proxy sang OAuthController, ép provider = 'github'
        return app(OAuthController::class)->redirect($request, 'github');
    }

    public function githubCallback(Request $request)
    {
        // proxy sang OAuthController, ép provider = 'github'
        return app(OAuthController::class)->callback($request, 'github');
    }

    private function githubProfileUrl(?string $nickname, $id): string
    {
        return $nickname
            ? "https://github.com/{$nickname}"
            : "https://github.com/" . urlencode((string) $id);
    }

    private function typeIdByCode(string $code): int
    {
        $code = strtoupper($code);

        return cache()->remember("acct_type_id_$code", 3600, function () use ($code) {
            return (int) AccountType::where('code', $code)->value('account_type_id');
        }) ?: 5;
    }
}
