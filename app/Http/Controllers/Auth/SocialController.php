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
        // Nếu là "liên kết", bắt buộc đã đăng nhập và gắn cờ vào session
        if ($request->query('mode') === 'link') {
            if (!Auth::check()) {
                return redirect()->route('login')
                    ->with('error', 'Vui lòng đăng nhập trước khi liên kết tài khoản.');
            }
            session(['oauth_linking' => 'github']);
        } else {
            session()->forget('oauth_linking');
        }

        return Socialite::driver('github')->redirect();
    }

    public function githubCallback(Request $request)
    {
        // Có thể dùng stateless để an toàn với proxy; session vẫn đọc được.
        $gh = Socialite::driver('github')->stateless()->user();

        // ========== NHÁNH LIÊN KẾT ==========
        $isLinking = session('oauth_linking') === 'github' && Auth::check();
        if ($isLinking) {
            $account = Auth::user();

            // Fallback email (nếu user ẩn email trên GitHub)
            $email = $gh->getEmail()
                ?? ($gh->user['email'] ?? null)
                ?? ($gh->getNickname() ? $gh->getNickname() . '@users.noreply.github.com' : null);

            // Không cho một GitHub profile gắn với 2 account khác nhau
            $existsElsewhere = SocialAccount::where('provider', 'github')
                ->where('provider_id', (string) $gh->getId())
                ->where('account_id', '!=', $account->account_id)
                ->exists();
            if ($existsElsewhere) {
                session()->forget('oauth_linking');
                return redirect()->route('settings.connected')
                    ->with('error', 'Tài khoản GitHub này đã được liên kết với người dùng khác.');
            }

            // URL profile GitHub để lưu vào cột nickname (theo đúng yêu cầu)
            $githubUrl = $this->githubProfileUrl($gh->getNickname(), $gh->getId());

            // Lưu/cập nhật vào bảng social_accounts
            SocialAccount::updateOrCreate(
                [
                    'account_id' => $account->account_id,
                    'provider'   => 'github',
                ],
                [
                    'provider_id'      => (string) $gh->getId(),
                    'nickname'         => $githubUrl,                                 // <-- lưu URL vào nickname
                    'name'             => $gh->getName() ?: $gh->getNickname(),
                    'email'            => $email,
                    'avatar'           => $gh->getAvatar(),
                    'token'            => $gh->token ?? null,
                    'refresh_token'    => $gh->refreshToken ?? null,
                    'token_expires_at' => isset($gh->expiresIn) ? now()->addSeconds((int) $gh->expiresIn) : null,
                ]
            );

            session()->forget('oauth_linking');

            // ❌ KHÔNG đăng nhập lại
            return redirect()->route('settings.connected')
                ->with('success', 'Đã liên kết GitHub thành công.');
        }

        // ========== NHÁNH ĐĂNG NHẬP ==========
        // (giữ nguyên behavior login bằng GitHub như trước)
        $email = $gh->getEmail()
            ?? ($gh->user['email'] ?? null)
            ?? ($gh->getNickname() ? $gh->getNickname() . '@users.noreply.github.com' : null);

        $payload = [
            'provider'    => 'github',
            'provider_id' => (string) $gh->getId(),
            'email'       => $email,
            'name'        => $gh->getName() ?: $gh->getNickname() ?: 'GitHub User',
            'avatar_url'  => $gh->getAvatar(),
            'username'    => $gh->getNickname(),
        ];

        $account = $this->upsertAccountFromOAuth($payload, $request);
        Auth::login($account, true);

        if ((int) $account->account_type_id === $this->typeIdByCode('GUEST')) {
            return redirect()->route('role.select');
        }
        return redirect()->intended('/');
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
