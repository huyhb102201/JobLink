<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AdminLogService;

class ReviewController extends Controller
{
    /**
     * Hiển thị danh sách đánh giá
     */
    public function index(Request $request)
    {
        // Lấy danh sách đánh giá chưa bị xóa
        // reviewer_id và reviewee_id là profile_id, không phải account_id
        $reviews = Review::notDeleted()
            ->with(['reviewer.account', 'reviewee.account'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Thống kê tổng quan
        $totalReviews = Review::notDeleted()->count();
        
        // Thống kê theo thời gian
        $todayReviews = Review::notDeleted()
            ->whereDate('created_at', today())
            ->count();
            
        $weekReviews = Review::notDeleted()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
            
        $monthReviews = Review::notDeleted()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // Thống kê theo rating
        $fiveStarReviews = Review::notDeleted()->where('rating', 5)->count();
        $fourStarReviews = Review::notDeleted()->where('rating', 4)->count();
        $threeStarReviews = Review::notDeleted()->where('rating', 3)->count();
        $twoStarReviews = Review::notDeleted()->where('rating', 2)->count();
        $oneStarReviews = Review::notDeleted()->where('rating', 1)->count();

        return view('admin.reviews.index', compact(
            'reviews',
            'totalReviews',
            'todayReviews',
            'weekReviews',
            'monthReviews',
            'fiveStarReviews',
            'fourStarReviews',
            'threeStarReviews',
            'twoStarReviews',
            'oneStarReviews'
        ));
    }

    /**
     * Xem chi tiết đánh giá
     */
    public function show($id)
    {
        $review = Review::where('review_id', $id)
            ->notDeleted()
            ->with(['reviewer.account', 'reviewee.account'])
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đánh giá.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'review' => $review
        ]);
    }

    /**
     * Xóa mềm đánh giá (soft delete)
     * Lưu ý: Chỉ cho phép xem và xóa, KHÔNG cho phép sửa
     */
    public function destroy($id)
    {
        $review = Review::where('review_id', $id)->notDeleted()->first();
        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đánh giá.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Soft delete: cập nhật isDeleted = 1
            $review->update(['isDeleted' => 1]);

            // Log admin action
            AdminLogService::logDelete(
                'Review',
                $review->review_id,
                "Xóa đánh giá ID: {$review->review_id}",
                [
                    'reviewer_id' => $review->reviewer_id,
                    'reviewee_id' => $review->reviewee_id,
                    'rating' => $review->rating
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa đánh giá thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa đánh giá: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại.'
            ], 500);
        }
    }

    /**
     * Xóa nhiều đánh giá cùng lúc
     */
    public function destroyMultiple(Request $request)
    {
        $idsInput = $request->input('ids');
        $ids = [];

        if (is_array($idsInput)) {
            $ids = $idsInput;
        } elseif (is_string($idsInput)) {
            $decoded = json_decode($idsInput, true);
            if (is_array($decoded)) {
                $ids = $decoded;
            } else {
                $ids = explode(',', $idsInput);
            }
        }

        $ids = array_values(array_unique(array_filter(array_map(function ($id) {
            if (is_numeric($id)) {
                return (int) $id;
            }
            return null;
        }, $ids))));

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất một đánh giá để xóa.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $reviews = Review::whereIn('review_id', $ids)->notDeleted()->get();

            if ($reviews->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đánh giá hợp lệ để xóa.'
                ], 404);
            }

            // Soft delete tất cả
            Review::whereIn('review_id', $ids)->update(['isDeleted' => 1]);

            // Log admin action
            AdminLogService::logBulk(
                'delete',
                'Review',
                $ids,
                'Xóa hàng loạt ' . count($ids) . ' đánh giá'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa các đánh giá đã chọn thành công.',
                'removed_ids' => $ids,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa nhiều đánh giá: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa các đánh giá này.'
            ], 500);
        }
    }
}
