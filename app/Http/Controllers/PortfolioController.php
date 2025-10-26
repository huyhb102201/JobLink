<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File as FileRule;
use Cloudinary\Api\Upload\UploadApi;
use App\Models\Review;
use App\Models\Skill;
class PortfolioController extends Controller
{
    public function index()
    {
        return view('portfolios.index');
    }



    public function show($username)
    {
        $profile = Profile::with('account')
            ->where('username', $username)
            ->firstOrFail();

        $account = $profile->account->loadMissing('socialAccounts');

        // Jobs
        $jobs = $account->jobs()
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->latest()
            ->get();

        $stats = [
            'total_jobs' => $jobs->count(),
            'completed_jobs' => $jobs->where('status', 'completed')->count(),
            'ongoing_jobs' => $jobs->where('status', 'ongoing')->count(),
        ];

        // Skills (giữ nguyên logic demo)
        $skillIds = collect($profile->skill_list ?? [])->map(fn($v) => (int) $v)->filter()->unique()->values()->all();
        $skills = collect();
        if ($skillIds) {
            $order = implode(',', $skillIds);
            $skills = \App\Models\Skill::whereIn('skill_id', $skillIds)
                ->orderByRaw("FIELD(skill_id, $order)")
                ->get(['skill_id', 'name'])
                ->map(fn($s) => [
                    'name' => $s->name,
                    'rating' => rand(3, 5),
                    'endorse' => rand(5, 50),
                    'level' => ['Beginner', 'Intermediate', 'Advanced', 'Expert'][rand(1, 3)],
                ]);
        }

        // Reviews (reviewee_id là profile_id)
        $reviews = Review::where('reviewee_id', $profile->profile_id)
            ->where('isDeleted', 0)
            ->with(['reviewerProfile:profile_id,fullname,username'])
            ->latest()
            ->get();

        $avgRating = round((float) $reviews->avg('rating'), 1);
        $reviewCount = $reviews->count();

        /* ===================== ORGS THEO account_id ===================== */
        // DN user sở hữu
        $ownedOrgs = \App\Models\Org::with('owner')
            ->withCount('members')
            ->where('owner_account_id', $account->account_id)
            ->get()
            ->map(function ($o) {
                $o->via_role = 'OWNER';
                $o->member_status = 'ACTIVE';
                return $o;
            });

        // DN user tham gia (qua org_members) — lấy cả role + status
        $memberOrgs = \App\Models\Org::query()
            ->with('owner')
            ->withCount('members')
            ->join('org_members as m', 'm.org_id', '=', 'orgs.org_id')
            ->where('m.account_id', $account->account_id)
            // ->where('m.status', 'ACTIVE') // bật nếu chỉ muốn DN đang active
            ->select('orgs.*', 'm.role as via_role', 'm.status as member_status')
            ->get();

        // Gộp & loại trùng
        $orgs = $ownedOrgs->concat($memberOrgs)->unique('org_id')->values();
        $allSkills = \App\Models\Skill::orderBy('name')->get(['skill_id', 'name']);
        $selectedIds = collect(preg_split('/\s*,\s*/', (string) ($profile->skill ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();
        return view('portfolios.index', compact(
            'profile',
            'allSkills',
            'selectedIds',
            'account',
            'jobs',
            'stats',
            'skills',
            'reviews',
            'avgRating',
            'reviewCount',
            'orgs' // <<=== TRUYỀN RA VIEW
        ));
    }



    public function upload(Request $r)
    {
        $account = Auth::user();

        // Validate thủ công để có thể trả JSON 422 khi AJAX
        $validator = Validator::make($r->all(), [
            'avatar' => [
                'required',
                FileRule::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024), // 5MB (KB)
            ],
        ], [
            'avatar.required' => 'Vui lòng chọn ảnh.',
            'avatar.image' => 'Tệp phải là ảnh.',
            'avatar.max' => 'Ảnh tối đa 5MB.',
            'avatar.mimes' => 'Định dạng hợp lệ: jpg, jpeg, png, webp.',
        ]);

        if ($validator->fails()) {
            if ($r->ajax() || $r->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Dữ liệu không hợp lệ.',
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $file = $r->file('avatar');

        try {
            // Upload lên Cloudinary
            $upload = (new UploadApi())->upload(
                $file->getRealPath(),
                [
                    'folder' => 'avatars/' . $account->account_id,
                    'resource_type' => 'image',
                    // có thể thêm transform mặc định nếu muốn:
                    // 'quality' => 'auto', 'fetch_format' => 'auto'
                ]
            );

            $secureUrl = $upload['secure_url'] ?? null;
            $publicId = $upload['public_id'] ?? null;

            if (!$secureUrl) {
                throw new \RuntimeException('Không nhận được URL từ Cloudinary.');
            }

            DB::table('accounts')
                ->where('account_id', $account->account_id)
                ->update([
                    'avatar_url' => $secureUrl,
                    // 'avatar_public_id' => $publicId, // nếu có cột
                    'updated_at' => now(),
                ]);

            if ($r->ajax() || $r->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'url' => $secureUrl,
                    'message' => '✅ Ảnh đại diện đã được cập nhật.',
                ]);
            }

            return back()->with('ok', '✅ Ảnh đại diện đã được cập nhật.');
        } catch (\Throwable $e) {
            Log::error('Avatar upload error', ['msg' => $e->getMessage()]);
            if ($r->ajax() || $r->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Upload thất bại: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['msg' => 'Upload thất bại: ' . $e->getMessage()]);
        }
    }
    public function updateLocation(Request $r)
    {
        $account = Auth::user();

        // 1) Validate + chuẩn hóa
        $data = $r->validate([
            'location' => ['nullable', 'string', 'max:150'],
        ]);
        $location = isset($data['location']) ? trim((string) $data['location']) : null;
        if ($location === '')
            $location = null;

        try {
            // 2) Cập nhật theo quan hệ hoặc DB thuần
            if (method_exists($account, 'profile')) {
                $profile = $account->profile()->firstOrCreate(['account_id' => $account->account_id]);
                $profile->location = $location;
                $profile->save();
                $savedLocation = $profile->location;
            } else {
                DB::table('profiles')->updateOrInsert(
                    ['account_id' => $account->account_id],
                    ['location' => $location, 'updated_at' => now()]
                );
                // Lấy lại để trả về cho chắc chắn
                $savedLocation = DB::table('profiles')
                    ->where('account_id', $account->account_id)
                    ->value('location');
            }

            // 3) Trả JSON nếu là AJAX, còn lại fallback về back()
            if ($r->ajax() || $r->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'location' => $savedLocation,
                    'message' => 'Cập nhật địa chỉ thành công.',
                ]);
            }

            return back()->with('ok', 'Đã cập nhật địa chỉ.');
        } catch (\Throwable $e) {
            report($e);

            if ($r->ajax() || $r->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật địa chỉ.',
                ], 500);
            }

