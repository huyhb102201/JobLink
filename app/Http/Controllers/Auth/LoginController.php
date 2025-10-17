<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Account;
use Carbon\Carbon;

class LoginController extends Controller
{
    const WINDOW_MINUTES = 5;     // khoảng “liên tục”
    const TEMP_LOCK_MIN  = 5;     // thời gian khóa tạm

    public function show()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        $email = Str::lower($request->input('email'));
        /** @var Account|null $user */
        $user  = Account::where('email', $email)->first();

        // 1) Không tồn tại user
        if (!$user) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        // 2) Khóa vĩnh viễn
        if ((int)$user->status === 0) {
            return back()->withErrors(['email' => 'Tài khoản của bạn đã bị khóa vĩnh viễn. Vui lòng liên hệ quản trị viên (22004027@st.vlute.edu.vn) nha!!!!.']);
        }

        // 3) Khóa tạm thời còn hiệu lực
        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            $remainSec = now()->diffInSeconds($user->locked_until, false) * -1;
            // diffInSeconds với false trả âm, mình đảo dấu để lấy số dương còn lại
            $remainSec = max(0, $remainSec);
            return back()
                ->withErrors(['email' => "Tài khoản đang bị khóa tạm thời. Vui lòng thử lại sau."])
                ->with('locked_until_ts', $user->locked_until->getTimestamp());
        }

        // 4) Nếu đã qua WINDOW kể từ lần sai gần nhất -> reset đếm (đảm bảo “không liên tục” thì không cộng dồn)
        if ($user->last_failed_at && $user->last_failed_at->lt(now()->subMinutes(self::WINDOW_MINUTES))) {
            $user->failed_attempts = 0;
            // Sau 1 khoảng dài không nhập, có thể coi như bỏ qua “án tạm” trước đó:
            // Bạn có thể reset strikes ở đây nếu muốn “khoan hồng” sau thời gian dài:
            // $user->temp_lock_strikes = 0;
            $user->save();
        }

        // 5) Thử đăng nhập
        if (Auth::attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->update([
                'last_login_at'    => now(),
                'last_login_ip'    => $request->ip(),
                'locked_until'     => null,
                'failed_attempts'  => 0,
                'last_failed_at'   => null,
                'temp_lock_strikes'=> 0, // reset “án”
            ]);

            $user->loadMissing('type');
            if ($user->type?->code === 'ADMIN') {
                return redirect()->intended(route('admin.accounts.index'));
            }
            return redirect()->intended('/');
        }

        // 6) Sai mật khẩu -> cập nhật đếm và xử lý án
        $user->failed_attempts = (int)$user->failed_attempts + 1;
        $user->last_failed_at  = now();

        // Nhánh A: chưa từng bị khóa tạm (strikes == 0) -> ăn án sau 3 lần sai liên tục
        if ((int)$user->temp_lock_strikes === 0 && (int)$user->failed_attempts >= 3) {
            $user->locked_until      = now()->addMinutes(self::TEMP_LOCK_MIN);
            $user->temp_lock_strikes = 1;     // đánh dấu đã từng ăn án
            $user->failed_attempts   = 0;     // reset đếm sau khi dính án tạm
            $user->save();

            return back()
                ->withErrors(['email' => "Bạn đã nhập sai 3 lần liên tiếp. Tài khoản bị khóa tạm trong ".self::TEMP_LOCK_MIN." phút."])
                ->with('locked_until_ts', $user->locked_until->getTimestamp());
        }

        // Nhánh B: đã từng bị khóa tạm (strikes >= 1) -> sai 2 lần liên tục => khóa vĩnh viễn
        if ((int)$user->temp_lock_strikes >= 1 && (int)$user->failed_attempts >= 2) {
            $user->status            = 0;     // khóa vĩnh viễn
            $user->locked_until      = null;
            $user->failed_attempts   = 0;
            $user->save();

            return back()->withErrors(['email' => "Bạn tiếp tục nhập sai sau khi mở khóa. Tài khoản đã bị khóa vĩnh viễn."]);
        }

        // Chưa đủ ngưỡng -> báo lỗi chung kèm số lần hiện tại trong “chuỗi liên tục”
        $remaining = ((int)$user->temp_lock_strikes === 0)
            ? max(0, 3 - (int)$user->failed_attempts)  // còn bao nhiêu lần tới án tạm
            : max(0, 2 - (int)$user->failed_attempts); // còn bao nhiêu lần tới án vĩnh viễn

        $user->save();

        $stageLabel = ((int)$user->temp_lock_strikes === 0) ? 'khóa tạm' : 'khóa vĩnh viễn';
        return back()->withErrors([
            'email' => "Email hoặc mật khẩu không đúng. (Sai liên tiếp {$user->failed_attempts}, còn {$remaining} lần trước khi {$stageLabel})"
        ])->onlyInput('email');
    }
}
