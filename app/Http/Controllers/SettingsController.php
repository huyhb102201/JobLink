<?php
// app/Http/Controllers/SettingsController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Job;
use App\Models\JobApply;
use App\Models\Account;
use Illuminate\Support\Facades\Validator;
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
    public function appeals(Request $r)
    {
        return view('settings.appeals', $this->payload($r));
    }

    public function submitted_jobs(Request $request)
    {
        $userId = Auth::id();

        // Lấy tất cả job_apply của user hiện tại
        $applies = JobApply::with(['job.jobCategory'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('settings.submitted_jobs', compact('applies'));
    }
    // updates
    public function updateMyInfo(Request $request)
{
    $user = $request->user()->loadMissing('profile');

    $data = $request->validate([
        'fullname'    => ['required','string','max:255'],
        'username'    => ['required','string','max:255'],
        // mô tả dài -> dùng TEXT
        'description' => ['nullable','string','max:5000'],
    ]);

    $profile = $user->profile()->firstOrCreate(['account_id' => $user->account_id]);

    // Nếu bạn cho phép HTML mô tả, nhớ sanitize:
    $desc = $data['description'] ?? null;
    if (function_exists('clean')) {
        $desc = clean($desc, 'user_profile'); // preset whitelist của bạn
    }

    $profile->fill([
        'fullname'    => $data['fullname'],
        'username'    => $data['username'],
        'description' => $desc,
    ])->save();

    return back()->with('ok','Đã cập nhật thông tin.');
}

public function updateSecurity(Request $request)
{
    $v = Validator::make($request->all(), [
        'password_current' => 'required',
        'password'         => 'required|min:6|confirmed',
    ], [], [
        'password_current' => 'mật khẩu hiện tại',
        'password'         => 'mật khẩu mới',
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
}
