<?php
namespace App\Http\Controllers;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;
class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->user();

        $account = Auth::user(); // bạn đang dùng account_id

        SocialAccount::updateOrCreate(
            ['account_id' => $account->account_id, 'provider' => $provider],
            [
                'provider_id'     => $socialUser->getId(),
                'nickname'        => $socialUser->getNickname(),
                'name'            => $socialUser->getName(),
                'email'           => $socialUser->getEmail(),
                'avatar'          => $socialUser->getAvatar(),
                'token'           => $socialUser->token ?? null,
                'refresh_token'   => $socialUser->refreshToken ?? null,
                'token_expires_at'=> isset($socialUser->expiresIn)
                                        ? Carbon::now()->addSeconds($socialUser->expiresIn)
                                        : null,
            ]
        );

        return redirect()->route('settings.connected')
            ->with('success', ucfirst($provider).' đã được liên kết thành công!');
    }
}
