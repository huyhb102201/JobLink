<?php
// app/Http/Controllers/OAuthController.php
namespace App\Http\Controllers;

use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    protected array $providers = ['github', 'facebook'];

    public function redirect(Request $request, string $provider)
    {
        abort_unless(in_array($provider, $this->providers, true), 404);

        // Nếu là liên kết, bắt buộc user đang đăng nhập
        if ($request->query('mode') === 'link') {
            if (!Auth::check()) {
                return redirect()->route('login')
                    ->with('error', 'Vui lòng đăng nhập trước khi liên kết tài khoản.');
            }
            // Cờ đánh dấu đang liên kết (dùng session cho đơn giản)
            session(['oauth_linking' => $provider]);
        } else {
            session()->forget('oauth_linking');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, $this->providers, true), 404);

        // Lấy thông tin từ provider (giữ stateful)
        $oauthUser = Socialite::driver($provider)->user();

        // ===== NHÁNH LIÊN KẾT =====
        $isLinking = session('oauth_linking') === $provider && Auth::check();
        if ($isLinking) {
            $account = Auth::user();

            // chặn một social profile gắn cho 2 account
            $alreadyLinkedElsewhere = \App\Models\SocialAccount::where('provider', $provider)
                ->where('provider_id', (string) $oauthUser->getId())
                ->where('account_id', '!=', $account->account_id)
                ->exists();
            if ($alreadyLinkedElsewhere) {
                session()->forget('oauth_linking');
                // ⚠️ route name đúng là 'settings.connected'
                return redirect()->route('settings.connected')
                    ->with('error', 'Tài khoản ' . $provider . ' này đã được liên kết với người dùng khác.');
            }

            $profileUrl = null;
            if ($provider === 'github') {
                $profileUrl = $this->githubProfileUrl($oauthUser->getNickname(), $oauthUser->getId());
            }

            \App\Models\SocialAccount::updateOrCreate(
                ['account_id' => $account->account_id, 'provider' => $provider],
                [
                    'provider_id' => (string) $oauthUser->getId(),
                    'nickname' => $profileUrl, // với GitHub: URL profile
                    'name' => $oauthUser->getName() ?: $oauthUser->getNickname(),
                    'email' => $oauthUser->getEmail(),
                    'avatar' => $oauthUser->getAvatar(),
                    'token' => $oauthUser->token ?? null,
                    'refresh_token' => $oauthUser->refreshToken ?? null,
                    'token_expires_at' => isset($oauthUser->expiresIn)
                        ? now()->addSeconds((int) $oauthUser->expiresIn)
                        : null,
                ]
            );

            session()->forget('oauth_linking');

            return redirect()->route('settings.connected')
                ->with('success', 'Đã liên kết ' . $provider . ' thành công.');
        }

        // ===== NHÁNH ĐĂNG NHẬP / ĐĂNG KÝ BẰNG OAUTH =====
        // 1) nếu social account đã tồn tại -> login
        $social = \App\Models\SocialAccount::where('provider', $provider)
            ->where('provider_id', (string) $oauthUser->getId())
            ->first();

        if ($social) {
            Auth::loginUsingId($social->account_id, remember: true);
            return redirect()->intended(route('home'));
        }

        // 2) nếu chưa, thử match theo email; nếu không có -> tạo account mới
        $email = $oauthUser->getEmail();
        $account = $email ? \App\Models\Account::where('email', $email)->first() : null;

        if (!$account) {
            $account = \App\Models\Account::create([
                'name' => $oauthUser->getName() ?: $oauthUser->getNickname() ?: 'User ' . \Illuminate\Support\Str::random(6),
                'email' => $email,
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                // tùy bạn: set account_type_id mặc định (GUEST) ở model events/migration
            ]);
        }

        $profileUrl = null;
        if ($provider === 'github') {
            $profileUrl = $this->githubProfileUrl($oauthUser->getNickname(), $oauthUser->getId());
        }

        \App\Models\SocialAccount::create([
            'account_id' => $account->account_id,
            'provider' => $provider,
            'provider_id' => (string) $oauthUser->getId(),
            'nickname' => $profileUrl, // GitHub: URL profile
            'name' => $oauthUser->getName() ?: $oauthUser->getNickname(),
            'email' => $email,
            'avatar' => $oauthUser->getAvatar(),
            'token' => $oauthUser->token ?? null,
            'refresh_token' => $oauthUser->refreshToken ?? null,
            'token_expires_at' => isset($oauthUser->expiresIn)
                ? now()->addSeconds((int) $oauthUser->expiresIn)
                : null,
        ]);

        Auth::login($account, remember: true);
        return redirect()->intended(route('home'));
    }


    private function githubProfileUrl(?string $nickname, $id): string
    {
        // Nickname gần như luôn có; nếu không, fallback dùng ID (ít gặp)
        if ($nickname) {
            return "https://github.com/{$nickname}";
        }
        return "https://github.com/" . urlencode((string) $id);
    }
}
