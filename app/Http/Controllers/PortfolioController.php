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

        $account = $profile->account;

        // Lấy tất cả jobs (sắp xếp mới nhất trước)
        $jobs = $account->jobs()->whereNotIn('status', ['pending', 'cancelled'])->latest()->get();

        $stats = [
            'total_jobs' => $jobs->count(),
            'completed_jobs' => $jobs->where('status', 'completed')->count(),
            'ongoing_jobs' => $jobs->where('status', 'ongoing')->count(),
        ];

        return view('portfolios.index', compact('profile', 'account', 'jobs', 'stats'));
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

}
