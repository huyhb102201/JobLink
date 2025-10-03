<?php
// app/Http/Controllers/SettingsController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Job;
use App\Models\JobApply;

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
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'skill' => 'nullable|string|max:1000',
            // KHÔNG có 'email'
        ]);

        $profile = $user->profile()->firstOrCreate(['account_id' => $user->account_id]);

        $profile->fill([
            'fullname' => $data['fullname'],
            'username' => $data['username'],
            'description' => $data['description'] ?? null,
            'skill' => $data['skill'] ?? null,
            // KHÔNG set 'email'
        ])->save();

        return back()->with('ok', 'Đã cập nhật thông tin.');
    }


    public function updateSecurity(Request $request)
    {
        $request->validate([
            'password_current' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = $request->user();
        if (!Hash::check($request->password_current, $user->password)) {
            return back()->withErrors(['password_current' => 'Mật khẩu hiện tại không đúng.']);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        return back()->with('ok', 'Đã đổi mật khẩu.');
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
