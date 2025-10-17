<?php
// app/Http/Controllers/SocialAuthController.php
namespace App\Http\Controllers;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // Chỉ dùng để xin quyền lấy info; không login bằng provider
    public function redirect(string $provider)
    {
        // Nếu callback ở subdomain/SPA, có thể dùng ->stateless()
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        // Nếu dùng SPA/subdomain callback: $u = Socialite::driver($provider)->stateless()->user();
        $u = Socialite::driver($provider)->user();

        $account = Auth::user();               // đã đăng nhập sẵn vào hệ thống của bạn
        if (!$account) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước khi liên kết.');
        }

        // Tạo URL hồ sơ chỉ để hiển thị (không dùng đăng nhập)
        $profileUrl = match ($provider) {
            'github'   => $u->getNickname()
                            ? "https://github.com/{$u->getNickname()}"
                            : "https://github.com/".($u->getId() ?? ''),
            'facebook' => $u->getId()
                            ? "https://www.facebook.com/profile.php?id={$u->getId()}"
                            : 'https://www.facebook.com/',
            default    => '',
        };

        // Lưu đúng 1 mẫu tin / provider / account_id (không đụng gì đến session đăng nhập)
        SocialAccount::updateOrCreate(
            ['account_id' => $account->account_id, 'provider' => $provider],
            [
                'provider_id'  => (string) $u->getId(),
                'profile_url'  => $profileUrl,
                'display_name' => $u->getNickname() ?: $u->getName() ?: $u->getEmail(),
            ]
        );

        return redirect()->route('settings.connected')
            ->with('success', ucfirst($provider).' đã được liên kết (chỉ lưu hồ sơ, không dùng đăng nhập).');
    }
}
