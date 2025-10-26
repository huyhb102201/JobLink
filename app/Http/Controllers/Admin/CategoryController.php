<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\AdminLogService;

class CategoryController extends Controller
{
    /**
     * Hiển thị danh sách danh mục
     */
    public function index(Request $request)
    {
        // Lấy danh sách danh mục chưa bị xóa với số lượng jobs
        $categories = JobCategory::notDeleted()
            ->withCount('jobs')
            ->orderBy('category_id', 'asc')
            ->get();

        // Thống kê
        $totalCategories = JobCategory::notDeleted()->count();
        $totalJobs = DB::table('jobs')->count();

        return view('admin.categories.index', compact(
            'categories',
            'totalCategories',
            'totalJobs'
        ));
    }

    /**
     * Thêm danh mục mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục',
            'name.unique' => 'Tên danh mục này đã tồn tại',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $category = JobCategory::create([
                'name' => $request->name,
                'description' => $request->description ?? '', // Set empty string nếu null
                'isDeleted' => 0,
            ]);

            // Log admin action
            AdminLogService::logCreate(
                'JobCategory',
                $category->category_id,
                "Tạo danh mục mới: {$category->name}",
                ['name' => $category->name, 'description' => $category->description]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thêm danh mục thành công!',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo danh mục: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại.'
            ], 500);
        }
    }

    /**
     * Cập nhật danh mục
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name,' . $id . ',category_id',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục',
            'name.unique' => 'Tên danh mục này đã tồn tại',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $category = JobCategory::where('category_id', $id)->notDeleted()->first();
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $category->update([
                'name' => $request->name,
                'description' => $request->description ?? '', // Set empty string nếu null
            ]);

            // Log admin action
            AdminLogService::logUpdate(
                'JobCategory',
                $category->category_id,
                "Cập nhật danh mục: {$category->name}",
                ['name' => $category->name, 'description' => $category->description]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật danh mục thành công!',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật danh mục: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại.'
            ], 500);
        }
    }

    /**
     * Xóa mềm danh mục (soft delete)
     */
    public function destroy($id)
    {
        $category = JobCategory::where('category_id', $id)->notDeleted()->first();
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Kiểm tra xem có jobs nào đang sử dụng danh mục này không
            $jobsCount = $category->jobs()->count();
            if ($jobsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa vì có {$jobsCount} công việc đang sử dụng danh mục này."
                ], 422);
            }

            // Soft delete: cập nhật isDeleted = 1
            $category->update(['isDeleted' => 1]);

            // Log admin action
            AdminLogService::logDelete(
                'JobCategory',
                $category->category_id,
                "Xóa danh mục: {$category->name}",
                ['name' => $category->name]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa danh mục thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa danh mục: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại.'
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết danh mục
     */
    public function show($id)
    {
        $category = JobCategory::where('category_id', $id)
            ->notDeleted()
            ->withCount('jobs')
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'category' => $category
        ]);
    }
}
