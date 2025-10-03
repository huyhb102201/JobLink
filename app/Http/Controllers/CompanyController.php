<?php
// app/Http/Controllers/CompanyController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Org;
use App\Models\Account;
use SendGrid\Mail\Mail as SGMail;
use Illuminate\Support\Str;
use App\Models\OrgInvitation;
class CompanyController extends Controller
{
    // Trang "Doanh nghiệp của tôi"
    // CompanyController@index
    public function index(Request $r)
    {
        $account = $r->user()->loadMissing(['type', 'profile']);
        $isBusiness = ($account?->type?->code === 'BUSS');

        $org = null;
        $members = collect();
        $usedSeats = 0;

        if ($isBusiness) {
            $org = \App\Models\Org::where('owner_account_id', $account->account_id)->first();

            if ($org) {
                $members = \DB::table('org_members as om')
                    ->join('accounts as a', 'a.account_id', '=', 'om.account_id')
                    ->leftJoin('profiles as p', 'p.account_id', '=', 'a.account_id')
                    ->where('om.org_id', $org->org_id)
                    ->select('a.account_id', 'p.fullname', 'a.email', 'om.role', 'om.created_at as joined_at')
                    ->orderByRaw("FIELD(om.role,'OWNER','ADMIN','MANAGER','MEMBER','BILLING')")
                    ->orderBy('p.fullname')
                    ->get();

                $usedSeats = $members->count();
            }
        }

        return view('settings.company', compact('account', 'isBusiness', 'org', 'usedSeats', 'members'));
    }


