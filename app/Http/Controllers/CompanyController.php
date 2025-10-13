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
    $account = $r->user()->loadMissing(['type','profile']);

    // Business nếu: BUSS hoặc là thành viên 1 org (ACTIVE/PENDING)
    $isMember = \DB::table('org_members')
        ->where('account_id', $account->account_id)
        ->whereIn('status', ['ACTIVE','PENDING'])
        ->exists();
    $isBusiness = (($account?->type?->code) === 'BUSS') || $isMember;

    $org = null; $members = collect(); $usedSeats = 0;
    $latestVerification = null; $owner = null; $pendingInvites = collect();
    $isOwner = false;         // <-- CHỈ DÙNG BIẾN NÀY

    if ($isBusiness) {
        // Ưu tiên org mà user là chủ
        $org = \App\Models\Org::where('owner_account_id', $account->account_id)->first();

        // Nếu không phải chủ, lấy org mà user là member (ACTIVE/PENDING) gần nhất
        if (!$org) {
            $mm = \DB::table('org_members as om')
                ->join('orgs as o', 'o.org_id', '=', 'om.org_id')
                ->where('om.account_id', $account->account_id)
                ->whereIn('om.status', ['ACTIVE','PENDING'])
                ->orderByDesc('om.created_at')
                ->select('o.org_id')
                ->first();
            if ($mm) $org = \App\Models\Org::find($mm->org_id);
        }

        if ($org) {
            // Tính isOwner
            $isOwner = ((int)$org->owner_account_id === (int)$account->account_id);

            // Thông tin chủ sở hữu (để hiển thị đúng tên chủ thay vì user hiện tại)
            $owner = \App\Models\Account::with('profile')->find($org->owner_account_id);

            // Danh sách thành viên của org
            $members = \DB::table('org_members as om')
                ->join('accounts as a', 'a.account_id', '=', 'om.account_id')
                ->leftJoin('profiles as p', 'p.account_id', '=', 'a.account_id')
                ->where('om.org_id', $org->org_id)
                ->select(
                    'a.account_id', 'a.email',
                    'p.fullname',
                    'om.role', 'om.status',
                    'om.created_at as joined_at', 'om.updated_at'
                )
                ->orderByRaw("FIELD(om.status,'PENDING','ACTIVE')")
                ->orderByRaw("FIELD(om.role,'OWNER','ADMIN','MANAGER','MEMBER','BILLING')")
                ->orderBy('p.fullname')
                ->get();

            // Ghế ACTIVE
            $usedSeats = $members->where('status', 'ACTIVE')->count();

            // Hồ sơ xác minh gần nhất
            $latestVerification = \DB::table('org_verifications')
                ->where('org_id', $org->org_id)
                ->latest('created_at')
                ->first();

            // Chỉ chủ sở hữu mới thấy danh sách lời mời đang chờ
            if ($isOwner) {
                $pendingInvites = \DB::table('org_invitations as oi')
                    ->where('oi.org_id', $org->org_id)
                    ->where('oi.status', 'PENDING')
                    ->where(function ($q) {
                        $q->whereNull('oi.expires_at')->orWhere('oi.expires_at', '>', now());
                    })
                    ->leftJoin('accounts as a', function ($join) {
                        $join->on(
                            \DB::raw('a.email COLLATE utf8mb4_unicode_ci'),
                            '=',
                            \DB::raw('oi.email COLLATE utf8mb4_unicode_ci')
                        );
                    })
                    ->leftJoin('profiles as p', 'p.account_id', '=', 'a.account_id')
                    ->select(
                        'oi.*',
                        'a.email as invitee_email',
                        'p.fullname as invitee_fullname',
                        'p.username as invitee_username'
                    )
                    ->orderByDesc('oi.created_at')
                    ->get();
            }
        }
    }

    return view('settings.company', compact(
        'account',
        'isBusiness',
        'org',
        'members',
        'usedSeats',
        'latestVerification',
        'owner',
        'pendingInvites',
        'isOwner' // <-- nhớ truyền
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
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
         $exists = DB::table('box_chat')->where('org_id', $org->org_id)->exists();
        if (!$exists) {
            DB::table('box_chat')->insert([
                'name'       => 'Nhóm doanh nghiệp ' . Str::limit($org->name, 230),
                'type'       => 3,              // 3 = nhóm doanh nghiệp
                'receiver_id'=> null,
                'sender_id'  => null,
                'job_id'     => null,
                'org_id'     => $org->org_id,   // liên kết org
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
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
        'org_id'   => 'required|integer',
        'username' => ['required','string','max:255','regex:/^[A-Za-z0-9._-]+$/'],
        '_modal'   => 'nullable|string',
    ]);

    $me = $r->user()->loadMissing('type');
    if (($me->type?->code) !== 'BUSS') {
        return back()->withErrors(['msg' => 'Chỉ Business mới được mời thành viên.']);
    }

    // Chỉ OWNER của org mới được mời
    $org = Org::where('org_id', $r->org_id)
        ->where('owner_account_id', $me->account_id)
        ->first();

    if (!$org) {
        return back()->withErrors(['msg' => 'Bạn không có quyền với tổ chức này.'])->withInput();
    }

    // Lấy account theo username (case-insensitive an toàn với collation)
    $username = trim($r->username);
    $target = Account::query()
        ->join('profiles as p', 'p.account_id', '=', 'accounts.account_id')
        ->whereRaw('LOWER(p.username) = ?', [mb_strtolower($username)])
        ->select('accounts.*') // tránh lấy trùng cột
        ->first();

    if (!$target) {
        return back()->withErrors(['username' => 'Không tìm thấy username này.'])->withInput();
    }

    // Không mời chính mình
    if ((int) $target->account_id === (int) $me->account_id) {
        return back()->withErrors(['username' => 'Bạn là chủ tổ chức này.'])->withInput();
    }

    // ---- CHẶN NẾU USER ĐÃ THUỘC DOANH NGHIỆP KHÁC ----
    // 1) Người này là OWNER của một org khác
    $ownerOrg = Org::where('owner_account_id', $target->account_id)->first();
    if ($ownerOrg && (int) $ownerOrg->org_id !== (int) $org->org_id) {
        return back()->withErrors([
            'username' => "Tài khoản này đang thuộc doanh nghiệp khác."
        ])->withInput();
    }

    // 2) Người này đã là member ACTIVE/PENDING ở org khác
    $otherMembership = \DB::table('org_members')
        ->where('account_id', $target->account_id)
        ->whereIn('status', ['ACTIVE','PENDING'])
        ->where('org_id', '<>', $org->org_id)
        ->first();

    if ($otherMembership) {
        $other = Org::find($otherMembership->org_id);
        $otherName = $other?->name ? "{$other->name} (#{$other->org_id})" : "#{$otherMembership->org_id}";
        return back()->withErrors([
            'username' => "Người này đã thuộc doanh nghiệp khác."
        ])->withInput();
    }

    // ---- NẾU ĐÃ Ở TRONG CÙNG ORG NÀY THÌ BÁO LẠI ----
    $sameOrgMember = \DB::table('org_members')
        ->where('org_id', $org->org_id)
        ->where('account_id', $target->account_id)
        ->first();

    if ($sameOrgMember && $sameOrgMember->status === 'ACTIVE') {
        return back()->withErrors(['username' => 'Người này đã là thành viên của tổ chức.'])->withInput();
    }
    if ($sameOrgMember && $sameOrgMember->status === 'PENDING') {
        return back()->withErrors(['username' => 'Bạn đã mời người này, đang chờ xác nhận.'])->withInput();
    }

    // ---- TẠO/ TÁI SỬ DỤNG INVITE CÒN HẠN ----
    $invite = OrgInvitation::where('org_id', $org->org_id)
        ->where('email', $target->email)
        ->where('status', 'PENDING')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->first();

    if (!$invite) {
        $invite = OrgInvitation::create([
            'org_id'     => $org->org_id,
            'email'      => $target->email,
            'role'       => 'MEMBER',
            'token'      => Str::random(48),
            'expires_at' => now()->addDays(7),
            'status'     => 'PENDING',
        ]);

        // KHÔNG đếm ghế ở bước mời; chỉ đếm khi accept
        \DB::table('org_members')->updateOrInsert(
            ['org_id' => $org->org_id, 'account_id' => $target->account_id],
            [
                'role'       => $invite->role ?? 'MEMBER',
                'status'     => 'PENDING',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    // Gửi email mời
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
    $user = $r->user()->loadMissing(['type', 'profile']);
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

    try {
        // Upload Cloudinary (cho ảnh & pdf)
        $upload = Cloudinary::uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder'        => "org_verifications/{$org->org_id}",
                'resource_type' => 'auto',
            ]
        );

        // Lấy dữ liệu cần lưu
        $secureUrl = $upload['secure_url'] ?? null;
        $publicId  = $upload['public_id'] ?? null;
        $bytes     = $upload['bytes'] ?? null;

        // ✅ mime chính xác để Blade nhận diện preview: "image/jpeg", "image/png", ...
        $mime      = $file->getMimeType();
        $ext       = strtolower($file->getClientOriginalExtension());

        DB::transaction(function () use ($org, $user, $secureUrl, $publicId, $mime, $bytes, $ext) {
            OrgVerification::create([
                'org_id'                  => $org->org_id,
                'submitted_by_account_id' => $user->account_id,
                'status'                  => 'PENDING',
                'file_path'               => $publicId,   // lưu public_id
                'file_url'                => $secureUrl,  // link trực tiếp (nếu có)
                'mime_type'               => $mime,       // ví dụ: image/jpeg
                'file_size'               => $bytes,
                'file_ext'                => $ext,
                'storage_driver'          => 'cloudinary',
            ]);

            DB::table('orgs')
                ->where('org_id', $org->org_id)
                ->update(['status' => 'PENDING', 'updated_at' => now()]);
        });

        return back()->with('ok', '✅ Đã gửi hồ sơ xác minh doanh nghiệp (Cloudinary).');

    } catch (\Throwable $e) {
        \Log::error('Cloudinary upload error', ['msg' => $e->getMessage()]);
        return back()->withErrors(['msg' => 'Upload thất bại: ' . $e->getMessage()]);
    }
}

// app/Http/Controllers/CompanyController.php

public function leaveOrg(Request $r, int $org)
{
    $user = $r->user();

    // membership hiện tại của user
    $member = \DB::table('org_members')
        ->where('org_id', $org)
        ->where('account_id', $user->account_id)
        ->first();

    if (!$member) {
        return back()->withErrors(['msg' => 'Bạn không thuộc tổ chức này.']);
    }

    if (strtoupper($member->role) === 'OWNER') {
        return back()->withErrors(['msg' => 'Chủ sở hữu không thể rời tổ chức.']);
    }

    // Xoá membership
    \DB::table('org_members')
        ->where('org_id', $org)
        ->where('account_id', $user->account_id)
        ->delete();

    // Option: nếu có lời mời đang pending theo email -> huỷ
    \DB::table('org_invitations')
        ->where('org_id', $org)
        ->where('email', $user->email)
        ->where('status', 'PENDING')
        ->update(['status' => 'CANCELLED', 'updated_at' => now()]);

    return redirect()->route('settings.company')->with('ok', 'Bạn đã rời khỏi doanh nghiệp.');
}


}