            return back()->withErrors(['location' => 'Có lỗi xảy ra khi cập nhật địa chỉ.']);
        }
    }

    public function updateSkills(Profile $profile, Request $request)
    {
        // Chỉ chủ sở hữu profile mới được sửa
        abort_unless(Auth::id() === $profile->account_id, 403);

        // skills[] có thể là số (skill_id) hoặc chuỗi (tên mới)
        $tokens = $request->input('skills', []);

        $ids = [];
        foreach ($tokens as $t) {
            // nếu là id số -> dùng luôn
            if (ctype_digit((string)$t)) {
                $ids[] = (int)$t;
                continue;
            }
            // nếu là tên -> tạo mới nếu chưa có
            $name = trim((string)$t);
            if ($name === '') continue;
            $skill = Skill::firstOrCreate(['name' => $name]);
            $ids[] = $skill->skill_id;
        }

        // chuẩn hóa + giới hạn tối đa (vd 20)
        $ids = collect($ids)->map(fn($v)=>(int)$v)->filter()->unique()->take(20)->values()->all();

        // lưu lại về CSV "14,6,10"
        $profile->skill = implode(',', $ids);
        $profile->save();

        // trả về để UI vẽ lại
        $skills = Skill::whereIn('skill_id', $ids)
            ->orderBy('name')->get(['skill_id','name']);

        return response()->json([
            'ok'      => true,
            'message' => 'Đã cập nhật kỹ năng.',
            'skills'  => $skills,
        ]);
    }

}