    // Tạo doanh nghiệp
    public function store(Request $r)
    {
        $account = $r->user()->loadMissing('type');
        if (($account?->type?->code) !== 'BUSS') {
            return back()->withErrors(['msg' => 'Chỉ tài khoản Business mới được tạo doanh nghiệp.']);
        }

        if (Org::where('owner_account_id', $account->account_id)->exists()) {
            return back()->withErrors(['msg' => 'Bạn đã có doanh nghiệp rồi.']);
        }

        $data = $r->validate([
            'name' => 'required|string|max:255',
            'seats_limit' => 'required|integer|min:1|max:200',
            'description' => 'nullable|string|max:1000',
        ]);

        // Tạo org
        $org = Org::create([
            'owner_account_id' => $account->account_id,
            'name' => $data['name'],
            'seats_limit' => $data['seats_limit'],
            'description' => $data['description'] ?? null,
        ]);

        // Ghi chủ sở hữu vào org_members với role OWNER (nếu có bảng này)
        DB::table('org_members')->insert([
            'org_id' => $org->org_id,
            'account_id' => $account->account_id,
            'role' => 'OWNER',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('settings.company')->with('ok', 'Đã tạo doanh nghiệp.');
    }
    public function addMemberByUsername(Request $r)
    {
        // username chỉ gồm chữ, số, dấu . _ -
        $r->validate([
            'org_id' => 'required|integer',
            'username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/'],
            '_modal' => 'nullable|string',
        ]);

        $me = $r->user()->loadMissing('type');
        if (($me->type?->code) !== 'BUSS') {
            return back()->withErrors(['msg' => 'Chỉ Business mới được thêm thành viên.']);
        }

        $org = Org::where('org_id', $r->org_id)
            ->where('owner_account_id', $me->account_id)
            ->first();
        if (!$org) {
            return back()->withErrors(['msg' => 'Bạn không có quyền với tổ chức này.'])->withInput();
        }

        // Hết ghế?
        $used = \DB::table('org_members')->where('org_id', $org->org_id)->count();
        if ($used >= $org->seats_limit) {
            return back()->withErrors(['username' => 'Đã hết số ghế.'])->withInput();
        }

        // Lấy username (không có @), có thể trim khoảng trắng
        $username = trim($r->username);

        // Tìm theo profiles.username
        // Nếu CSDL của bạn dùng collation CI (không phân biệt hoa/thường) thì chỉ cần where('username', $username)
        $target = Account::with('profile')
            ->whereHas('profile', function ($q) use ($username) {
                $q->where('username', $username);
                // Nếu muốn đảm bảo không phân biệt hoa/thường bất kể collation:
                // $q->whereRaw('LOWER(username) = ?', [mb_strtolower($username)]);
            })
            ->first();

        if (!$target) {
            return back()->withErrors(['username' => 'Không tìm thấy username này.'])->withInput();
        }

        // Không thêm trùng (và không tự thêm chính mình)
        if ((int) $target->account_id === (int) $me->account_id) {
            return back()->withErrors(['username' => 'Bạn đã là thành viên (Owner).'])->withInput();
        }
        $exists = \DB::table('org_members')
            ->where('org_id', $org->org_id)
            ->where('account_id', $target->account_id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['username' => 'Người này đã trong tổ chức.'])->withInput();
        }

        \DB::table('org_members')->insert([
            'org_id' => $org->org_id,
            'account_id' => $target->account_id,
            'role' => 'MEMBER',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Đã thêm @' . $target->profile->username . ' vào doanh nghiệp.');
    }
    protected function sendOrgInviteEmail(Account $target, Org $org, OrgInvitation $invite): void
    {
        $verifyUrl = route('company.invite.accept', $invite->token);

        $mail = new SGMail();
        $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
        $mail->setSubject('Lời mời tham gia doanh nghiệp: ' . $org->name);
        $mail->addTo($target->email, $target->profile?->fullname ?: ($target->name ?: 'Bạn'));
        $mail->addContent('text/html', "
    <div style='font-family:Arial,sans-serif;background:#f9f9f9;padding:20px;'>
      <div style='max-width:600px;margin:auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 6px 20px rgba(0,0,0,.06)'>
        <h2 style='margin:0 0 12px;color:#111'>Tham gia doanh nghiệp</h2>
        <p style='color:#555;line-height:1.6;margin:0 0 16px'>
          Bạn được mời tham gia doanh nghiệp <strong>{$org->name}</strong> với quyền <strong>{$invite->role}</strong>.
        </p>
        <div style='text-align:center;margin:26px 0'>
          <a href='{$verifyUrl}' style='display:inline-block;background:#0d6efd;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none'>
            Chấp nhận lời mời
          </a>
        </div>
        <p style='color:#888;font-size:13px;margin:0 0 8px;text-align:center'>
          Nếu nút không hoạt động, mở liên kết: <a href='{$verifyUrl}' style='color:#0d6efd'>{$verifyUrl}</a>
        </p>
        <hr style='border:none;border-top:1px solid #eee;margin:24px 0'>
        <p style='color:#aaa;font-size:12px;text-align:center;margin:0'>
          Liên kết hết hạn: " . ($invite->expires_at ? $invite->expires_at->format('d/m/Y H:i') : '—') . "
        </p>
      </div>
    </div>");

        $sg = new \SendGrid(env('SENDGRID_API_KEY'), [
            'curl' => [
                CURLOPT_CAINFO => storage_path('certs/cacert.pem'),
                CURLOPT_CAPATH => storage_path('certs'),
                // KHÔNG nên tắt verify; chỉ dùng tạm lúc cần test:
                // CURLOPT_SSL_VERIFYPEER => false,
                // CURLOPT_SSL_VERIFYHOST => 0,
            ],
        ]);

        try {
            $resp = $sg->send($mail);
            // Ghi log để biết tình trạng
            \Log::info('SendGrid invite', ['code' => $resp->statusCode(), 'body' => $resp->body()]);
        } catch (\Throwable $e) {
            \Log::error('SendGrid invite error', ['msg' => $e->getMessage()]);
            throw $e;
        }
    }
    public function inviteByUsername(Request $r)
    {
        $r->validate([
            'org_id' => 'required|integer',
            'username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/'],
            '_modal' => 'nullable|string',
        ]);

        $me = $r->user()->loadMissing('type');
        if (($me->type?->code) !== 'BUSS') {
            return back()->withErrors(['msg' => 'Chỉ Business mới được mời thành viên.']);
        }

        $org = Org::where('org_id', $r->org_id)
            ->where('owner_account_id', $me->account_id)
            ->first();
        if (!$org)
            return back()->withErrors(['msg' => 'Bạn không có quyền với tổ chức này.'])->withInput();

        // tìm user theo profiles.username
        $username = trim($r->username);
        $target = Account::with('profile')
            ->whereHas('profile', fn($q) => $q->where('username', $username))
            ->first();
        if (!$target)
            return back()->withErrors(['username' => 'Không tìm thấy username này.'])->withInput();

        // đã là member?
        $exists = DB::table('org_members')->where('org_id', $org->org_id)->where('account_id', $target->account_id)->exists();
        if ($exists)
            return back()->withErrors(['username' => 'Người này đã trong tổ chức.'])->withInput();

        // kiếm invite còn hạn, nếu chưa thì tạo mới
        $invite = OrgInvitation::where('org_id', $org->org_id)
            ->where('email', $target->email)
            ->where('status', 'PENDING')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$invite) {
            $invite = OrgInvitation::create([
                'org_id' => $org->org_id,
                'email' => $target->email,
                'role' => 'MEMBER',
                'token' => Str::random(48),
                'expires_at' => now()->addDays(7),
                'status' => 'PENDING',
            ]);
        }

        // gửi mail qua SendGrid
        $this->sendOrgInviteEmail($target, $org, $invite);

        return back()->with('ok', 'Đã gửi lời mời tới ' . $target->email . '.');
    }
    public function acceptInvite(Request $r, string $token)
    {
        $invite = OrgInvitation::where('token', $token)->first();
        if (!$invite || $invite->status !== 'PENDING' || ($invite->expires_at && $invite->expires_at <= now())) {
            return redirect()->route('settings.company')->withErrors(['msg' => 'Lời mời không hợp lệ hoặc đã hết hạn.']);
        }

        if (!$r->user()) {
            session(['url.intended' => route('company.invite.accept', $token)]);
            return redirect()->route('login')->withErrors(['msg' => 'Vui lòng đăng nhập để chấp nhận lời mời.']);
        }

        $user = $r->user();
        if (strcasecmp($user->email, $invite->email) !== 0) {
            return redirect()->route('settings.company')->withErrors(['msg' => 'Email tài khoản không khớp email được mời.']);
        }

        $org = Org::find($invite->org_id);
        if (!$org)
            return redirect()->route('settings.company')->withErrors(['msg' => 'Tổ chức không tồn tại.']);

        // check ghế khi accept
        $used = DB::table('org_members')->where('org_id', $org->org_id)->count();
        if ($used >= $org->seats_limit) {
            return redirect()->route('settings.company')->withErrors(['msg' => 'Tổ chức đã hết ghế.']);
        }

        DB::table('org_members')->updateOrInsert(
            ['org_id' => $org->org_id, 'account_id' => $user->account_id],
            ['role' => $invite->role, 'created_at' => now(), 'updated_at' => now()]
        );

        $invite->update(['status' => 'ACCEPTED']);

        return redirect()->route('settings.company')->with('ok', 'Bạn đã gia nhập doanh nghiệp: ' . $org->name);
    }

}
