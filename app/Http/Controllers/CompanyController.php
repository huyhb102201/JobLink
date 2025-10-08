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
use Illuminate\Support\Facades\Storage;
use App\Models\OrgVerification;
use Illuminate\Validation\Rules\File;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CompanyController extends Controller
{
    // Trang "Doanh nghiệp của tôi"
    // CompanyController@index
    public function index(Request $r)
{
    $account     = $r->user()->loadMissing(['type','profile']);
    $isBusiness  = ($account?->type?->code === 'BUSS');

    $org = null;
    $members = collect();
    $usedSeats = 0;
    $latestVerification = null;   // <-- có biến này

    if ($isBusiness) {
        $org = \App\Models\Org::where('owner_account_id', $account->account_id)->first();

        if ($org) {
            // ... phần query $members của bạn giữ nguyên ...
            $members = \DB::table('org_members as om')
                    ->join('accounts as a', 'a.account_id', '=', 'om.account_id')
                    ->leftJoin('profiles as p', 'p.account_id', '=', 'a.account_id')
                    ->where('om.org_id', $org->org_id)
                    ->select(
                        'a.account_id',
                        'a.email',
                        'p.fullname',
                        'om.role',
                        'om.status',
                        'om.created_at as joined_at',
                        'om.updated_at'
                    )
                    ->orderByRaw("FIELD(om.status,'PENDING','ACTIVE')") // đưa pending lên/ xuống tuỳ ý
                    ->orderByRaw("FIELD(om.role,'OWNER','ADMIN','MANAGER','MEMBER','BILLING')")
                    ->orderBy('p.fullname')
                    ->get();
            // LẤY HỒ SƠ XÁC MINH GẦN NHẤT
            $latestVerification = \DB::table('org_verifications')
                ->where('org_id', $org->org_id)
                ->orderByDesc('created_at')
                ->first();

            $usedSeats = $members->count();
        }
    }

    return view('settings.company', compact(
        'account','isBusiness','org','usedSeats','members','latestVerification'
    ));
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

        // KHÔNG truyền curl options như CAINFO/CAPATH
        $sg = new \SendGrid(env('SENDGRID_API_KEY'));

        try {
            $resp = $sg->send($mail);
            \Log::info('SendGrid invite', ['code' => $resp->statusCode()]);
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
            // KHÔNG đếm ghế ở bước mời; chỉ đếm khi accept
            $member = \DB::table('org_members')
                ->where('org_id', $org->org_id)
                ->where('account_id', $target->account_id)
                ->first();

            if ($member && $member->status === 'ACTIVE') {
                return back()->withErrors(['username' => 'Người này đã là thành viên.'])->withInput();
            }

            \DB::table('org_members')->updateOrInsert(
                ['org_id' => $org->org_id, 'account_id' => $target->account_id],
                [
                    'role' => $invite->role ?? 'MEMBER',
                    'status' => 'PENDING',
                    'updated_at' => now(),
                    // nếu là dòng mới cần cả created_at
                    'created_at' => $member ? $member->created_at : now(),
                ]
            );

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
        // chỉ kiểm tra ghế ACTIVE
        $used = \DB::table('org_members')
            ->where('org_id', $org->org_id)
            ->where('status', 'ACTIVE')
            ->count();
        if ($used >= $org->seats_limit) {
            return redirect()->route('settings.company')
                ->withErrors(['msg' => 'Tổ chức đã hết ghế.']);
        }

        // Cập nhật trạng thái ACTIVE (nếu chưa có dòng thì tạo mới luôn)
        \DB::table('org_members')->updateOrInsert(
            ['org_id' => $org->org_id, 'account_id' => $user->account_id],
            [
                'role' => $invite->role ?? 'MEMBER',
                'status' => 'ACTIVE',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $invite->update(['status' => 'ACCEPTED']);


        return redirect()->route('settings.company')->with('ok', 'Bạn đã gia nhập doanh nghiệp: ' . $org->name);
    }

    public function company(Request $r)
    {
        $account = $r->user()->loadMissing(['type', 'profile']);
        $isBusiness = ($account->type?->code) === 'BUSS';

        $org = Org::where('owner_account_id', $account->account_id)->first();

        // Thành viên đã join
        $members = collect();
        $usedSeats = 0;
        if ($org) {
            $members = DB::table('org_members as om')
                ->join('accounts as a', 'a.account_id', '=', 'om.account_id')
                ->leftJoin('profiles as p', 'p.account_id', '=', 'a.account_id')
                ->select('om.role', 'om.created_at as joined_at', 'a.email', 'p.fullname')
                ->where('om.org_id', $org->org_id)
                ->orderByDesc('om.created_at')
                ->get();

            $usedSeats = DB::table('org_members')->where('org_id', $org->org_id)->count();
        }

        // 👇 Lời mời còn "pending" (case-insensitive) + còn hạn
        $pendingInvites = collect();
        if ($org) {
            $pendingInvites = OrgInvitation::query()
                ->where('org_id', $org->org_id)
                ->whereRaw('LOWER(status) = ?', ['pending'])
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                // Lấy thêm thông tin người được mời (nếu mời theo username/account_id)
                ->leftJoin('accounts as a', 'a.account_id', '=', 'org_invitations.invitee_account_id')
                ->leftJoin('profiles as p', 'p.account_id', '=', 'a.account_id')
                ->select(
                    'org_invitations.*',
                    'a.email as invitee_email',
                    'p.fullname as invitee_fullname',
                    'p.username as invitee_username'
                )
                ->orderByDesc('org_invitations.created_at')
                ->get();
        }

        return view('settings.company', compact(
            'account',
            'isBusiness',
            'org',
            'members',
            'pendingInvites',
            'usedSeats'
        ));
    }
    public function removeMember(Request $r, int $org, int $account)
    {
        $me = $r->user();
        $orgRow = Org::where('org_id', $org)
            ->where('owner_account_id', $me->account_id) // chỉ Owner mới gỡ
            ->first();

        if (!$orgRow) {
            return back()->withErrors(['msg' => 'Bạn không có quyền với tổ chức này.']);
        }

        $member = DB::table('org_members')
            ->where('org_id', $org)
            ->where('account_id', $account)
            ->first();

        if (!$member) {
            return back()->withErrors(['msg' => 'Không tìm thấy thành viên.']);
        }
        if ($member->role === 'OWNER') {
            return back()->withErrors(['msg' => 'Không thể xoá Chủ sở hữu.']);
        }

        // Xoá membership
        DB::table('org_members')
            ->where('org_id', $org)
            ->where('account_id', $account)
            ->delete();

        // Nếu có lời mời pending theo email -> chuyển CANCELLED (optional)
        $acc = \App\Models\Account::find($account);
        if ($acc) {
            DB::table('org_invitations')
                ->where('org_id', $org)
                ->where('email', $acc->email)
                ->where('status', 'PENDING')
                ->update(['status' => 'CANCELLED', 'updated_at' => now()]);
        }

        return back()->with('ok', 'Đã xoá thành viên khỏi tổ chức.');
    }

public function submitVerification(Request $r)
{
    $user = $r->user()->loadMissing(['type','profile']);
    if (($user->type?->code) !== 'BUSS') {
        return back()->withErrors(['msg' => 'Chỉ tài khoản Business mới được xác minh doanh nghiệp.']);
    }

    $org = Org::where('owner_account_id', $user->account_id)->first();
    if (!$org) {
        return back()->withErrors(['msg' => 'Bạn chưa có doanh nghiệp để xác minh.']);
    }

    $r->validate([
        '_modal' => 'nullable|string',
        'file'   => [ 'required', File::types(['jpg','jpeg','png','webp','pdf'])->max(10 * 1024) ],
    ]);

    $file = $r->file('file');

    // Upload lên Cloudinary (ảnh + pdf)
    $up = Cloudinary::upload(
        $file->getRealPath(),
        [
            'folder'        => "org_verifications/{$org->org_id}",
            'resource_type' => 'auto', // để nhận cả pdf
        ]
    );

    $secureUrl = $up->getSecurePath();   // https://...
    $publicId  = $up->getPublicId();     // lưu để xóa/transform sau
    $bytes     = $up->getBytes();
    $mime      = $up->getMimeType();
    $ext       = $up->getExtension();

    \DB::transaction(function () use ($org, $user, $secureUrl, $publicId, $mime, $bytes, $ext) {
        OrgVerification::create([
            'org_id'                  => $org->org_id,
            'submitted_by_account_id' => $user->account_id,
            'status'                  => 'PENDING',
            'file_path'               => $publicId,    // lưu public_id
            'file_url'                => $secureUrl,   // nếu có cột này
            'mime_type'               => $mime,
            'file_size'               => $bytes,
            'file_ext'                => $ext,
            'storage_driver'          => 'cloudinary',
        ]);

        \DB::table('orgs')->where('org_id', $org->org_id)
            ->update(['status' => 'PENDING', 'updated_at' => now()]);
    });

    return back()->with('ok', 'Đã gửi hồ sơ xác minh doanh nghiệp (Cloudinary).');
}

}
