@extends('admin.layouts.app') 

@section('title', 'Chi Tiết Đơn Xét Duyệt')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-file-invoice me-2"></i>Chi Tiết Đơn Xét Duyệt #{{ $verification->id }}
    </h1>
    <a href="{{ route('admin.verifications.index') }}" class="btn btn-secondary shadow-sm">
        <i class="fas fa-arrow-left me-2"></i>Quay lại Danh sách
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow mb-4 border-primary">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-info-circle me-2"></i>Thông tin Đơn Xét Duyệt
                </h6>
            </div>
            <div class="card-body">
                <p><strong>ID Đơn:</strong> {{ $verification->id }}</p>
                <p><strong>Trạng Thái Đơn:</strong> 
                    @switch($verification->status)
                        @case('PENDING')
                            <span class="badge bg-warning text-dark">Chờ Duyệt</span>
                            @break
                        @case('APPROVED')
                            <span class="badge bg-success text-white">Đã Duyệt</span>
                            @break
                        @case('REJECTED')
                            <span class="badge bg-danger text-white">Đã Từ Chối</span>
                            @break
                        @default
                            <span class="badge bg-secondary text-white">{{ $verification->status }}</span>
                    @endswitch
                </p>
                <p><strong>Thời gian Nộp:</strong> 
                    {{-- ĐÃ KIỂM TRA created_at LUÔN LÀ DATE MẶC ĐỊNH, NHƯNG TA VẪN KIỂM TRA AN TOÀN --}}
                    @if ($verification->created_at)
                        {{ $verification->created_at instanceof \Carbon\Carbon ? $verification->created_at->format('d/m/Y H:i') : \Carbon\Carbon::parse($verification->created_at)->format('d/m/Y H:i') }}
                    @else
                        N/A
                    @endif
                </p>
                <p><strong>Người Nộp:</strong> 
                    @if ($verification->submittedByAccount)
                        <a href="#">{{ $verification->submittedByAccount->name }} (ID: {{ $verification->submittedByAccount->account_id }})</a>
                    @else
                        Không rõ
                    @endif
                </p>
                <hr>
                
                <h6 class="font-weight-bold text-primary mb-3">Thông tin Doanh Nghiệp</h6>
                @if ($verification->org)
                    <p><strong>ID Org:</strong> {{ $verification->org->org_id }}</p>
                    <p><strong>Tên Doanh Nghiệp:</strong> {{ $verification->org->name }}</p>
                    <p><strong>Trạng Thái Org:</strong> 
                        @if ($verification->org->is_verified)
                            <span class="badge bg-success text-white">Đã Xác Minh (VERIFIED)</span>
                        @else
                            <span class="badge bg-secondary text-white">{{ $verification->org->verification_status }}</span>
                        @endif
                    </p>
                    <p><strong>Chủ Sở Hữu Org:</strong> 
                        @if ($verification->org->owner)
                            <a href="#">{{ $verification->org->owner->name }} (ID: {{ $verification->org->owner->account_id }})</a>
                        @else
                            Không rõ
                        @endif
                    </p>
                @else
                    <p class="text-danger">Không tìm thấy thông tin Doanh nghiệp.</p>
                @endif

                @if ($verification->status !== 'PENDING')
                    <hr>
                    <h6 class="font-weight-bold text-primary mb-3">Kết quả Duyệt</h6>
                    <p><strong>Người Duyệt:</strong> 
                        @if ($verification->reviewedByAccount)
                            {{ $verification->reviewedByAccount->name }}
                        @else
                            Admin
                        @endif
                    </p>
                    <p><strong>Thời gian Duyệt:</strong> 
                        {{-- FIX LỖI TRIỆT ĐỂ: Buộc chuyển đổi sang Carbon nếu nó vẫn là string --}}
                        @if ($verification->reviewed_at)
                            {{ $verification->reviewed_at instanceof \Carbon\Carbon ? $verification->reviewed_at->format('d/m/Y H:i') : \Carbon\Carbon::parse($verification->reviewed_at)->format('d/m/Y H:i') }}
                        @else
                            Chưa có thời gian
                        @endif
                    </p>
                    <p><strong>Ghi chú:</strong> <br>{{ $verification->review_note ?? 'Không có ghi chú.' }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow mb-4 border-info">
            <div class="card-header bg-info text-white py-3">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-file-alt me-2"></i>Tài liệu đính kèm
                </h6>
            </div>
            <div class="card-body text-center">
                @php
                    // Ưu tiên file_url, nếu không có thì dùng file_path
                    $fileUrl = $verification->file_url;
                    if (empty($fileUrl) && !empty($verification->file_path)) {
                        $fileUrl = Storage::url($verification->file_path);
                    }
                    
                    // Xác định loại file
                    $mimeType = $verification->mime_type ?? '';
                    $isImage = Str::startsWith($mimeType, 'image/') || 
                               preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $fileUrl);
                @endphp
                
                @if ($fileUrl)
                    @if (!empty($verification->file_path))
                        <p><strong>Tên file:</strong> {{ basename($verification->file_path) }}</p>
                    @endif
                    @if (!empty($mimeType))
                        <p><strong>Loại file:</strong> {{ $mimeType }}</p>
                    @endif
                    
                    @if ($isImage)
                        <div class="mb-3" style="overflow: auto; max-height: 600px;">
                            <img src="{{ $fileUrl }}" 
                                 alt="Tài liệu Doanh nghiệp" 
                                 class="img-fluid rounded shadow verification-image" 
                                 style="max-height: 400px; max-width: 100%; cursor: zoom-in; transition: transform 0.3s ease;"
                                 onclick="toggleImageZoom(this)"
                                 onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}'; this.alt='Không thể tải hình ảnh';">
                        </div>
                    @else
                        <i class="fas fa-file fa-5x text-secondary my-3"></i>
                    @endif
                    
                    <div class="mt-3">
                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Xem File gốc
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không có tài liệu đính kèm hoặc file đã bị xóa.
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Phần thao tác duyệt/từ chối --}}
        @if ($verification->status === 'PENDING')
        <div class="card shadow mb-4 border-warning">
            <div class="card-header bg-warning text-dark py-3">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-tasks me-2"></i>Thao Tác Xét Duyệt
                </h6>
            </div>
            <div class="card-body">
                {{-- Form Duyệt --}}
                <form id="approve-form" action="{{ route('admin.verifications.approve', $verification) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="approve_note" class="form-label">Ghi chú (Tùy chọn):</label>
                        <textarea name="review_note" id="approve_note" class="form-control" rows="2" placeholder="Ghi chú xác minh thành công"></textarea>
                    </div>
                    {{-- Nút type="button" để kích hoạt JS/SweetAlert --}}
                    <button type="button" class="btn btn-success w-100 mb-2" id="btn-approve">
                        <i class="fas fa-check"></i> DUYỆT và Xác Minh
                    </button>
                </form>

                <hr class="my-3">

                {{-- Form Từ Chối --}}
                <form id="reject-form" action="{{ route('admin.verifications.reject', $verification) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="reject_note" class="form-label">Lý do Từ chối (Bắt buộc):</label>
                        <textarea name="review_note" id="reject_note" class="form-control @error('review_note') is-invalid @enderror" rows="3" required placeholder="Nêu rõ lý do từ chối để người dùng biết"></textarea>
                        @error('review_note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Nút type="button" để kích hoạt JS/SweetAlert --}}
                    <button type="button" class="btn btn-danger w-100" id="btn-reject">
                        <i class="fas fa-times"></i> TỪ CHỐI
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
{{-- Đảm bảo đã include SweetAlert2 CDN trong layout chính của bạn --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Script hiển thị kết quả từ Session Flash
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: '{{ session('success') }}',
                confirmButtonText: 'Đóng'
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: '{{ session('error') }}',
                confirmButtonText: 'Đóng'
            });
        @endif

        // 2. Logic SweetAlert cho nút DUYỆT
        const btnApprove = document.getElementById('btn-approve');
        if (btnApprove) {
            btnApprove.addEventListener('click', function() {
                Swal.fire({
                    title: 'Xác nhận Duyệt?',
                    text: "Bạn có chắc chắn muốn DUYỆT đơn này và xác minh doanh nghiệp?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Vâng, Duyệt!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Gửi form khi người dùng xác nhận
                        document.getElementById('approve-form').submit();
                    }
                })
            });
        }

        // 3. Logic SweetAlert cho nút TỪ CHỐI
        const btnReject = document.getElementById('btn-reject');
        if (btnReject) {
            btnReject.addEventListener('click', function() {
                const rejectNote = document.getElementById('reject_note').value;
                
                // Kiểm tra validation bắt buộc của Laravel trên client side trước khi gửi
                if (!rejectNote || rejectNote.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thiếu lý do!',
                        text: 'Vui lòng nhập lý do từ chối xét duyệt (Bắt buộc).',
                        confirmButtonText: 'Đã hiểu'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Xác nhận Từ chối?',
                    text: "Doanh nghiệp sẽ không được xác minh. Tiếp tục?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Vâng, Từ chối!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Gửi form khi người dùng xác nhận
                        document.getElementById('reject-form').submit();
                    }
                })
            });
        }
    });
    
    // Function để toggle zoom hình ảnh
    window.toggleImageZoom = function(img) {
        const isZoomed = img.classList.contains('zoomed');
        
        if (isZoomed) {
            // Thu nhỏ về kích thước ban đầu
            img.classList.remove('zoomed');
            img.style.transform = 'scale(1)';
            img.style.cursor = 'zoom-in';
            img.style.maxHeight = '400px';
            img.style.maxWidth = '100%';
            img.style.position = 'relative';
            img.style.zIndex = '1';
            
            // Bỏ khả năng kéo
            img.onmousedown = null;
            img.onmousemove = null;
            img.onmouseup = null;
            img.onmouseleave = null;
        } else {
            // Phóng to hình ảnh
            img.classList.add('zoomed');
            img.style.transform = 'scale(2)';
            img.style.cursor = 'zoom-out';
            img.style.maxHeight = 'none';
            img.style.maxWidth = 'none';
            img.style.position = 'relative';
            img.style.zIndex = '10';
            
            // Thêm khả năng kéo hình khi zoom
            let isDragging = false;
            let startX, startY, scrollLeft, scrollTop;
            const container = img.parentElement;
            
            img.onmousedown = function(e) {
                isDragging = true;
                img.style.cursor = 'grabbing';
                startX = e.pageX - container.offsetLeft;
                startY = e.pageY - container.offsetTop;
                scrollLeft = container.scrollLeft;
                scrollTop = container.scrollTop;
            };
            
            img.onmousemove = function(e) {
                if (!isDragging) return;
                e.preventDefault();
                const x = e.pageX - container.offsetLeft;
                const y = e.pageY - container.offsetTop;
                const walkX = (x - startX) * 2;
                const walkY = (y - startY) * 2;
                container.scrollLeft = scrollLeft - walkX;
                container.scrollTop = scrollTop - walkY;
            };
            
            img.onmouseup = function() {
                isDragging = false;
                img.style.cursor = 'zoom-out';
            };
            
            img.onmouseleave = function() {
                isDragging = false;
                img.style.cursor = 'zoom-out';
            };
        }
    };
</script>
@endpush