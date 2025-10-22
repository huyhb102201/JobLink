<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
class ReviewController extends Controller
{
    public function store(Request $r)
    {
        $data = $r->validate([
            'reviewee_id' => ['required', 'integer', 'exists:profiles,profile_id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $meProfile = $r->user()->profile;
        if (!$meProfile) {
            return response()->json(['ok' => false, 'message' => 'Bạn chưa có hồ sơ để đánh giá.'], 422);
        }
        if ((int) $data['reviewee_id'] === (int) $meProfile->profile_id) {
            return response()->json(['ok' => false, 'message' => 'Bạn không thể tự đánh giá chính mình.'], 422);
        }

        $review = Review::create([
            'reviewer_id' => $meProfile->profile_id,
            'reviewee_id' => (int) $data['reviewee_id'],
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'] ?? null,
            'created_at' => now(),
        ])->load('reviewerProfile:profile_id,fullname,username');

        // Tính lại avg + count cho reviewee
        $avg = (float) Review::where('reviewee_id', $data['reviewee_id'])->avg('rating');
        $count = (int) Review::where('reviewee_id', $data['reviewee_id'])->count();

        // ReviewController@store
        return response()->json([
            'ok' => true,
            'message' => 'Đã gửi đánh giá!',
            'review' => [
                'fullname' => $review->reviewerProfile->fullname ?? 'Ẩn danh',
                'username' => $review->reviewerProfile->username ?? null,
                'rating' => (int) $review->rating,
                'comment' => (string) ($review->comment ?? ''),
                'created_at' => now()->format('d/m/Y H:i'),
            ],
            'avg_rating' => round($avg, 1),     // <— dùng cái này để hiển thị
            'review_count' => $count,             // <— và cái này
        ]);

    }
public function destroy($id)
{
    $review = Review::with('revieweeProfile')->findOrFail($id);

    // Lấy chủ sở hữu profile (account_id) từ reviewee_id
    $ownerAccountId = optional($review->revieweeProfile)->account_id;

    if (Auth::id() !== $ownerAccountId /* && !Auth::user()->isAdmin() */) {
        return response()->json(['ok' => false, 'message' => 'Không có quyền xóa đánh giá này.'], 403);
    }

    $review->isDeleted = 1;
    $review->save();

    // TÍNH LẠI sau khi xoá
    $avg = Review::where('reviewee_id', $review->reviewee_id)
        ->where('isDeleted', 0)
        ->avg('rating');
    $cnt = Review::where('reviewee_id', $review->reviewee_id)
        ->where('isDeleted', 0)
        ->count();

    return response()->json([
        'ok'           => true,
        'message'      => 'Đã xóa đánh giá thành công!',
        'avg_rating'   => round((float)$avg, 1),
        'review_count' => $cnt,
        'review_id'    => $review->review_id,
    ]);
}

}
