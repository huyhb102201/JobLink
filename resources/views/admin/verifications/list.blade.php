@extends('admin.layouts.app')

@section('title', 'Xét duyệt doanh nghiệp')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Fix layout cho bảng */
    #verifications-table {
        width: 100% !important;
        table-layout: fixed;
    }
    
    #verifications-table th,
    #verifications-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Định width cho từng cột */
    #verifications-table th:nth-child(1),
    #verifications-table td:nth-child(1) { width: 50px; } /* Checkbox */
    
    #verifications-table th:nth-child(2),
    #verifications-table td:nth-child(2) { 
        width: 200px; 
        white-space: normal;
    } /* Tên doanh nghiệp */
    
    #verifications-table th:nth-child(3),
    #verifications-table td:nth-child(3) { width: 180px; } /* Chủ sở hữu */
    
    #verifications-table th:nth-child(4),
    #verifications-table td:nth-child(4) { width: 180px; } /* Người nộp */
    
    #verifications-table th:nth-child(5),
    #verifications-table td:nth-child(5) { width: 120px; } /* Trạng thái */
    
    #verifications-table th:nth-child(6),
    #verifications-table td:nth-child(6) { width: 130px; } /* Ngày nộp */
    
    #verifications-table th:nth-child(7),
    #verifications-table td:nth-child(7) { 
        width: 150px; 
        text-align: center;
        white-space: nowrap;
    } /* Thao tác */
    
    .btn-group .btn {
        margin-right: 2px;
        padding: 0.25rem 0.5rem;
    }
    
    .btn-group .btn:last-child {
        margin-right: 0;
    }
    
    /* Tối ưu hiển thị badge */
    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }
    
    /* DataTables custom styling */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 0.375rem;
        border: 1px solid #ced4da;
        padding: 0.375rem 0.75rem;
        margin-left: 0.5rem;
    }
    
    /* Styling cho disabled checkbox và buttons */
    input[type="checkbox"]:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
    
    .btn:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
</style>
@endpush

@section('content')
  <h1 class="h3 mb-4">Xét duyệt doanh nghiệp</h1>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng đơn xét duyệt</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalVerifications ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-building fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Chờ duyệt</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingVerifications ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đã duyệt</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $approvedVerifications ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-check-circle fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Đã từ chối</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $rejectedVerifications ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-times-circle fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Danh sách đơn xét duyệt</h6>
      <div>
        <button id="bulk-approve-btn" type="button" class="btn btn-success btn-sm me-2" disabled>
          <i class="fas fa-check me-1"></i> Duyệt đã chọn
        </button>
        <button id="bulk-reject-btn" type="button" class="btn btn-danger btn-sm" disabled>
          <i class="fas fa-times me-1"></i> Từ chối đã chọn
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="verifications-table">
          <thead class="table-light">
            <tr>
              <th><input type="checkbox" class="form-check-input" id="checkAll"></th>
              <th>Tên doanh nghiệp</th>
              <th>Chủ sở hữu</th>
              <th>Người nộp</th>
              <th>Trạng thái</th>
              <th>Ngày nộp</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach($verifications as $verification)
            <tr>
              <td>
                @if($verification->status == 'PENDING')
                  <input type="checkbox" class="form-check-input row-checkbox" data-verification-id="{{ $verification->id }}">
                @else
                  <input type="checkbox" class="form-check-input" disabled>
                @endif
              </td>
              <td>
                <div class="fw-bold">{{ $verification->org->name ?? 'N/A' }}</div>
                <small class="text-muted">ID: {{ $verification->org_id }}</small>
              </td>
              <td>
                {{ $verification->org->owner->name ?? 'N/A' }}
                @if($verification->org && $verification->org->owner)
                  <br><small class="text-muted">ID: {{ $verification->org->owner->account_id }}</small>
                @endif
              </td>
              <td>
                {{ $verification->submittedByAccount->name ?? 'N/A' }}
                @if($verification->submittedByAccount)
                  <br><small class="text-muted">ID: {{ $verification->submittedByAccount->account_id }}</small>
                @endif
              </td>
              <td>
                @if($verification->status == 'PENDING')
                  <span class="badge bg-warning">Chờ duyệt</span>
                @elseif($verification->status == 'APPROVED')
                  <span class="badge bg-success">Đã duyệt</span>
                @elseif($verification->status == 'REJECTED')
                  <span class="badge bg-danger">Đã từ chối</span>
                @else
                  <span class="badge bg-secondary">{{ $verification->status }}</span>
                @endif
              </td>
              <td>{{ $verification->created_at->format('d/m/Y H:i') }}</td>
              <td>
                <div class="btn-group" role="group">
                  <button class="btn btn-info btn-sm view-verification-btn" 
                          data-verification-id="{{ $verification->id }}" 
                          title="Xem chi tiết">
                    <i class="fas fa-eye"></i>
                  </button>
                  @if($verification->status == 'PENDING')
                    <button class="btn btn-success btn-sm approve-verification-btn" 
                            data-verification-id="{{ $verification->id }}" 
                            title="Duyệt">
                      <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-danger btn-sm reject-verification-btn" 
                            data-verification-id="{{ $verification->id }}" 
                            title="Từ chối">
                      <i class="fas fa-times"></i>
                    </button>
                  @else
                    <button class="btn btn-success btn-sm" disabled title="Đã xử lý">
                      <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" disabled title="Đã xử lý">
                      <i class="fas fa-times"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal xem chi tiết xét duyệt -->
  <div class="modal fade" id="verificationDetailModal" tabindex="-1" aria-labelledby="verificationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="verificationDetailModalLabel">
            <i class="fas fa-file-invoice me-2"></i>Chi tiết đơn xét duyệt
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4" id="verificationDetailContent">
          <!-- Content sẽ được load bằng JavaScript -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Đóng
          </button>
          <button type="button" class="btn btn-success" id="modalApproveBtn" style="display:none;">
            <i class="fas fa-check me-1"></i>Duyệt
          </button>
          <button type="button" class="btn btn-danger" id="modalRejectBtn" style="display:none;">
            <i class="fas fa-times me-1"></i>Từ chối
          </button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Preload verification details data
