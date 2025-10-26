<?php
// app/Http/Controllers/OAuthController.php
namespace App\Http\Controllers;

use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    protected array $providers = ['github','facebook'];

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

        // Lấy thông tin từ provider
        $oauthUser = Socialite::driver($provider)->user();

        // === NHÁNH LIÊN KẾT: chỉ thêm vào DB, KHÔNG login ===
        $isLinking = session('oauth_linking') === $provider && Auth::check();
        if ($isLinking) {
            $account = Auth::user();

            // Không cho 1 GitHub profile gắn cho 2 tài khoản khác nhau
            $alreadyLinkedElsewhere = SocialAccount::where('provider', $provider)
                ->where('provider_id', (string) $oauthUser->getId())
                ->where('account_id', '!=', $account->account_id)
                ->exists();

            if ($alreadyLinkedElsewhere) {
                session()->forget('oauth_linking');
                return redirect()->route('settings.connected.index')
                    ->with('error', 'Tài khoản '.$provider.' này đã được liên kết với người dùng khác.');
            }

            // URL GitHub (lưu vào cột nickname như bạn yêu cầu)
            $githubUrl = $provider === 'github'
                ? $this->githubProfileUrl($oauthUser->getNickname(), $oauthUser->getId())
                : null;

            // Lưu/ cập nhật vào bảng social_accounts
            SocialAccount::updateOrCreate(
                [
                    'account_id' => $account->account_id,
                    'provider'   => $provider,
                ],
                [
                    'provider_id'      => (string) $oauthUser->getId(),
                    'nickname'         => $githubUrl, // <-- LƯU URL VÀO nickname
                    'name'             => $oauthUser->getName() ?: $oauthUser->getNickname(),
                    'email'            => $oauthUser->getEmail(),
                    'avatar'           => $oauthUser->getAvatar(),
                    'token'            => $oauthUser->token ?? null,
                    'refresh_token'    => $oauthUser->refreshToken ?? null,
                    'token_expires_at' => isset($oauthUser->expiresIn)
                        ? now()->addSeconds((int) $oauthUser->expiresIn)
                        : null,
                ]
            );

            session()->forget('oauth_linking');

            return redirect()->route('settings.connected.index')
                ->with('success', 'Đã liên kết '.$provider.' thành công.');
        }

        // === (Tùy chọn) NHÁNH LOGIN/ SIGNUP bằng OAuth nếu bạn còn dùng cho nút "Đăng nhập với GitHub"
        // Ở câu hỏi này bạn chỉ cần nhánh link ở trên, nên có thể bỏ toàn bộ login flow bên dưới.
        // Nếu vẫn giữ, nhớ không chạy tới đây khi mode=link.
        return redirect()->route('login')->with('error', 'Không hợp lệ.');
    }

    private function githubProfileUrl(?string $nickname, $id): string
    {
        // Nickname gần như luôn có; nếu không, fallback dùng ID (ít gặp)
        if ($nickname) {
            return "https://github.com/{$nickname}";
        }
        return "https://github.com/".urlencode((string) $id);
    }
}
