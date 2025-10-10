@extends('admin.layouts.app')

@section('title', 'Quản lý thanh toán')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Fix layout cho bảng */
    #payments-table {
        width: 100% !important;
        table-layout: fixed;
    }
    
    #payments-table th,
    #payments-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Định width cho từng cột */
    #payments-table th:nth-child(1),
    #payments-table td:nth-child(1) { width: 50px; } /* Checkbox */
    
    #payments-table th:nth-child(2),
    #payments-table td:nth-child(2) { 
        width: 200px; 
        white-space: normal;
    } /* Người dùng */
    
    #payments-table th:nth-child(3),
    #payments-table td:nth-child(3) { width: 150px; } /* Mã đơn hàng */
    
    #payments-table th:nth-child(4),
    #payments-table td:nth-child(4) { width: 120px; } /* Số tiền */
    
    #payments-table th:nth-child(5),
    #payments-table td:nth-child(5) { width: 100px; } /* Trạng thái */
    
    #payments-table th:nth-child(6),
    #payments-table td:nth-child(6) { width: 130px; } /* Ngày tạo */
    
    #payments-table th:nth-child(7),
    #payments-table td:nth-child(7) { 
        width: 120px; 
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
    
    /* Fix avatar */
    .user-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 0.75rem;
        border: 2px solid #e3e6f0;
        background-color: #f8f9fc;
        display: block;
        flex-shrink: 0;
    }
    
    .user-avatar:hover {
        border-color: #5a5c69;
        transform: scale(1.05);
        transition: all 0.2s ease;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        min-width: 0;
    }
    
    .user-details {
        min-width: 0;
        flex: 1;
    }
    
    .user-name {
        font-weight: 600;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .user-id {
        font-size: 0.75rem;
        color: #6c757d;
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
</style>
@endpush

@section('content')
  <h1 class="h3 mb-4">Quản lý thanh toán</h1>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Doanh thu hôm nay</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalRevenueToday ?? 0) }} đ</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đơn hàng hôm nay</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalOrdersToday ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tổng doanh thu</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalRevenue ?? 0) }} đ</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Giao dịch thành công</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSuccessTransactions ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-check-circle fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Danh sách giao dịch</h6>
      <div>
        <a href="{{ route('admin.payments.export') }}" class="btn btn-success btn-sm me-2" id="export-excel-btn">
          <i class="fas fa-file-excel me-1"></i> Xuất Excel
        </a>
        <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#membershipPlansModal">
          <i class="fa-solid fa-crown me-1"></i> Quản lý gói Membership
        </button>
        <button id="bulk-delete-btn" type="button" class="btn btn-danger btn-sm" disabled>
          <i class="fa-solid fa-trash me-1"></i> Xóa đã chọn
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="payments-table">
          <thead class="table-light">
            <tr>
              <th><input type="checkbox" class="form-check-input" id="checkAll"></th>
              <th>Người dùng</th>
              <th>Mã đơn hàng</th>
              <th>Số tiền</th>
              <th>Trạng thái</th>
              <th>Ngày tạo</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach($payments as $payment)
            <tr>
              <td>
                <input type="checkbox" class="form-check-input row-checkbox" data-payment-id="{{ $payment->payment_id }}">
              </td>
              <td>
                <div class="user-info">
                  <img src="{{ $payment->account->avatar_url ?: asset('images/man.jpg') }}" 
                       alt="Avatar" 
                       class="user-avatar" 
                       onerror="this.src='{{ asset('images/man.jpg') }}'"
                       loading="lazy">
                  <div class="user-details">
                    <div class="user-name">{{ $payment->account->profile->fullname ?? $payment->account->name ?? 'N/A' }}</div>
                    <div class="user-id">{{ $payment->account->email }}</div>
                  </div>
                </div>
              </td>
              <td>{{ $payment->order_code ?? 'N/A' }}</td>
              <td>
                <span class="badge bg-info">{{ number_format($payment->amount) }} đ</span>
              </td>
              <td>
                @if($payment->status == 'success')
                  <span class="badge bg-success">Thành công</span>
                @elseif($payment->status == 'failed')
                  <span class="badge bg-danger">Thất bại</span>
                @else
                  <span class="badge bg-warning">Đang chờ</span>
                @endif
              </td>
              <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
              <td>
                <div class="btn-group" role="group">
                  <button class="btn btn-info btn-sm view-payment-btn" 
                          data-payment-id="{{ $payment->payment_id }}" 
                          title="Xem chi tiết">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="btn btn-danger btn-sm delete-payment-btn" 
                          data-payment-id="{{ $payment->payment_id }}" 
                          title="Xóa">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal quản lý Membership Plans -->
  <div class="modal fade" id="membershipPlansModal" tabindex="-1" aria-labelledby="membershipPlansModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="membershipPlansModalLabel">Quản lý gói Membership</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Form thêm gói membership mới -->
          <div class="card mb-3">
            <div class="card-header">
              <h6 class="mb-0">Thêm gói Membership mới</h6>
            </div>
            <div class="card-body">
              <form id="addMembershipPlanForm">
                <div class="row">
                  <div class="col-md-4">
                    <label class="form-label">Tên gói</label>
                    <input type="text" class="form-control" id="newPlanName" name="name" required placeholder="VD: Premium Plan">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Giá (VNĐ)</label>
                    <input type="number" class="form-control" id="newPlanPrice" name="price" required placeholder="99000">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Thời hạn (ngày)</label>
                    <input type="number" class="form-control" id="newPlanDuration" name="duration_days" required placeholder="30">
                  </div>
                </div>
                <div class="row mt-3">
                  <div class="col-md-8">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" id="newPlanDescription" name="description" rows="2" placeholder="Mô tả về gói membership này..."></textarea>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Trạng thái</label>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="newPlanActive" name="is_active" checked>
                      <label class="form-check-label" for="newPlanActive">
                        Kích hoạt
                      </label>
                    </div>
                  </div>
                </div>
                <div class="mt-3">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Thêm gói Membership
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Danh sách gói membership hiện có -->
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">Danh sách gói Membership hiện có</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-hover" id="membershipPlansTable">
                  <thead class="table-light">
                    <tr>
                      <th width="20%">Tên gói</th>
                      <th width="15%">Giá</th>
                      <th width="10%">Thời hạn</th>
                      <th width="10%">Giảm giá</th>
                      <th width="25%">Mô tả</th>
                      <th width="10%">Trạng thái</th>
                      <th width="10%">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody id="membershipPlansTableBody">
                    @foreach($membershipPlans as $plan)
                    <tr data-plan-id="{{ $plan->id }}">
                      <td>
                        <strong>{{ $plan->name ?? $plan->tagline ?? 'Không có tên' }}</strong>
                        @if($plan->is_popular)
                          <span class="badge bg-warning ms-1">Popular</span>
                        @endif
                      </td>
                      <td>
                        <span class="text-success fw-bold">{{ number_format($plan->price) }} đ</span>
                      </td>
                      <td>{{ $plan->duration_days ? $plan->duration_days . ' ngày' : 'ngày' }}</td>
                      <td>
                        @if($plan->discount_percent > 0)
                          <span class="badge bg-danger">-{{ $plan->discount_percent }}%</span>
                        @else
                          <span class="text-muted">Không</span>
                        @endif
                      </td>
                      <td>{{ $plan->description ?? 'Không có mô tả' }}</td>
                      <td>
                        @if($plan->is_active)
                          <span class="badge bg-success">Hoạt động</span>
                        @else
                          <span class="badge bg-secondary">Tạm dừng</span>
                        @endif
                      </td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary edit-plan-btn" 
                                data-plan-id="{{ $plan->plan_id }}"
                                data-plan-name="{{ $plan->name ?? $plan->tagline }}"
                                data-plan-price="{{ $plan->price }}"
                                data-plan-discount="{{ $plan->discount_percent ?? 0 }}"
                                data-plan-popular="{{ $plan->is_popular ? 1 : 0 }}">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-plan-btn" 
                                data-plan-id="{{ $plan->plan_id }}">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal xem chi tiết thanh toán -->
  <div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="paymentDetailsModalLabel">Chi tiết giao dịch</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="paymentDetailsContent">
          <!-- Nội dung sẽ được load bằng JavaScript -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal chỉnh sửa Membership Plan -->
  <div class="modal fade" id="editMembershipPlanModal" tabindex="-1" aria-labelledby="editMembershipPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editMembershipPlanModalLabel">Chỉnh sửa gói Membership</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="editMembershipPlanForm" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Tên gói</label>
              <input type="text" class="form-control" id="edit-plan-name" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Giá mới (VNĐ)</label>
              <input type="number" class="form-control" id="edit-plan-price" name="price" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Giảm giá (%)</label>
              <input type="number" class="form-control" id="edit-plan-discount" name="discount_percent" min="0" max="100" value="0">
            </div>
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="edit-plan-popular" name="is_popular">
                <label class="form-check-label" for="edit-plan-popular">
                  Đánh dấu là gói phổ biến
                </label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Preload payment details
