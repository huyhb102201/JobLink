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
    #payments-table td:nth-child(1) { 
        width: 300px; 
        white-space: normal;
    } /* Tài khoản */
    
    #payments-table th:nth-child(2),
    #payments-table td:nth-child(2) { width: 150px; } /* Tổng thanh toán */
    
    #payments-table th:nth-child(3),
    #payments-table td:nth-child(3) { width: 150px; } /* Số lần thanh toán */
    
    #payments-table th:nth-child(4),
    #payments-table td:nth-child(4) { 
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
        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#membershipPlansModal">
          <i class="fa-solid fa-crown me-1"></i> Quản lý gói Membership
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="payments-table">
          <thead class="table-light">
            <tr>
              <th>Tài khoản</th>
              <th>Tổng thanh toán</th>
              <th>Số lần thanh toán</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach($groupedPayments as $index => $group)
            <tr>
              <td>
                <div class="user-info">
                  <img src="{{ $group['account_avatar'] }}" 
                       alt="Avatar" 
                       class="user-avatar" 
                       onerror="this.src='{{ asset('images/man.jpg') }}'"
                       loading="lazy">
                  <div class="user-details">
                    <div class="user-name">{{ $group['account_name'] }}</div>
                    <div class="user-id" style="font-size: 0.7rem;">{{ $group['account_email'] }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-success">{{ number_format($group['total_amount']) }} đ</span>
              </td>
              <td>
                <span class="badge bg-info">{{ $group['payment_count'] }} lần</span>
              </td>
              <td>
                <button class="btn btn-primary btn-sm view-account-payments-btn" 
                        data-account-index="{{ $index }}" 
                        title="Xem chi tiết">
                  <i class="fas fa-eye"></i> Chi tiết
                </button>
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
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="membershipPlansModalLabel">
            <i class="fas fa-crown me-2"></i>Quản lý gói Membership
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Form thêm gói membership mới -->
          <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm gói Membership mới</h6>
            </div>
            <div class="card-body">
              <form id="addMembershipPlanForm">
                <div class="row">
                  <div class="col-md-3">
                    <label class="form-label">Tên gói</label>
                    <input type="text" class="form-control" id="newPlanName" name="name" required placeholder="VD: Premium Plan">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Loại tài khoản</label>
                    <select class="form-select" id="newPlanAccountType" name="account_type_id" required>
                      <option value="">-- Chọn loại --</option>
                      @foreach($accountTypes as $type)
                        <option value="{{ $type->account_type_id }}">{{ $type->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Giá (VNĐ)</label>
                    <input type="number" class="form-control" id="newPlanPrice" name="price" required placeholder="99000">
                  </div>
                  <div class="col-md-3">
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
          <div class="card border-primary">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách gói Membership hiện có</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-hover" id="membershipPlansTable">
                  <thead class="table-light">
                    <tr>
                      <th width="18%">Tên gói</th>
                      <th width="12%">Loại TK</th>
                      <th width="12%">Giá</th>
                      <th width="10%">Thời hạn</th>
                      <th width="8%">Giảm giá</th>
                      <th width="20%">Mô tả</th>
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
                        <span class="badge bg-secondary">{{ $plan->accountType->name ?? 'N/A' }}</span>
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
                                data-plan-popular="{{ $plan->is_popular ? 1 : 0 }}"
                                data-plan-active="{{ $plan->is_active ? 1 : 0 }}">
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Đóng
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal xem chi tiết thanh toán của tài khoản -->
  <div class="modal fade" id="accountPaymentsModal" tabindex="-1" aria-labelledby="accountPaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="accountPaymentsModalLabel">Lịch sử thanh toán</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="accountPaymentsContent">
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
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="edit-plan-active" name="is_active" checked>
                <label class="form-check-label" for="edit-plan-active">
                  Trạng thái hoạt động
                </label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            <button type="button" class="btn btn-primary" onclick="submitEditForm()">Cập nhật</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Function để submit edit form - GLOBAL SCOPE
function submitEditForm() {
    console.log('=== submitEditForm() CALLED ===');
    
    const form = $('#editMembershipPlanForm');
    const actionUrl = form.attr('action');
    
    console.log('Form:', form);
    console.log('Action URL:', actionUrl);
    console.log('Form data:', form.serialize());
    
    if (!actionUrl || actionUrl === '') {
        alert('Lỗi: Không tìm thấy URL cập nhật');
        return;
    }
    
    // Show global loading
    showLoading('Đang cập nhật gói membership...');
    
    $.ajax({
        url: actionUrl,
        method: 'POST',
        data: form.serialize() + '&_method=PUT',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            hideLoading();
            console.log('Success:', response);
            if (response.success) {
                // Đóng modal ngay lập tức
                $('#editMembershipPlanModal').modal('hide');
                
                // Hiển thị thông báo và reload ngay
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Đã cập nhật gói membership',
                    timer: 1000,
                    showConfirmButton: false,
                    allowOutsideClick: false
                });
                
                // Reload sau 1 giây
                setTimeout(function() {
                    location.reload(true); // Force reload từ server
                }, 1000);
            }
        },
        error: function(xhr) {
            hideLoading();
            console.error('Error:', xhr);
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: xhr.responseJSON?.message || 'Có lỗi xảy ra'
            });
        }
    });
}

