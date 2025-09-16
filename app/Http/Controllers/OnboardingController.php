<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class OnboardingController extends Controller
{
    // B1: form nhập full name
    public function showName()
    {
        $user = Auth::user();
        // tạo profile nếu chưa có
        $profile = DB::table('profiles')->where('account_id', $user->account_id)->first();

        return view('auth.onb-name', ['profile' => $profile, 'user' => $user]);
    }

    public function storeName(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'fullname' => 'required|string|max:255',
        ]);

        // upsert vào profiles
        $existing = DB::table('profiles')->where('account_id', $user->account_id)->first();
        if ($existing) {
            DB::table('profiles')->where('profile_id', $existing->profile_id)
              ->update([
                  'fullname'  => $data['fullname'],
                  'email'     => $user->email,
                  'updated_at'=> now(),
              ]);
        } else {
            DB::table('profiles')->insert([
                'account_id'=> $user->account_id,
                'email'     => $user->email,
                'fullname'  => $data['fullname'],
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        }

        // Nếu là freelancer → sang bước chọn skill; nếu là client → về home
        $freelancerTypeId = DB::table('account_types')
            ->where('code', 'F_BASIC')
            ->value('account_type_id');

        if ((int)$user->account_type_id === (int)$freelancerTypeId) {
            return redirect()->route('onb.skills.show');
        }
        return redirect()->route('home')->with('status','Cập nhật họ tên xong!');
    }

    // B2: form chọn skill + mô tả (chỉ cho freelancer)
    // OnboardingController@showSkills
// app/Http/Controllers/OnboardingController.php

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
}