const verificationDetailsData = @json($verificationDetails);

$(document).ready(function() {
    let selectedVerifications = new Set();
    let table; // Khai báo trước để tránh lỗi

    function updateButtonStates() {
        const selectedCount = selectedVerifications.size;
        const isDisabled = selectedCount === 0;
        
        $('#bulk-approve-btn').prop('disabled', isDisabled);
        $('#bulk-reject-btn').prop('disabled', isDisabled);
        
        // Cập nhật text hiển thị số lượng
        if (selectedCount > 0) {
            $('#bulk-approve-btn').html(`<i class="fas fa-check me-1"></i> Duyệt đã chọn (${selectedCount})`);
            $('#bulk-reject-btn').html(`<i class="fas fa-times me-1"></i> Từ chối đã chọn (${selectedCount})`);
        } else {
            $('#bulk-approve-btn').html('<i class="fas fa-check me-1"></i> Duyệt đã chọn');
            $('#bulk-reject-btn').html('<i class="fas fa-times me-1"></i> Từ chối đã chọn');
        }
    }
    
    function updateCheckAllState() {
        // Kiểm tra xem table đã được khởi tạo chưa
        if (!table) {
            return;
        }
        
        // Kiểm tra TẤT CẢ verification (trên mọi trang)
        const totalVerifications = table.rows().count();
        const selectedCount = selectedVerifications.size;
        
        if (selectedCount === 0) {
            $('#checkAll').prop('checked', false);
            $('#checkAll').prop('indeterminate', false);
        } else if (selectedCount === totalVerifications) {
            $('#checkAll').prop('checked', true);
            $('#checkAll').prop('indeterminate', false);
        } else {
            $('#checkAll').prop('checked', false);
            $('#checkAll').prop('indeterminate', true);
        }
    }

    // Khởi tạo DataTables tối ưu - vừa đẹp vừa nhanh
    table = $('#verifications-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        stateSave: true,
        searching: true,
        searchDelay: 100,
        deferRender: true,
        autoWidth: false,
        columnDefs: [
            { orderable: false, targets: [0, 6] }, // Tắt sort cho checkbox và action
            { className: "text-center", targets: [0, 6] }
        ],
        language: {
            lengthMenu: "Hiển thị _MENU_ mục",
            zeroRecords: "Không tìm thấy dữ liệu",
            info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
            infoEmpty: "Hiển thị 0 đến 0 của 0 mục",
            infoFiltered: "(được lọc từ _MAX_ mục)",
            search: "Tìm kiếm:",
            paginate: { first: "Đầu", last: "Cuối", next: "Tiếp", previous: "Trước" }
        },
        drawCallback: function() {
            // Giữ trạng thái checkbox khi chuyển trang
            $('.row-checkbox').each(function() {
                const verificationId = $(this).data('verification-id');
                if (selectedVerifications.has(verificationId)) {
                    $(this).prop('checked', true);
                }
            });
            
            // Cập nhật trạng thái "Chọn tất cả"
            updateCheckAllState();
            
            // Cập nhật trạng thái nút (quan trọng!)
            updateButtonStates();
            
            bindEvents();
        }
    });

    function bindEvents() {
        // Checkbox events
        $('.row-checkbox').off('change').on('change', function() {
            const verificationId = $(this).data('verification-id');
            if (this.checked) {
                selectedVerifications.add(verificationId);
            } else {
                selectedVerifications.delete(verificationId);
            }
            updateButtonStates();
            updateCheckAllState();
        });

        // Approve button events
        $('.approve-verification-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const verificationId = $(this).data('verification-id');
            
            Swal.fire({
                title: 'Duyệt đơn xét duyệt?',
                text: "Bạn có chắc chắn muốn duyệt đơn này?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý duyệt',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('Đang duyệt đơn xét duyệt...');
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/verifications/${verificationId}/approve`
                    }).append('@csrf');
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // View verification button events - Sử dụng dữ liệu đã preload
        $('.view-verification-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const verificationId = $(this).data('verification-id');
            
            // Lấy dữ liệu từ preloaded data
            const v = verificationDetailsData[verificationId];
            
            if (v) {
                // Show modal với dữ liệu ngay lập tức
                $('#verificationDetailModal').modal('show');
                $('#verificationDetailContent').html(`
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-building me-2"></i>Thông tin doanh nghiệp</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="fw-bold" width="40%">Tên doanh nghiệp:</td>
                                            <td>${v.org_name}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Mã số thuế:</td>
                                            <td>${v.tax_code || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Địa chỉ:</td>
                                            <td>${v.address || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Số điện thoại:</td>
                                            <td>${v.phone || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Email:</td>
                                            <td>${v.email || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Website:</td>
                                            <td>${v.website || 'N/A'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-info h-100">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Thông tin xét duyệt</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="fw-bold" width="40%">Người nộp:</td>
                                            <td>${v.submitted_by}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Ngày nộp:</td>
                                            <td>${v.created_at}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Trạng thái:</td>
                                            <td>${v.status_badge}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Ghi chú:</td>
                                            <td>${v.note || 'Không có'}</td>
                                        </tr>
                                        ${v.reviewed_at ? `
                                        <tr>
                                            <td class="fw-bold">Người duyệt:</td>
                                            <td>${v.reviewed_by || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Ngày duyệt:</td>
                                            <td>${v.reviewed_at}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Ghi chú duyệt:</td>
                                            <td>${v.review_note || 'Không có'}</td>
                                        </tr>
                                        ` : ''}
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${v.documents_html ? `<div class="mt-4">${v.documents_html}</div>` : ''}
                `);
                
                // Show/hide action buttons based on status
                if (v.status === 'PENDING') {
                    $('#modalApproveBtn').show().data('verification-id', verificationId);
                    $('#modalRejectBtn').show().data('verification-id', verificationId);
                } else {
                    $('#modalApproveBtn').hide();
                    $('#modalRejectBtn').hide();
                }
            } else {
                // Fallback nếu không tìm thấy dữ liệu
                $('#verificationDetailModal').modal('show');
                $('#verificationDetailContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không thể tải thông tin chi tiết
                    </div>
                `);
            }
        });

        // Reject button events
        $('.reject-verification-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const verificationId = $(this).data('verification-id');
            
            Swal.fire({
                title: 'Từ chối đơn xét duyệt?',
                text: "Vui lòng nhập lý do từ chối:",
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do từ chối (bắt buộc)...',
                inputAttributes: {
                    'aria-label': 'Nhập lý do từ chối',
                    'maxlength': 500
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý từ chối',
                cancelButtonText: 'Hủy',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Bạn phải nhập lý do từ chối!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('Đang từ chối đơn xét duyệt...');
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/verifications/${verificationId}/reject`
                    }).append(
                        '@csrf',
                        $('<input>', { type: 'hidden', name: 'review_note', value: result.value })
                    );
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    }

    // Check all functionality - Chọn tất cả trên MỌI TRANG
    $('#checkAll').on('change', function() {
        const isChecked = this.checked;
        
        if (isChecked) {
            // Chọn TẤT CẢ verification (trên mọi trang)
            selectedVerifications.clear();
            table.rows().nodes().to$().each(function() {
                const checkbox = $(this).find('.row-checkbox:not(:disabled)');
                const verificationId = checkbox.data('verification-id');
                
                if (verificationId) {
                    checkbox.prop('checked', true);
                    selectedVerifications.add(verificationId);
                }
            });
        } else {
            // Bỏ chọn TẤT CẢ
            table.rows().nodes().to$().each(function() {
                const checkbox = $(this).find('.row-checkbox');
                checkbox.prop('checked', false);
            });
            selectedVerifications.clear();
        }
        
        updateButtonStates();
    });

    // Bulk operations
    function performBulkApprove() {
        if (selectedVerifications.size === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn ít nhất một đơn xét duyệt', 'warning');
            return;
        }

        const verificationIds = Array.from(selectedVerifications);

        Swal.fire({
            title: `Bạn có chắc chắn muốn duyệt ${verificationIds.length} đơn đã chọn?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý duyệt',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Đang duyệt hàng loạt...');
                const form = $('<form>', {
                    method: 'POST',
                    action: '/admin/verifications/bulk-approve'
                }).append(
                    '@csrf',
                    $('<input>', { type: 'hidden', name: 'verification_ids', value: verificationIds.join(',') })
                );
                $('body').append(form);
                form.submit();
            }
        });
    }

    function performBulkReject() {
        if (selectedVerifications.size === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn ít nhất một đơn xét duyệt', 'warning');
            return;
        }

        const verificationIds = Array.from(selectedVerifications);

        Swal.fire({
            title: `Từ chối ${verificationIds.length} đơn đã chọn?`,
            text: "Vui lòng nhập lý do từ chối:",
            input: 'textarea',
            inputPlaceholder: 'Nhập lý do từ chối (bắt buộc)...',
            inputAttributes: {
                'aria-label': 'Nhập lý do từ chối',
                'maxlength': 500
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý từ chối',
            cancelButtonText: 'Hủy',
            inputValidator: (value) => {
                if (!value) {
                    return 'Bạn phải nhập lý do từ chối!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                showLoading('Đang từ chối hàng loạt...');
                const form = $('<form>', {
                    method: 'POST',
                    action: '/admin/verifications/bulk-reject'
                }).append(
                    '@csrf',
                    $('<input>', { type: 'hidden', name: 'verification_ids', value: verificationIds.join(',') }),
                    $('<input>', { type: 'hidden', name: 'review_note', value: result.value })
                );
                $('body').append(form);
                form.submit();
            }
        });
    }

    $('#bulk-approve-btn').on('click', function() {
        performBulkApprove();
    });

    $('#bulk-reject-btn').on('click', function() {
        performBulkReject();
    });

    // Modal approve/reject buttons
    $('#modalApproveBtn').on('click', function() {
        const verificationId = $(this).data('verification-id');
        if (verificationId) {
            $('#verificationDetailModal').modal('hide');
            Swal.fire({
                title: 'Duyệt đơn xét duyệt?',
                text: "Bạn có chắc chắn muốn duyệt đơn này?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý duyệt',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('Đang duyệt đơn xét duyệt...');
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/verifications/${verificationId}/approve`
                    }).append('@csrf');
                    $('body').append(form);
                    form.submit();
                }
            });
        }
    });

    $('#modalRejectBtn').on('click', function() {
        const verificationId = $(this).data('verification-id');
        if (verificationId) {
            $('#verificationDetailModal').modal('hide');
            Swal.fire({
                title: 'Từ chối đơn xét duyệt?',
                text: "Vui lòng nhập lý do từ chối:",
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do từ chối (bắt buộc)...',
                inputAttributes: {
                    'aria-label': 'Nhập lý do từ chối',
                    'maxlength': 500
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý từ chối',
                cancelButtonText: 'Hủy',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Bạn phải nhập lý do từ chối!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('Đang từ chối đơn xét duyệt...'); // Thêm loading cho modal reject
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/verifications/${verificationId}/reject`
                    }).append(
                        '@csrf',
                        $('<input>', { type: 'hidden', name: 'review_note', value: result.value })
                    );
                    $('body').append(form);
                    form.submit();
                }
            });
        }
    });

    // Initial bind
    bindEvents();
});

// Function để toggle zoom hình ảnh
function toggleImageZoom(img) {
    const isZoomed = img.classList.contains('zoomed');
    
    if (isZoomed) {
        // Thu nhỏ về kích thước ban đầu
        img.classList.remove('zoomed');
        img.style.transform = 'scale(1)';
        img.style.cursor = 'zoom-in';
        img.style.maxWidth = '100%';
        img.style.width = 'auto';
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
        img.style.transform = 'scale(1.5)';
        img.style.cursor = 'zoom-out';
        img.style.maxWidth = 'none';
        img.style.width = '100%';
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
}
</script>

@if(session('success'))
<script>
    $(document).ready(function() {
        Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: '{{ session('success') }}',
            confirmButtonText: 'OK'
        });
    });
</script>
@endif

@if(session('error'))
<script>
    $(document).ready(function() {
        Swal.fire({
            icon: 'error',
            title: 'Có lỗi!',
            text: '{{ session('error') }}',
            confirmButtonText: 'OK'
        });
    });
</script>
@endif

@endpush