// Preload grouped payments data
const groupedPaymentsData = @json($groupedPayments);

$(document).ready(function() {

    // Khởi tạo DataTables tối ưu - vừa đẹp vừa nhanh
    const table = $('#payments-table').DataTable({
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
            { orderable: false, targets: [3] }, // Tắt sort cho action
            { className: "text-center", targets: [3] }
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
        // View account payments button
        $('.view-account-payments-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const accountIndex = $(this).data('account-index');
            
            // Find account data by index
            const accountData = groupedPaymentsData[accountIndex];
            
            if (!accountData) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Không tìm thấy thông tin thanh toán'
                });
                return;
            }
            
            // Build payment history table with DataTables for pagination
            let paymentsHtml = `
                <div class="mb-4">
                    <div class="card border-primary">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-user me-2 text-primary"></i>Thông tin tài khoản</h6>
                                    <p class="mb-1"><strong>Tên:</strong> ${accountData.account_name}</p>
                                    <p class="mb-1"><strong>Email:</strong> ${accountData.account_email}</p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6><i class="fas fa-money-bill-wave me-2 text-success"></i>Thống kê thanh toán</h6>
                                    <p class="mb-1"><strong>Tổng số đơn:</strong> <span class="badge bg-primary">${accountData.payments.length}</span></p>
                                    <p class="mb-1"><strong>Tổng tiền (thành công):</strong> <span class="badge bg-success fs-6">${new Intl.NumberFormat().format(accountData.total_amount)} đ</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm" id="modalPaymentsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Gói Membership</th>
                                <th>Số tiền</th>
                                <th>Phương thức</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            accountData.payments.forEach(payment => {
                const paymentMethod = payment.payment_method || 'Chưa xác định';
                paymentsHtml += `
                    <tr>
                        <td><code class="text-primary">${payment.order_code}</code></td>
                        <td><strong>${payment.plan_name}</strong></td>
                        <td><span class="badge bg-info fs-6">${payment.amount_formatted} đ</span></td>
                        <td><span class="badge bg-secondary">${paymentMethod}</span></td>
                        <td>${payment.status_badge}</td>
                        <td><small>${payment.created_at}</small></td>
                    </tr>
                `;
            });
            
            paymentsHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            
            $('#accountPaymentsContent').html(paymentsHtml);
            
            // Destroy existing DataTable if exists
            if ($.fn.DataTable.isDataTable('#modalPaymentsTable')) {
                $('#modalPaymentsTable').DataTable().destroy();
            }
            
            // Initialize DataTables with pagination (client-side, instant)
            $('#modalPaymentsTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ordering: true,
                searching: false,
                language: {
                    lengthMenu: "Hiển thị _MENU_ mục",
                    zeroRecords: "Không tìm thấy dữ liệu",
                    info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                    infoEmpty: "Hiển thị 0 đến 0 của 0 mục",
                    paginate: { first: "Đầu", last: "Cuối", next: "Tiếp", previous: "Trước" }
                }
            });
            
            $('#accountPaymentsModal').modal('show');
        });
    }


    // Membership Plan Management
    $('#addMembershipPlanForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#newPlanName').val(),
            account_type_id: $('#newPlanAccountType').val(),
            price: $('#newPlanPrice').val(),
            duration_days: $('#newPlanDuration').val(),
            description: $('#newPlanDescription').val(),
            is_active: $('#newPlanActive').is(':checked') ? 1 : 0
        };
        
        showLoading('Đang thêm gói membership...');
        
        $.ajax({
            url: '/admin/membership-plans',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();
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
                                <span class="badge bg-secondary">${plan.account_type?.name || 'N/A'}</span>
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
                                <span class="badge ${plan.is_active ? 'bg-success' : 'bg-secondary'}">${plan.is_active ? 'Hoạt động' : 'Tạm dừng'}</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-plan-btn" 
                                        data-plan-id="${plan.plan_id}"
                                        data-plan-name="${plan.name || plan.tagline}"
                                        data-plan-price="${plan.price}"
                                        data-plan-discount="${plan.discount_percent || 0}"
                                        data-plan-popular="${plan.is_popular ? 1 : 0}"
                                        data-plan-active="${plan.is_active ? 1 : 0}">
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
                hideLoading();
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
        const planActive = $(this).data('plan-active');
        
        // Populate modal fields
        $('#edit-plan-name').val(planName);
        $('#edit-plan-price').val(planPrice);
        $('#edit-plan-discount').val(planDiscount);
        $('#edit-plan-popular').prop('checked', planPopular == 1);
        $('#edit-plan-active').prop('checked', planActive == 1);
        
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
            text: "Hệ thống sẽ kiểm tra xem có người dùng nào đang sử dụng gói này không trước khi xóa.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý xóa!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Đang xóa gói membership...');
                
                // Dùng AJAX để không reload trang
                $.ajax({
                    url: `/admin/membership-plans/${planId}`,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        hideLoading();
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
                        hideLoading();
                        Swal.fire({
                            icon: 'error',
                            title: 'Xóa không thành công!',
                            text: xhr.responseJSON?.message || 'Không thể xóa gói membership này',
                            confirmButtonText: 'Đã hiểu'
                        });
                    }
                });
            }
        });
    });

    // Edit membership plan form submit - Sử dụng document delegation
    $(document).on('submit', '#editMembershipPlanForm', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('=== EDIT FORM SUBMIT TRIGGERED ===');
        
        const form = $(this);
        const actionUrl = form.attr('action');
        
        console.log('Form:', form);
        console.log('Action URL:', actionUrl);
        console.log('Form data:', form.serialize());
        
        // Kiểm tra action URL có hợp lệ không
        if (!actionUrl || actionUrl === '' || actionUrl === 'undefined') {
            console.error('Invalid action URL');
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không tìm thấy URL cập nhật. Vui lòng thử lại.'
            });
            return false;
        }
        
        console.log('Sending AJAX request...');
        
        $.ajax({
            url: actionUrl,
            method: 'POST', // Sử dụng POST thay vì PUT
            data: form.serialize() + '&_method=PUT', // Thêm _method=PUT vào data
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                console.log('AJAX beforeSend');
                showLoading('Đang thêm gói membership...');
            },
            success: function(response) {
                hideLoading();
                console.log('AJAX Success response:', response);
                if (response.success) {
                    $('#editMembershipPlanModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã thêm gói membership',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Reload to see changes
                    });
                }
            },
            error: function(xhr) {
                hideLoading();
                console.error('AJAX Error response:', xhr);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseJSON);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi cập nhật'
                });
            }
        });
        
        return false;
    });

    // Export Excel functionality
    $('#export-excel-btn').on('click', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        // Show loading state
        showLoading('Đang xuất file Excel...');
        
        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = btn.attr('href');
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show success message
        setTimeout(() => {
            hideLoading();
            
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
