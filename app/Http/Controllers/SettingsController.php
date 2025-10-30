<?php
// app/Http/Controllers/SettingsController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Job;
use App\Models\JobApply;
use App\Models\Account;
use App\Models\JobReport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
class SettingsController extends Controller
{
    // helper: load user + relations dùng chung
    private function payload(Request $r)
    {
        $account = $r->user()->load(['profile', 'type']);
        $profile = $account->profile;
        $settings = is_array($account->settings ?? null) ? $account->settings : [];
        return compact('account', 'profile', 'settings');
    }

    // pages
    public function myInfo(Request $r)
    {
        return view('settings.my-info', $this->payload($r));
    }
    public function billing(Request $r)
    {
        return view('settings.billing', $this->payload($r));
    }
    public function security(Request $r)
    {
        return view('settings.security', $this->payload($r));
    }
    public function membership(Request $r)
    {
        return view('settings.membership', $this->payload($r));
    }
    public function teams(Request $r)
    {
        return view('settings.teams', $this->payload($r));
    }
    public function notifications(Request $r)
    {
        return view('settings.notifications', $this->payload($r));
    }
    public function members(Request $r)
    {
        return view('settings.members', $this->payload($r));
    }
    public function tax(Request $r)
    {
        return view('settings.tax', $this->payload($r));
    }
    public function connected(Request $r)
    {
        return view('settings.connected', $this->payload($r));
    }

    public function reported_jobs(Request $request)
    {
        $userId = Auth::id();

        $reports = JobReport::with(['job.client', 'job.jobCategory'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('settings.reported_jobs', compact('reports'));
    }
    // updates
    public function updateMyInfo(Request $request)
    {
        $user = $request->user()->loadMissing('profile');

        $data = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            // mô tả dài -> dùng TEXT
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $profile = $user->profile()->firstOrCreate(['account_id' => $user->account_id]);

        // Nếu bạn cho phép HTML mô tả, nhớ sanitize:
        $desc = $data['description'] ?? null;
        if (function_exists('clean')) {
            $desc = clean($desc, 'user_profile'); // preset whitelist của bạn
        }

        $profile->fill([
            'fullname' => $data['fullname'],
            'username' => $data['username'],
            'description' => $desc,
        ])->save();

        return back()->with('ok', 'Đã cập nhật thông tin.');
    }

    public function updateSecurity(Request $request)
    {
        $v = Validator::make($request->all(), [
            'password_current' => 'required',
            'password' => 'required|min:6|confirmed',
        ], [], [
            'password_current' => 'mật khẩu hiện tại',
            'password' => 'mật khẩu mới',
            'password_confirmation' => 'xác nhận mật khẩu',
        ]);

        if ($v->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $v->errors()], 422);
            }
            return back()->withErrors($v)->withInput();
        }

        $account = \App\Models\Account::find(Auth::id());
        if (!$account) {
            $msg = 'Không tìm thấy tài khoản.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 404)
                : back()->withErrors(['msg' => $msg]);
        }

