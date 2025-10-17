<?php
// app/Http/Controllers/Auth/AccountPasswordResetController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountPasswordResetController extends Controller
{
    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
        ]);

        // Không tiết lộ email có tồn tại hay không: vẫn trả OK
        $account = Account::where('email', $request->email)->first();

        if ($account) {
            $token = Str::random(64);
            $account->password_reset_token = $token;
            $account->password_reset_expires_at = Carbon::now()->addMinutes(60);
            $account->save();

            $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($account->email));
            $this->sendResetMail($account, $resetUrl); // dùng SendGrid
        }

        return back()->with('status', 'Chúng tôi đã gửi liên kết đặt lại mật khẩu.');
    }

    public function showResetForm(Request $request, $token)
    {
        $email = $request->query('email');

        $account = Account::where('email', $email)
            ->where('password_reset_token', $token)
            ->first();

        // Không có tài khoản/tokens hoặc đã hết hạn -> chặn luôn
        if (!$account || !$account->password_reset_expires_at || now()->greaterThan($account->password_reset_expires_at)) {
            // Có thể chuyển sang view thông báo riêng hoặc quay về trang quên mật khẩu
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }


    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự',
        ]);

        $account = Account::where('email', $request->email)
            ->where('password_reset_token', $request->token)
            ->first();

        if (!$account || !$account->password_reset_expires_at || now()->greaterThan($account->password_reset_expires_at)) {
            return back()->withErrors(['email' => 'Liên kết không hợp lệ hoặc đã hết hạn.']);
        }

        $account->password = Hash::make($request->password);
        $account->password_reset_token = null;
        $account->password_reset_expires_at = null;
        // rotate remember_token để đăng xuất phiên cũ (nếu có)
        $account->remember_token = Str::random(60);
        $account->save();

        return redirect()->route('login')->with('status', 'Đổi mật khẩu thành công. Bạn có thể đăng nhập lại.');
    }

    private function sendResetMail(Account $account, string $resetUrl): void
    {
        // === SendGrid giống hàm sendCustomVerification() của bạn ===
        $mail = new \SendGrid\Mail\Mail();
        $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
        $mail->setSubject('Đặt lại mật khẩu');
        $mail->addTo($account->email, $account->name ?? 'Người dùng');
        $mail->addContent('text/html', "
            <div style='font-family: Arial, sans-serif; background-color:#f9f9f9; padding:20px;'>
                <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:8px; padding:30px; box-shadow:0 2px 5px rgba(0,0,0,0.1);'>
                    <h2 style='color:#333; text-align:center; margin-bottom:20px;'>Yêu cầu đặt lại mật khẩu</h2>
                    <p style='font-size:15px; color:#555; line-height:1.6;'>
                        Xin chào,<br><br> Bạn vừa yêu cầu đặt lại mật khẩu. Nhấn nút bên dưới để tiếp tục:
                    </p>
                    <div style='text-align:center; margin:30px 0;'>
                        <a href='{$resetUrl}'
                           style='background-color:#28a745; color:#fff; padding:12px 24px; border-radius:5px; text-decoration:none; font-size:16px;'>
                           Đặt lại mật khẩu
                        </a>
                    </div>
                    <p style='font-size:13px; color:#999; text-align:center;'>
                        Nếu nút không hoạt động, hãy copy liên kết sau và dán vào trình duyệt:<br>
                        <a href='{$resetUrl}' style='color:#007bff;'>{$resetUrl}</a>
                    </p>
                    <hr style='border:none; border-top:1px solid #eee; margin:30px 0;'>
                    <p style='font-size:12px; color:#aaa; text-align:center;'>Liên kết có hiệu lực 60 phút.</p>
                </div>
            </div>
        ");
        $sg = new \SendGrid(env('SENDGRID_API_KEY'));
        $sg->send($mail);
    }
}
