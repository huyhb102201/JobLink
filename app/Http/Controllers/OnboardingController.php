<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    // =======================
    // B1: form nhập full name + username (đã thêm username)
    // =======================
    public function showName()
    {
        $user = Auth::user();

        // tạo profile nếu chưa có
        $profile = DB::table('profiles')->where('account_id', $user->account_id)->first();

        // Nếu profile đã có username -> dùng luôn; ngược lại đề xuất từ email hoặc name
        $suggested = $profile?->username ?: $this->suggestUsernameFromEmailOrName($user->email, $user->name);

        return view('auth.onb-name', [
            'profile'   => $profile,
            'user'      => $user,
            'suggested' => $suggested, // <-- truyền ra view để điền sẵn input username
        ]);
    }

    public function storeName(Request $request)
    {
        $user = Auth::user();

        // Lấy profile hiện có (nếu có) để ignore unique khi update
        $existing = DB::table('profiles')->where('account_id', $user->account_id)->first();

        $request->merge([
            // nếu vì lý do gì user không nhập username (ví dụ back/refresh),
            // tự đề xuất lại để tránh null
            'username' => $request->input('username')
                ?: ($existing->username ?? $this->suggestUsernameFromEmailOrName($user->email, $user->name)),
        ]);

        // rule unique: ignore bản ghi hiện tại nếu đang update
        $uniqueRule = Rule::unique('profiles', 'username');
        if ($existing?->profile_id) {
            $uniqueRule = $uniqueRule->ignore($existing->profile_id, 'profile_id');
        }

        $data = $request->validate([
            'fullname' => ['required','string','max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_.]+$/', // chỉ chữ, số, _ và .
                $uniqueRule,
            ],
        ], [
            'username.regex' => 'Username chỉ gồm chữ, số, dấu _ hoặc .',
        ]);

        // upsert vào profiles
        if ($existing) {
            DB::table('profiles')->where('profile_id', $existing->profile_id)->update([
                'fullname'   => $data['fullname'],
                'username'   => $data['username'], // lưu không có ký tự @
                'email'      => $user->email,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('profiles')->insert([
                'account_id' => $user->account_id,
                'email'      => $user->email,
                'fullname'   => $data['fullname'],
                'username'   => $data['username'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Nếu là freelancer → sang bước chọn skill; nếu là client → về home
        $freelancerTypeId = DB::table('account_types')
            ->where('code', 'F_BASIC')
            ->value('account_type_id');

        if ((int)$user->account_type_id === (int)$freelancerTypeId) {
            return redirect()->route('onb.skills.show');
        }
        return redirect()->route('home')->with('status','Cập nhật họ tên & username xong!');
    }

    // =======================
    // B2: form chọn skill + mô tả (chỉ cho freelancer)
    // =======================
    public function showSkills()
    {
        $user = Auth::user();

        // chỉ cho freelancer (F_BASIC)
        $freelancerTypeId = DB::table('account_types')
            ->where('code', 'F_BASIC')
            ->value('account_type_id');
        if ((int)$user->account_type_id !== (int)$freelancerTypeId) {
            return redirect()->route('home');
        }

        // Xác định khóa chính của bảng skills: skill_id hoặc id
        $skillKey = Schema::hasColumn('skills', 'skill_id') ? 'skill_id' : 'id';

        // Lấy danh sách kỹ năng, alias thành "id" cho đồng nhất với view
        $skills = DB::table('skills')->select([$skillKey.' as id', 'name'])
                    ->orderBy('name')->get();

        $profile  = DB::table('profiles')->where('account_id', $user->account_id)->first();
        $selected = collect(explode(',', (string)($profile->skill ?? '')))
                    ->filter()->map(fn($v)=>(int)$v)->values()->all();

        return view('auth.onb-skills', [
            'skills'   => $skills,
            'profile'  => $profile,
            'selected' => $selected,
        ]);
    }

    public function storeSkills(Request $request)
    {
        $user = Auth::user();

        // Xác định cột khóa
        $skillKey = Schema::hasColumn('skills', 'skill_id') ? 'skill_id' : 'id';

        // Lấy list id hợp lệ từ DB, dùng Rule::in để không phụ thuộc tên cột
        $validIds = DB::table('skills')->pluck($skillKey)->map(fn($v)=>(int)$v)->all();

        $data = $request->validate([
            'skills'      => ['required','array','min:1'],        // bắt buộc chọn ít nhất 1
            'skills.*'    => ['integer', Rule::in($validIds)],    // chỉ chấp nhận ID có thật
            'description' => ['nullable','string','max:1000'],
        ],[
            'skills.required' => 'Vui lòng chọn ít nhất 1 kỹ năng.',
            'skills.min'      => 'Vui lòng chọn ít nhất 1 kỹ năng.',
        ]);

        $ids    = collect($data['skills'])->unique()->values()->all();
        $idsCsv = implode(',', $ids);

        $profile = DB::table('profiles')->where('account_id', $user->account_id)->first();

        if ($profile) {
            DB::table('profiles')->where('profile_id', $profile->profile_id)->update([
                'skill'       => $idsCsv,                   // lưu "1,5,9"
                'description' => $data['description'] ?? null,
                'updated_at'  => now(),
            ]);
        } else {
            DB::table('profiles')->insert([
                'account_id'  => $user->account_id,
                'email'       => $user->email,
                'fullname'    => $user->name ?? null,
                'skill'       => $idsCsv,
                'description' => $data['description'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        return redirect()->route('home')->with('status', 'Hồ sơ freelancer đã sẵn sàng!');
    }

    // =======================
    // Helper: đề xuất username từ email hoặc name, đảm bảo unique
    // =======================
    protected function suggestUsernameFromEmailOrName(?string $email, ?string $name): string
    {
        // Lấy phần trước @ nếu có email
        $base = null;
        if ($email && str_contains($email, '@')) {
            $base = explode('@', $email)[0];
        }

        // Fallback từ name nếu cần
        if (!$base && $name) {
            $base = Str::slug($name, '_'); // "Hồ Gia Huy" -> "ho_gia_huy"
        }

        if (!$base) {
            $base = 'user';
        }

        // Chuẩn hoá: bỏ dấu, lower, chỉ giữ a-z0-9_. và cắt độ dài
        $base = Str::ascii($base);
        $base = strtolower($base);
        $base = preg_replace('/[^a-z0-9_.]+/','', $base) ?: 'user';
        $base = trim($base, '._');
        $base = substr($base, 0, 30); // chừa chỗ cho hậu tố số

        // Nếu chưa tồn tại -> dùng luôn
        $exists = DB::table('profiles')->where('username', $base)->exists();
        if (!$exists) return $base;

        // Thử thêm hậu tố số: name1, name2, ...
        for ($i = 1; $i <= 9999; $i++) {
            $candidate = substr($base, 0, 30 - strlen((string)$i)) . $i;
            if (!DB::table('profiles')->where('username', $candidate)->exists()) {
                return $candidate;
            }
        }

        // fallback cuối
        return $base . Str::random(3);
    }
}