const paymentDetailsData = @json($paymentDetails);

$(document).ready(function() {
    let selectedPayments = new Set();

    function updateButtonStates() {
        const selectedCount = selectedPayments.size;
        const isDisabled = selectedCount === 0;
        $('#bulk-delete-btn').prop('disabled', isDisabled);
    }

    // Khởi tạo DataTables tối ưu - vừa đẹp vừa nhanh
    const table = $('#payments-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true,
        paging: true,
        pageLength: 25,
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
            bindEvents();
        }
    });

    function bindEvents() {
        // Checkbox events
        $('.row-checkbox').off('change').on('change', function() {
            const paymentId = $(this).data('payment-id');
            if (this.checked) {
                selectedPayments.add(paymentId);
            } else {
                selectedPayments.delete(paymentId);
            }
            updateButtonStates();
        });

        // Delete button events
        $('.delete-payment-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const paymentId = $(this).data('payment-id');
            
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                text: "Bạn sẽ xóa giao dịch này. Hành động này không thể hoàn tác!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Đồng ý, xóa!',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/payments/${paymentId}`
                    }).append(
                        '@csrf',
                        '@method("DELETE")'
                    );
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // View button events – sử dụng dữ liệu đã preload
        $('.view-payment-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const paymentId = $(this).data('payment-id');
            const payment = paymentDetailsData[paymentId];

            $('#paymentDetailsModal').modal('show');

            if (!payment) {
                $('#paymentDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không thể tải thông tin giao dịch
                    </div>
                `);
                return;
            }

            const customerName = payment.account.fullname || payment.account.name || 'N/A';
            const customerEmail = payment.account.email || 'N/A';
            const plan = payment.plan;

            const planHtml = plan ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-3"><i class="fas fa-crown me-2"></i>Thông tin gói Membership</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="20%" class="text-muted">Tên gói:</td>
                                <td><strong>${plan.name || 'Không có'}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Giá gói:</td>
                                <td><span class="text-success fw-bold">${plan.price_formatted || '0'} đ</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Thời hạn:</td>
                                <td>${plan.duration_days || 0} ngày</td>
                            </tr>
                        </table>
                    </div>
                </div>
            ` : '';

            const descriptionHtml = payment.description ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-2"><i class="fas fa-info-circle me-2"></i>Mô tả</h6>
                        <p class="text-muted">${payment.description}</p>
                    </div>
                </div>
            ` : '';

            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Thông tin khách hàng</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%" class="text-muted">Họ tên:</td>
                                <td><strong>${customerName}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email:</td>
                                <td>${customerEmail}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">ID tài khoản:</td>
                                <td><code>${payment.account.id}</code></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-receipt me-2"></i>Thông tin giao dịch</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%" class="text-muted">Mã giao dịch:</td>
                                <td><code>${payment.payment_id}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mã đơn hàng:</td>
                                <td><strong>${payment.order_code}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số tiền:</td>
                                <td><span class="badge bg-info">${payment.amount_formatted} đ</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Trạng thái:</td>
                                <td>${payment.status_badge}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ngày tạo:</td>
                                <td>${payment.created_at}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                ${planHtml}
                ${descriptionHtml}
            `;

            $('#paymentDetailsContent').html(content);
        });
    }

    // Check all functionality
    $('#checkAll').on('change', function() {
        const isChecked = this.checked;
        $('.row-checkbox').each(function() {
            this.checked = isChecked;
            const paymentId = $(this).data('payment-id');
            if (isChecked) {
                selectedPayments.add(paymentId);
            } else {
                selectedPayments.delete(paymentId);
            }
        });
        updateButtonStates();
    });

    // Bulk delete
    $('#bulk-delete-btn').on('click', function() {
        if (selectedPayments.size === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn ít nhất một giao dịch', 'warning');
            return;
        }

        const paymentIds = Array.from(selectedPayments);

        Swal.fire({
            title: `Bạn có chắc chắn muốn xóa ${paymentIds.length} giao dịch đã chọn?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý xóa!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = $('<form>', {
                    method: 'POST',
                    action: '/admin/payments/delete-multiple'
                }).append(
                    '@csrf',
                    $('<input>', { type: 'hidden', name: 'ids', value: paymentIds.join(',') })
                );
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Membership Plan Management
    $('#addMembershipPlanForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#newPlanName').val(),
            price: $('#newPlanPrice').val(),
            duration_days: $('#newPlanDuration').val(),
            description: $('#newPlanDescription').val(),
            is_active: $('#newPlanActive').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: '/admin/membership-plans',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Add new row to table
                    const plan = response.plan;
                    const newRow = `
                        <tr data-plan-id="${plan.plan_id}">
                            <td>
                                <strong>${plan.name || plan.tagline || 'Không có tên'}</strong>
                                ${plan.is_popular ? '<span class="badge bg-warning ms-1">Popular</span>' : ''}
                            </td>
                            <td>
                                <span class="text-success fw-bold">${new Intl.NumberFormat().format(plan.price || 0)} đ</span>
                            </td>
                            <td>${plan.duration_days || 0} ngày</td>
                            <td>
                                ${plan.discount_percent > 0 ? 
                                    `<span class="badge bg-danger">-${plan.discount_percent}%</span>` : 
                                    '<span class="text-muted">Không</span>'}
                            </td>
                            <td>${plan.description || 'Không có mô tả'}</td>
                            <td>
                                <span class="badge bg-success">Tạm dừng</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-plan-btn" 
                                        data-plan-id="${plan.plan_id}"
                                        data-plan-name="${plan.name || plan.tagline}"
                                        data-plan-price="${plan.price}"
                                        data-plan-discount="${plan.discount_percent || 0}"
                                        data-plan-popular="${plan.is_popular ? 1 : 0}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-plan-btn" 
                                        data-plan-id="${plan.plan_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    $('#membershipPlansTableBody').append(newRow);
                    
                    // Reset form
                    $('#addMembershipPlanForm')[0].reset();
                    $('#newPlanActive').prop('checked', true);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã thêm gói membership mới',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi thêm gói membership'
                });
            }
        });
    });

    // Edit membership plan
    $(document).on('click', '.edit-plan-btn', function() {
        const planId = $(this).data('plan-id');
        const planName = $(this).data('plan-name');
        const planPrice = $(this).data('plan-price');
        const planDiscount = $(this).data('plan-discount');
        const planPopular = $(this).data('plan-popular');
        
        // Populate modal fields
        $('#edit-plan-name').val(planName);
        $('#edit-plan-price').val(planPrice);
        $('#edit-plan-discount').val(planDiscount);
        $('#edit-plan-popular').prop('checked', planPopular == 1);
        
        // Set form action
        $('#editMembershipPlanForm').attr('action', `/admin/membership-plans/${planId}`);
        
        // Show modal
        $('#editMembershipPlanModal').modal('show');
    });

    // Delete membership plan
    $(document).on('click', '.delete-plan-btn', function() {
        const planId = $(this).data('plan-id');
        const row = $(this).closest('tr');
        
        console.log('Delete button clicked, planId:', planId);
        
        if (!planId) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không tìm thấy ID gói membership'
            });
            return;
        }
        
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Xóa gói membership này có thể ảnh hưởng đến các giao dịch đang sử dụng!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý xóa!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                // Dùng AJAX để không reload trang
                $.ajax({
                    url: `/admin/membership-plans/${planId}`,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Xóa row khỏi bảng
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã xóa!',
                            text: 'Gói membership đã được xóa thành công',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: xhr.responseJSON?.message || 'Không thể xóa gói membership này'
                        });
                    }
                });
            }
        });
    });

    // Edit membership plan form submit
    $('#editMembershipPlanForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const actionUrl = form.attr('action');
        
        $.ajax({
            url: actionUrl,
            method: 'PUT',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#editMembershipPlanModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã cập nhật gói membership',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Reload to see changes
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi cập nhật'
                });
            }
        });
    });

    // Export Excel functionality
    $('#export-excel-btn').on('click', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const originalText = btn.html();
        
        // Show loading state
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Đang xuất...');
        btn.prop('disabled', true);
        
        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = btn.attr('href');
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show success message and restore button
        setTimeout(() => {
            btn.html(originalText);
            btn.prop('disabled', false);
            
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: 'File Excel đã được tải xuống',
                timer: 2000,
                showConfirmButton: false
            });
        }, 1500);
    });

    // Initial bind
    bindEvents();
});
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
