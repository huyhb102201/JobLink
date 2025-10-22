@extends('admin.layouts.app') 

@section('title', 'Chi Tiết Đơn Xét Duyệt')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Chi Tiết Đơn Xét Duyệt #{{ $verification->id }}</h1>
    <a href="{{ route('admin.verifications.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Quay lại Danh sách
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Thông tin Đơn Xét Duyệt</h6>
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
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tài liệu đính kèm</h6>
            </div>
            <div class="card-body text-center">
                @if ($verification->file_path)
                    <p><strong>Tên file:</strong> {{ basename($verification->file_path) }}</p>
                    <p><strong>Loại file:</strong> {{ $verification->mime_type }}</p>
                    @if (Str::startsWith($verification->mime_type, 'image/'))
                        {{-- Giả định Storage::url() hoạt động đúng để tạo URL công khai --}}
                        <img src="{{ Storage::url($verification->file_path) }}" alt="Tài liệu Doanh nghiệp" class="img-fluid rounded" style="max-height: 300px;">
                    @else
                        <i class="fas fa-file fa-5x text-secondary my-3"></i>
                    @endif
                    <div class="mt-3">
                        <a href="{{ Storage::url($verification->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-download"></i> Tải/Xem File gốc
                        </a>
                    </div>
                @else
                    <p class="text-danger">Không có tài liệu đính kèm.</p>
                @endif
            </div>
        </div>
        
        {{-- Phần thao tác duyệt/từ chối --}}
        @if ($verification->status === 'PENDING')
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-dark">Thao Tác Xét Duyệt</h6>
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
</script>
@endpush