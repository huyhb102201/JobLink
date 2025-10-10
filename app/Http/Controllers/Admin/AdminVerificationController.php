<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrgVerification;
use App\Models\Org;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

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
            $statusBadge = match($verification->status) {
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

        // Tối ưu hóa queries với cache
        $totalVerifications = \Cache::remember('admin_total_verifications', 300, function() {
            return OrgVerification::count();
        });
        $pendingVerifications = \Cache::remember('admin_pending_verifications', 300, function() {
            return OrgVerification::where('status', 'PENDING')->count();
        });
        $approvedVerifications = \Cache::remember('admin_approved_verifications', 300, function() {
            return OrgVerification::where('status', 'APPROVED')->count();
        });
        $rejectedVerifications = \Cache::remember('admin_rejected_verifications', 300, function() {
            return OrgVerification::where('status', 'REJECTED')->count();
        });

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
            
            $statusBadge = match($verification->status) {
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
        if (empty($fileUrl) && !empty($verification->file_path)) {
            // Tạo URL từ file_path
            $fileUrl = asset('storage/' . $verification->file_path);
        }
        
        if (empty($fileUrl) && empty($verification->file_path)) {
            return '<div class="mt-4">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Không có tài liệu đính kèm
                </div>
            </div>';
        }
        
        $html = '<div class="mt-4"><h6 class="fw-bold text-primary">Tài liệu đính kèm</h6>';
        
        // Kiểm tra xem file có tồn tại không
        $filePath = storage_path('app/public/' . $verification->file_path);
        $fileExists = !empty($verification->file_path) && file_exists($filePath);
        
        if (!$fileExists && !empty($verification->file_path)) {
            $html .= '<div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>File không tồn tại:</strong> ' . $verification->file_path . '
                <br><small>File có thể đã bị xóa hoặc chưa được upload.</small>
            </div>';
            $html .= '</div>';
            return $html;
        }
        
        // Xác định loại file để hiển thị phù hợp
        $mimeType = $verification->mime_type ?? '';
        $isImage = strpos($mimeType, 'image/') === 0;
        $isPdf = strpos($mimeType, 'pdf') !== false;
        
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
            // Hiển thị ảnh
            $html .= '<img src="' . $fileUrl . '" 
                          style="max-width: 100%; height: auto; display: block; margin: 0 auto;" 
                          alt="Tài liệu xác minh">';
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

            DB::commit();
            
            // Xóa cache thống kê
            \Cache::forget('admin_total_verifications');
            \Cache::forget('admin_pending_verifications');
            \Cache::forget('admin_approved_verifications');
            \Cache::forget('admin_rejected_verifications');
            
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

            DB::commit();
            
            // Xóa cache thống kê
            \Cache::forget('admin_total_verifications');
            \Cache::forget('admin_pending_verifications');
            \Cache::forget('admin_approved_verifications');
            \Cache::forget('admin_rejected_verifications');
            
            return back()->with('success', 'Đã từ chối xét duyệt doanh nghiệp.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra khi từ chối xét duyệt: ' . $e->getMessage());
        }
    }
}