<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkillController extends Controller
{
    /**
     * Hiển thị danh sách kỹ năng
     */
    public function index(Request $request)
    {
        // Load TẤT CẢ kỹ năng một lần
        $allSkills = DB::table('skills')
            ->select('skill_id', 'name')
            ->orderBy('skill_id', 'asc')
            ->get();

        // Đếm số người dùng cho mỗi kỹ năng từ bảng profiles
        $userCounts = [];
        
        // Lấy tất cả profiles có skill
        $profiles = DB::table('profiles')
            ->whereNotNull('skill')
            ->where('skill', '!=', '')
            ->select('skill')
            ->get();
        
        // Đếm số lần xuất hiện của mỗi skill_id
        foreach ($profiles as $profile) {
            if (!empty($profile->skill)) {
                // Split skill CSV (ví dụ: "1,5,10" -> [1, 5, 10])
                $skillIds = array_map('trim', explode(',', $profile->skill));
                
                foreach ($skillIds as $skillId) {
                    if (!empty($skillId) && is_numeric($skillId)) {
                        $skillId = (int)$skillId;
                        if (!isset($userCounts[$skillId])) {
                            $userCounts[$skillId] = 0;
                        }
                        $userCounts[$skillId]++;
                    }
                }
            }
        }

        // Thêm user_count vào mỗi skill
        foreach ($allSkills as $skill) {
            $skill->user_count = $userCounts[$skill->skill_id] ?? 0;
        }

        return view('admin.skills.index', [
            'allSkills' => $allSkills,
            'totalSkills' => $allSkills->count(),
        ]);
    }

    /**
     * Lấy chi tiết kỹ năng
     */
    public function show($id)
    {
        try {
            $skill = DB::table('skills')
                ->where('skill_id', $id)
                ->first();

            if (!$skill) {
                return response()->json(['error' => 'Kỹ năng không tồn tại'], 404);
            }

            // Đếm số người dùng có kỹ năng này (nếu bảng tồn tại)
            $userCount = 0;
            try {
                $userCount = DB::table('user_skills')
                    ->where('skill_id', $id)
                    ->count();
            } catch (\Exception $e) {
                // Bảng user_skills không tồn tại, bỏ qua
            }

            return response()->json([
                'skill' => $skill,
                'user_count' => $userCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm kỹ năng mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skills,name',
        ], [
            'name.required' => 'Tên kỹ năng không được để trống',
            'name.unique' => 'Kỹ năng này đã tồn tại',
            'name.max' => 'Tên kỹ năng không được quá 255 ký tự',
        ]);

        $skillId = DB::table('skills')->insertGetId([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm kỹ năng thành công',
            'skill_id' => $skillId,
        ]);
    }

    /**
     * Cập nhật kỹ năng
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:skills,name,' . $id . ',skill_id',
            ], [
                'name.required' => 'Tên kỹ năng không được để trống',
                'name.unique' => 'Kỹ năng này đã tồn tại',
                'name.max' => 'Tên kỹ năng không được quá 255 ký tự',
            ]);

            $updated = DB::table('skills')
                ->where('skill_id', $id)
                ->update([
                    'name' => $request->name,
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kỹ năng không tồn tại',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật kỹ năng thành công',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating skill: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa kỹ năng
     */
    public function destroy($id)
    {
        try {
            // Kiểm tra xem có người dùng nào đang sử dụng kỹ năng này không (nếu bảng tồn tại)
            $userCount = 0;
            try {
                $userCount = DB::table('user_skills')
                    ->where('skill_id', $id)
                    ->count();
            } catch (\Exception $e) {
                // Bảng user_skills không tồn tại, bỏ qua
            }

            if ($userCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa kỹ năng này vì có {$userCount} người dùng đang sử dụng",
                ], 400);
            }

            $deleted = DB::table('skills')
                ->where('skill_id', $id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kỹ năng không tồn tại',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa kỹ năng thành công',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting skill: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }
}
