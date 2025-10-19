<?php
namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        /** @var \App\Models\Account $account */
        $account = $request->user();

        $profile = $account->profile()->firstOrCreate([
            'account_id' => $account->account_id,
        ], [
            'fullname' => $account->name,
            'email'    => $account->email,
        ]);

        return view('profile.show', compact('account','profile'));
    }

    public function update(UpdateProfileRequest $request)
    {
        $account = $request->user();
        $profile = $account->profile()->firstOrCreate(['account_id'=>$account->account_id]);

        $data = $request->validated();

        // Chuẩn hóa skill: "php,  laravel ,react" -> "php, laravel, react"
        $skillCsv = collect(preg_split('/[,;]/', (string)($data['skill'] ?? '')))
            ->map(fn($s)=>trim($s))
            ->filter()
            ->implode(', ');

        $profile->update([
            'fullname'    => $data['fullname'],
            'email'       => $data['email'] ?? null,
            'description' => $data['description'] ?? null,
            'skill'       => $skillCsv ?: null,
        ]);

        return back()->with('ok','Đã lưu hồ sơ');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        $account = $request->user();

        // lưu file vào storage/public/avatars
        $path = $request->file('avatar')->store('avatars','public');

        // xóa ảnh cũ nếu là file local
        if ($account->avatar_url && str_starts_with($account->avatar_url, 'storage/')) {
            Storage::disk('public')->delete(str_replace('storage/','', $account->avatar_url));
        }

        // lưu dạng URL public để Blade dùng trực tiếp
        $account->update(['avatar_url' => 'storage/'.$path]);

        return back()->with('ok','Đã cập nhật ảnh đại diện');
    }
    public function updateAbout(Request $request)
    {
        // Validate
        $data = $request->validate([
            'description' => ['nullable','string','max:5000'],
        ]);

        // Lấy profile theo user hiện tại (giả sử khóa ngoại là account_id / user_id)
        $user = Auth::user();
        $profile = $user->profile; // nếu quan hệ là hasOne Profile

        if (!$profile) {
            return response()->json([
                'ok' => false,
                'message' => 'Không tìm thấy hồ sơ để cập nhật.',
            ], 404);
        }

        // (Khuyến nghị) sanitize để tránh XSS — nếu bạn có HTMLPurifier/clean()
        $cleanHtml = $data['description'] ?? '';
        if (function_exists('clean')) {
            // preset 'user_profile' là ví dụ: bạn tự cấu hình whitelist
            $cleanHtml = clean($cleanHtml, 'user_profile');
        }

        $profile->description = $cleanHtml;
        $profile->save();

        // Trả JSON để JS cập nhật ngay trên UI
        return response()->json([
            'ok'      => true,
            'message' => 'Đã cập nhật giới thiệu.',
            'html'    => $cleanHtml, // nội dung đã sanitize
        ]);
    }
}
