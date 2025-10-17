<?php
namespace App\Http\Controllers;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;

class ConnectedServicesController extends Controller
{
    public function index()
    {
        $account = Auth::user()->loadMissing('socialAccounts');

        $providers = ['github','facebook'];
        $linked = collect($providers)->mapWithKeys(fn($p) => [
            $p => $account->socialAccounts->firstWhere('provider', $p)
        ]);

        return view('settings.connected', compact('account', 'linked'));
    }

    public function unlink(string $provider)
    {
        $account = Auth::user();
        $social = SocialAccount::where('account_id', $account->account_id)
            ->where('provider', $provider)
            ->first();

        if (!$social) {
            return back()->with('error', 'Bạn chưa liên kết '.$provider.'.');
        }

        $social->delete();

        return back()->with('success', 'Đã hủy liên kết '.$provider.' thành công.');
    }
}
