<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrgVerification;
use App\Models\Org;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use App\Services\AdminLogService;

class AdminVerificationController extends Controller
{
    public function index(Request $request)
    {
        // Load tất cả verifications một lần với đầy đủ thông tin để preload modal
        $verifications = OrgVerification::with([
            'org:org_id,name,owner_account_id,tax_code,address,phone,email,website',
            'org.owner:account_id,name',
            'submittedByAccount:account_id,name',
            'reviewedByAccount:account_id,name'
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Preload verification details cho JavaScript
        $verificationDetails = [];
        foreach ($verifications as $verification) {
            $statusBadge = match ($verification->status) {
                'PENDING' => '<span class="badge bg-warning">Chờ duyệt</span>',
                'APPROVED' => '<span class="badge bg-success">Đã duyệt</span>',
                'REJECTED' => '<span class="badge bg-danger">Đã từ chối</span>',
                default => '<span class="badge bg-secondary">' . $verification->status . '</span>',
            };

            $org = $verification->org;

            $verificationDetails[$verification->id] = [
                'id' => $verification->id,
                'org_name' => $org->name ?? 'N/A',
                'tax_code' => $org->tax_code ?? 'N/A',
                'address' => $org->address ?? 'N/A',
                'phone' => $org->phone ?? 'N/A',
                'email' => $org->email ?? 'N/A',
                'website' => $org->website ?? 'N/A',
                'submitted_by' => $verification->submittedByAccount->name ?? 'N/A',
                'created_at' => $verification->created_at ? $verification->created_at->format('d/m/Y H:i') : 'N/A',
                'status' => $verification->status,
                'status_badge' => $statusBadge,
                'note' => 'Đơn xét duyệt doanh nghiệp',
                'reviewed_by' => $verification->reviewedByAccount->name ?? null,
                'reviewed_at' => $verification->reviewed_at ? $verification->reviewed_at->format('d/m/Y H:i') : null,
                'review_note' => $verification->review_note,
                'documents_html' => $this->getDocumentsHtml($verification),
            ];
        }

        // Lấy thống kê real-time (không cache để cập nhật ngay lập tức)
        $totalVerifications = OrgVerification::count();
        $pendingVerifications = OrgVerification::where('status', 'PENDING')->count();
        $approvedVerifications = OrgVerification::where('status', 'APPROVED')->count();
        $rejectedVerifications = OrgVerification::where('status', 'REJECTED')->count();

        return view('admin.verifications.list', compact(
            'verifications',
            'verificationDetails',
            'totalVerifications',
            'pendingVerifications',
            'approvedVerifications',
            'rejectedVerifications'
        ));
    }

    public function show(OrgVerification $verification)
    {
        $verification->load(['org.owner', 'submittedByAccount']);
        return view('admin.verifications.show', compact('verification'));
    }

    public function getDetails(OrgVerification $verification)
    {
        try {
            $verification->load(['org.owner', 'submittedByAccount', 'reviewedByAccount']);

            $statusBadge = match ($verification->status) {
                'PENDING' => '<span class="badge bg-warning">Chờ duyệt</span>',
                'APPROVED' => '<span class="badge bg-success">Đã duyệt</span>',
                'REJECTED' => '<span class="badge bg-danger">Đã từ chối</span>',
                default => '<span class="badge bg-secondary">' . $verification->status . '</span>',
            };

            $org = $verification->org;

            return response()->json([
                'success' => true,
                'verification' => [
                    'id' => $verification->id,
                    'org_name' => $org->name ?? 'N/A',
                    'tax_code' => $org->tax_code ?? 'N/A',
                    'address' => $org->address ?? 'N/A',
                    'phone' => $org->phone ?? 'N/A',
                    'email' => $org->email ?? 'N/A',
                    'website' => $org->website ?? 'N/A',
                    'submitted_by' => $verification->submittedByAccount->name ?? 'N/A',
                    'created_at' => $verification->created_at->format('d/m/Y H:i'),
                    'status' => $verification->status,
                    'status_badge' => $statusBadge,
                    'note' => 'Đơn xét duyệt doanh nghiệp',
                    'reviewed_by' => $verification->reviewedByAccount->name ?? null,
                    'reviewed_at' => $verification->reviewed_at ? $verification->reviewed_at->format('d/m/Y H:i') : null,
                    'review_note' => $verification->review_note,
                    'documents_html' => $this->getDocumentsHtml($verification),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading verification details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải thông tin: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getDocumentsHtml($verification)
    {
        // Xác định URL file: ưu tiên file_url, nếu không có thì dùng file_path
        $fileUrl = $verification->file_url;
        $useFileUrl = !empty($fileUrl);

        if (empty($fileUrl) && !empty($verification->file_path)) {
            // Tạo URL từ file_path
            $fileUrl = asset('storage/' . $verification->file_path);
        }

        if (empty($fileUrl)) {
            return '<div class="mt-4">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Không có tài liệu đính kèm
                </div>
            </div>';
        }

        $html = '<div class="mt-4"><h6 class="fw-bold text-primary">Tài liệu đính kèm</h6>';

        // Chỉ kiểm tra file_exists nếu dùng file_path (local storage)
        // Không kiểm tra nếu dùng file_url (external URL)
        if (!$useFileUrl && !empty($verification->file_path)) {
            $filePath = storage_path('app/public/' . $verification->file_path);
            $fileExists = file_exists($filePath);

            if (!$fileExists) {
                $html .= '<div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>File không tồn tại:</strong> ' . $verification->file_path . '
                    <br><small>File có thể đã bị xóa hoặc chưa được upload.</small>
                </div>';
                $html .= '</div>';
                return $html;
            }
        }

        // Xác định loại file để hiển thị phù hợp
        $mimeType = $verification->mime_type ?? '';
        $isImage = strpos($mimeType, 'image/') === 0 || preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $fileUrl);
        $isPdf = strpos($mimeType, 'pdf') !== false || preg_match('/\.pdf$/i', $fileUrl);

        $html .= '<div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Tài liệu xác minh doanh nghiệp</span>
                <a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i> Mở tab mới
                </a>
            </div>
            <div class="card-body p-0">
                <div class="embed-responsive" style="height: 600px; overflow: auto; background: #f5f5f5;">';

        if ($isImage) {
            // Hiển thị ảnh với chức năng zoom
            $html .= '<img src="' . $fileUrl . '" 
                          class="verification-image" 
                          style="max-width: 100%; height: auto; display: block; margin: 0 auto; cursor: zoom-in; transition: transform 0.3s ease;" 
                          alt="Tài liệu xác minh"
                          onclick="toggleImageZoom(this)">';
        } elseif ($isPdf) {
            // Hiển thị PDF
            $html .= '<iframe src="' . $fileUrl . '" 
                            style="width: 100%; height: 100%; border: none;" 
                            type="application/pdf">
                        <p>Trình duyệt không hỗ trợ hiển thị PDF. 
                           <a href="' . $fileUrl . '" target="_blank">Nhấn vào đây để tải xuống</a>
                        </p>
                    </iframe>';
        } else {
            // File khác
            $html .= '<div class="text-center p-5">
                        <i class="fas fa-file fa-5x text-muted mb-3"></i>
                        <p>Không thể xem trước file này.</p>
                        <a href="' . $fileUrl . '" target="_blank" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i> Tải xuống
                        </a>
                    </div>';
        }

        $html .= '</div>
            </div>
            <div class="card-footer text-muted">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Loại: ' . ($verification->mime_type ?? 'N/A') . ' | 
                    Kích thước: ' . ($verification->file_size ? number_format($verification->file_size / 1024, 2) . ' KB' : 'N/A') . '
                    ' . ($verification->file_path ? ' | Đường dẫn: ' . $verification->file_path : '') . '
                </small>
            </div>
        </div>';

        $html .= '</div>';
        return $html;
    }

    public function approve(Request $request, OrgVerification $verification)
    {
        // Kiểm tra nếu đã duyệt hoặc từ chối rồi thì không làm gì nữa
        if ($verification->status !== 'PENDING') {
            return back()->with('error', 'Đơn xét duyệt này không ở trạng thái chờ.');
        }

        DB::beginTransaction();
        try {
            // 1. Cập nhật trạng thái của đơn xét duyệt
            $verification->status = 'APPROVED';
            $verification->review_note = $request->input('review_note', 'Đã được xét duyệt tự động.');
            $verification->reviewed_by_account_id = auth()->id();
            $verification->reviewed_at = now();
            $verification->save();

            // 2. Cập nhật trạng thái của tổ chức (Org)
            $org = $verification->org;
            if ($org) {
                $org->status = 'VERIFIED';
                $org->save();
            }

            // Log admin action
            AdminLogService::logApprove(
                'OrgVerification',
                $verification->id,
                "Duyệt xác minh doanh nghiệp: {$org->name}"
            );

            try {
                $ownerId = $org->owner_account_id ?? null;
                if ($ownerId && $ownerId !== auth()->id()) {
                    $notification = app(\App\Services\NotificationService::class)->create(
                        userId: $ownerId,
                        type: \App\Models\Notification::TYPE_SYSTEM,
                        title: 'Doanh nghiệp của bạn đã được xác minh',
                        body: "Doanh nghiệp '{$org->name}' đã được xác minh thành công bởi hệ thống.",
                        meta: [
                            'org_id' => $org->org_id ?? $org->id,
                            'verification_id' => $verification->id,
                        ],
                        actorId: auth()->id(),
                        severity: 'low'
                    );

                    broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $ownerId))->toOthers();
                    \Cache::forget("header_json_{$ownerId}");
                }
            } catch (\Exception $e) {
                \Log::error('Gửi thông báo duyệt doanh nghiệp thất bại', ['error' => $e->getMessage()]);
            }

            DB::commit();

            return back()->with('success', 'Đã xét duyệt và xác minh doanh nghiệp thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra khi xét duyệt: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, OrgVerification $verification)
    {
        // Kiểm tra nếu đã duyệt hoặc từ chối rồi thì không làm gì nữa
        if ($verification->status !== 'PENDING') {
            return back()->with('error', 'Đơn xét duyệt này không ở trạng thái chờ.');
        }

        $request->validate([
            'review_note' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // 1. Cập nhật trạng thái của đơn xét duyệt
            $verification->status = 'REJECTED';
            $verification->review_note = $request->input('review_note');
            $verification->reviewed_by_account_id = auth()->id();
            $verification->reviewed_at = now();
            $verification->save();

            // 2. Cập nhật trạng thái của tổ chức (Org) về UNVERIFIED
            $org = $verification->org;
            if ($org && $org->status !== 'UNVERIFIED') {
                $org->status = 'UNVERIFIED';
                $org->save();
            }

            // Log admin action
            AdminLogService::logReject(
                'OrgVerification',
                $verification->id,
                "Từ chối xác minh doanh nghiệp: {$org->name}",
                $request->input('review_note')
            );

            try {
                $ownerId = $org->owner_account_id ?? null;
                if ($ownerId && $ownerId !== auth()->id()) {
                    $notification = app(\App\Services\NotificationService::class)->create(
                        userId: $ownerId,
                        type: \App\Models\Notification::TYPE_SYSTEM,
                        title: 'Xác minh doanh nghiệp bị từ chối',
                        body: "Doanh nghiệp '{$org->name}' đã bị từ chối xác minh. Lý do: {$verification->review_note}",
                        meta: [
                            'org_id' => $org->org_id ?? $org->id,
                            'verification_id' => $verification->id,
                        ],
                        actorId: auth()->id(),
                        severity: 'medium'
                    );

                    broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $ownerId))->toOthers();
                    \Cache::forget("header_json_{$ownerId}");
                }
            } catch (\Exception $e) {
                \Log::error('Gửi thông báo từ chối doanh nghiệp thất bại', ['error' => $e->getMessage()]);
            }

            DB::commit();

            return back()->with('success', 'Đã từ chối xét duyệt doanh nghiệp.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra khi từ chối xét duyệt: ' . $e->getMessage());
        }
    }

    /**
     * Duyệt nhiều đơn xét duyệt cùng lúc
     */
    public function bulkApprove(Request $request)
    {
        $verificationIds = $request->input('verification_ids');

        // Nếu là string (comma-separated hoặc JSON), xử lý
        if (is_string($verificationIds)) {
            // Thử decode JSON trước
            $decoded = json_decode($verificationIds, true);
            if ($decoded !== null) {
                $verificationIds = $decoded;
            } else {
                // Nếu không phải JSON, split by comma
                $verificationIds = explode(',', $verificationIds);
                $verificationIds = array_map('intval', array_filter($verificationIds));
            }
        }

        if (empty($verificationIds) || !is_array($verificationIds)) {
            return back()->with('error', 'Không có đơn xét duyệt nào được chọn.');
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($verificationIds as $id) {
                $verification = OrgVerification::find($id);

                if ($verification && $verification->status === 'PENDING') {
                    $verification->status = 'APPROVED';
                    $verification->review_note = 'Đã được xét duyệt hàng loạt.';
                    $verification->reviewed_by_account_id = auth()->id();
                    $verification->reviewed_at = now();
                    $verification->save();

                    // Cập nhật trạng thái Org
                    $org = $verification->org;
                    if ($org) {
                        $org->status = 'VERIFIED';
                        $org->save();
                    }

                    try {
                        $ownerId = $org->owner_account_id ?? null;
                        if ($ownerId && $ownerId !== auth()->id()) {
                            $notification = app(\App\Services\NotificationService::class)->create(
                                userId: $ownerId,
                                type: \App\Models\Notification::TYPE_SYSTEM,
                                title: 'Doanh nghiệp của bạn đã được xác minh',
                                body: "Doanh nghiệp '{$org->name}' đã được xác minh thành công bởi hệ thống.",
                                meta: [
                                    'org_id' => $org->org_id ?? $org->id,
                                    'verification_id' => $verification->id,
                                ],
                                actorId: auth()->id(),
                                severity: 'low'
                            );

                            broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $ownerId))->toOthers();
                            \Cache::forget("header_json_{$ownerId}");
                        }
                    } catch (\Exception $e) {
                        \Log::error('Gửi thông báo duyệt doanh nghiệp thất bại (bulk)', [
                            'error' => $e->getMessage(),
                            'org_id' => $org->id ?? null
                        ]);
                    }

                    $count++;
                }
            }

            if ($count > 0) {
                // Log bulk approve
                AdminLogService::logBulk('approve', 'OrgVerification', $verificationIds, "Duyệt hàng loạt $count đơn xét duyệt");

                DB::commit();

                return back()->with('success', "Đã duyệt thành công $count đơn xét duyệt!");
            } else {
                DB::rollBack();
                return back()->with('error', 'Không có đơn xét duyệt nào hợp lệ để duyệt.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk approve error: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Từ chối nhiều đơn xét duyệt cùng lúc
     */
    public function bulkReject(Request $request)
    {
        $verificationIds = $request->input('verification_ids');
        $reviewNote = $request->input('review_note', 'Đã bị từ chối hàng loạt.');

        // Nếu là string (comma-separated hoặc JSON), xử lý
        if (is_string($verificationIds)) {
            // Thử decode JSON trước
            $decoded = json_decode($verificationIds, true);
            if ($decoded !== null) {
                $verificationIds = $decoded;
            } else {
                // Nếu không phải JSON, split by comma
                $verificationIds = explode(',', $verificationIds);
                $verificationIds = array_map('intval', array_filter($verificationIds));
            }
        }

        if (empty($verificationIds) || !is_array($verificationIds)) {
            return back()->with('error', 'Không có đơn xét duyệt nào được chọn.');
        }

        // Validate review note
        if (empty(trim($reviewNote))) {
            return back()->with('error', 'Vui lòng nhập lý do từ chối.');
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($verificationIds as $id) {
                $verification = OrgVerification::find($id);

                if ($verification && $verification->status === 'PENDING') {
                    $verification->status = 'REJECTED';
                    $verification->review_note = $reviewNote;
                    $verification->reviewed_by_account_id = auth()->id();
                    $verification->reviewed_at = now();
                    $verification->save();

                    // Cập nhật trạng thái Org
                    $org = $verification->org;
                    if ($org && $org->status !== 'UNVERIFIED') {
                        $org->status = 'UNVERIFIED';
                        $org->save();
                    }

                    try {
                        $ownerId = $org->owner_account_id ?? null;
                        if ($ownerId && $ownerId !== auth()->id()) {
                            $notification = app(\App\Services\NotificationService::class)->create(
                                userId: $ownerId,
                                type: \App\Models\Notification::TYPE_SYSTEM,
                                title: 'Xác minh doanh nghiệp bị từ chối',
                                body: "Doanh nghiệp '{$org->name}' đã bị từ chối xác minh. Lý do: {$reviewNote}",
                                meta: [
                                    'org_id' => $org->org_id ?? $org->id,
                                    'verification_id' => $verification->id,
                                ],
                                actorId: auth()->id(),
                                severity: 'medium'
                            );

                            broadcast(new \App\Events\GenericNotificationBroadcasted($notification, $ownerId))->toOthers();
                            \Cache::forget("header_json_{$ownerId}");
                        }
                    } catch (\Exception $e) {
                        \Log::error('Gửi thông báo từ chối doanh nghiệp thất bại (bulk)', [
                            'error' => $e->getMessage(),
                            'org_id' => $org->id ?? null
                        ]);
                    }

                    $count++;
                }
            }

            if ($count > 0) {
                // Log bulk reject
                AdminLogService::logBulk('reject', 'OrgVerification', $verificationIds, "Từ chối hàng loạt $count đơn xét duyệt: $reviewNote");

                DB::commit();

                return back()->with('success', "Đã từ chối $count đơn xét duyệt!");
            } else {
                DB::rollBack();
                return back()->with('error', 'Không có đơn xét duyệt nào hợp lệ để từ chối.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk reject error: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}