        if (!Hash::check($request->password_current, $account->password)) {
            $msg = 'Mật khẩu hiện tại không đúng.';
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['password_current' => [$msg]]], 422);
            }
            return back()->withErrors(['password_current' => $msg])->withInput();
        }

        $account->password = Hash::make($request->password);
        $account->save();

        $ok = 'Đã đổi mật khẩu thành công.';
        return $request->expectsJson()
            ? response()->json(['ok' => $ok])
            : back()->with('ok', $ok);
    }

    public function updateNotifications(Request $request)
    {
        $user = $request->user();
        $s = is_array($user->settings ?? null) ? $user->settings : [];
        $s['online_for_messages'] = $request->boolean('online_for_messages');
        $s['notify_messages'] = $request->boolean('notify_messages');
        $s['notify_new_proposals'] = $request->boolean('notify_new_proposals');
        $user->settings = $s;
        $user->save();
        return back()->with('ok', 'Đã lưu thông báo.');
    }

    public function changeMembership(Request $request)
    {
        $request->validate(['account_type_id' => 'required|integer']);
        $user = $request->user();
        $user->account_type_id = (int) $request->account_type_id;
        $user->save();
        return back()->with('ok', 'Đã đổi gói tài khoản.');
    }
     public function destroyAccount(Request $request)
{
    // 1) Validate với named error bag 'delete' để lỗi hiển thị trong modal xoá
    $v = Validator::make($request->all(), [
        'password'     => 'required|string|min:6',
        'confirm_text' => 'required|string',
        'agree'        => 'accepted',
    ], [
        'agree.accepted' => 'Bạn cần tick xác nhận xoá vĩnh viễn.',
    ]);

    if ($v->fails()) {
        return back()->withErrors($v, 'delete')->withInput();
    }

    if (strtoupper(trim($request->confirm_text)) !== 'DELETE') {
        return back()->withErrors(['confirm_text' => 'Bạn phải nhập chính xác DELETE để xác nhận.'], 'delete')
                     ->withInput();
    }

    /** @var \App\Models\Account $user */
    $user = Auth::user();
    if (!$user) {
        return redirect()->route('login');
    }

    // (Tuỳ chọn) Không cho xóa super admin
    if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
        return back()->withErrors(['password' => 'Tài khoản quản trị tối cao không thể bị xoá.'], 'delete');
    }

    if (!Hash::check($request->password, $user->password)) {
        return back()->withErrors(['password' => 'Mật khẩu không đúng.'], 'delete')->withInput();
    }

    DB::beginTransaction();
    try {
        $aid = (int) $user->account_id;

        // 2) Thu hồi session/token trước để ngắt truy cập
        // Sanctum/Passport/Personal Access Token (tuỳ bạn dùng gì)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
        // Laravel sessions table (nếu dùng database driver cho session)
        try {
            DB::table('sessions')->where('user_id', $aid)->delete();
        } catch (\Throwable $e) {
            // Bỏ qua nếu không dùng DB sessions
        }

        // 3) Xoá dữ liệu phụ thuộc THEO THỨ TỰ AN TOÀN
        // === Các bảng LIÊN QUAN tới jobs (xoá phụ thuộc trước rồi mới xoá jobs) ===
        $jobIds = DB::table('jobs')->where('account_id', $aid)->pluck('job_id');
        if ($jobIds->count()) {
            // Ví dụ các bảng phụ thuộc job_id — tuỳ schema của bạn, thêm bớt tại đây
            DB::table('tasks')->whereIn('job_id', $jobIds)->delete();
            DB::table('comments')->whereIn('job_id', $jobIds)->delete();
            DB::table('jobs_view')->whereIn('job_id', $jobIds)->delete();
            DB::table('job_favorites')->whereIn('job_id', $jobIds)->delete();
            DB::table('job_reports')->whereIn('job_id', $jobIds)->delete();
              DB::table('job_detail')->whereIn('job_id', $jobIds)->delete();
            // Cuối cùng mới xoá jobs
            DB::table('jobs')->whereIn('job_id', $jobIds)->delete();
        }

        // === Các bảng phụ thuộc account_id / user liên quan ===
        // Ví dụ (tuỳ schema của bạn, thêm bớt tại đây):
        DB::table('profiles')->where('account_id', $aid)->delete();
        DB::table('bank_accounts')->where('account_id', $aid)->delete();
        DB::table('withdrawal_logs')->where('account_id', $aid)->delete();
        DB::table('notifications')->where('user_id', $aid)->delete();
        DB::table('job_favorites')->where('user_id', $aid)->delete();
        DB::table('jobs_view')->where('account_id', $aid)->delete();
        DB::table('payments')->where('account_id', $aid)->delete();;
        // ratings: có thể lưu cả rater_id và ratee_id
        // message/chat nếu có:
        try {
            DB::table('messages')->where('sender_id', $aid)->orWhere('receiver_id', $aid)->delete();
            DB::table('box_chat_members')->where('account_id', $aid)->delete();
            // Nếu có bảng box_chats không còn thành viên, bạn có thể dọn dẹp thêm...
        } catch (\Throwable $e) {
            // Bỏ qua nếu app chưa có các bảng này
        }

        // Personal access tokens (bảng mặc định của Sanctum)
        try {
            DB::table('personal_access_tokens')->where('tokenable_type', get_class($user))
                                               ->where('tokenable_id', $aid)->delete();
        } catch (\Throwable $e) {
            // Bỏ qua nếu không dùng Sanctum
        }

        // 4) Cuối cùng xoá account (Hard delete hoặc forceDelete nếu SoftDeletes)
        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses($user))) {
            $user->forceDelete();
        } else {
            $user->delete();
        }

        DB::commit();

        // 5) Đăng xuất & xoá session hiện tại
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Tài khoản đã được xoá vĩnh viễn.');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors([
            'general' => 'Xoá tài khoản thất bại: ' . $e->getMessage() .
                         ' (hãy kiểm tra khoá ngoại ON DELETE CASCADE hoặc xoá thủ công các bản ghi liên quan).'
        ], 'delete');
    }
}
}
