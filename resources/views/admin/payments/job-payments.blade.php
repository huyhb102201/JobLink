@extends('admin.layouts.app')

@section('title', 'Quản lý thanh toán việc làm')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
    }

    #job-payments-table {
        width: 100% !important;
        table-layout: fixed;
    }

    #job-payments-table th,
    #job-payments-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #job-payments-table th:nth-child(1),
    #job-payments-table td:nth-child(1) {
        width: 50px;
        text-align: center;
    }

    #job-payments-table th:nth-child(2),
    #job-payments-table td:nth-child(2) {
        width: 15%;
        white-space: normal;
        font-size: 0.85rem;
        text-align: left;
    }

    #job-payments-table th:nth-child(3),
    #job-payments-table td:nth-child(3) {
        width: 20%;
    }

    #job-payments-table th:nth-child(4),
    #job-payments-table td:nth-child(4) {
        width: 20%;
    }

    #job-payments-table th:nth-child(5),
    #job-payments-table td:nth-child(5) {
        width: 15%;
        text-align: center;
    }

    #job-payments-table .btn-group {
        gap: 2px;
    }

    #job-payments-table .btn-group .btn {
        padding: 0.2rem 0.3rem;
        font-size: 0.75rem;
    }

    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }
</style>
@endpush

@section('content')
  <h1 class="h3 mb-4"><i class="fa-solid fa-briefcase-dollar me-2"></i>Quản lý thanh toán việc làm</h1>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tổng tiền đã thanh toán</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalPaidAmount ?? 0, 0, ',', '.') }} đ</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-coins fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Đang chờ/GD xử lý</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalPendingAmount ?? 0, 0, ',', '.') }} đ</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng số giao dịch</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalPayments ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Số giao dịch đã thanh toán</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalPaidTransactions ?? 0 }}</div>
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
      <h6 class="m-0 font-weight-bold text-primary"><i class="fa-solid fa-briefcase-dollar me-2"></i>Danh sách thanh toán việc làm</h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="job-payments-table">
          <thead class="table-light">
            <tr>
              <th width="15%">Client</th>
              <th width="18%">Tổng thanh toán</th>
              <th width="18%">Số lần thanh toán</th>
              <th width="12%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach($groupedPayments as $index => $group)
            <tr>
              <td style="text-align: left;">
                <div class="fw-semibold" style="font-size: 0.8rem;">{{ $group['client_name'] }}</div>
                <div class="text-muted" style="font-size: 0.65rem; line-height: 1.2;">{{ $group['client_email'] }}</div>
              </td>
              <td>
                <span class="badge bg-success">{{ number_format($group['total_amount']) }} đ</span>
              </td>
              <td>
                <span class="badge bg-info">{{ $group['payment_count'] }} lần</span>
              </td>
              <td>
                <button class="btn btn-primary btn-sm view-client-payments-btn" 
                        data-client-index="{{ $index }}" 
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

  <!-- Modal lịch sử thanh toán của job -->
  <div class="modal fade" id="jobPaymentsHistoryModal" tabindex="-1" aria-labelledby="jobPaymentsHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="jobPaymentsHistoryModalLabel">
            <i class="fas fa-history me-2"></i>Lịch sử thanh toán Job
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4" id="jobPaymentsHistoryContent">
          <!-- Content will be loaded by JavaScript -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Đóng
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal chi tiết thanh toán -->
  <div class="modal fade" id="jobPaymentDetailsModal" tabindex="-1" aria-labelledby="jobPaymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="jobPaymentDetailsModalLabel">
            <i class="fas fa-file-invoice-dollar me-2"></i>Chi tiết thanh toán Job
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Thông tin Job và Khách hàng -->
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white">
                  <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Thông tin Job</h6>
                </div>
                <div class="card-body">
                  <table class="table table-borderless mb-0">
                    <tr>
                      <td class="fw-bold" width="35%">Job ID:</td>
                      <td id="detail-job-id" class="text-primary">--</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Tiêu đề:</td>
                      <td id="detail-job-title">--</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Ngân sách:</td>
                      <td id="detail-job-budget" class="text-success fw-bold">--</td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card border-info h-100">
                <div class="card-header bg-info text-white">
                  <h6 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin khách hàng</h6>
                </div>
                <div class="card-body">
                  <table class="table table-borderless mb-0">
                    <tr>
                      <td class="fw-bold" width="35%">ID:</td>
                      <td id="detail-client-id" class="text-primary">--</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Tên:</td>
                      <td id="detail-client-fullname">--</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Email:</td>
                      <td id="detail-client-email">--</td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Thông tin thanh toán -->
          <div class="card border-success mb-3">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Thông tin thanh toán</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <div class="p-3 bg-light rounded">
                    <small class="text-muted d-block mb-1">Mã đơn hàng</small>
                    <div id="detail-order-code" class="text-primary fw-bold fs-5">--</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-3 bg-light rounded">
                    <small class="text-muted d-block mb-1">Số tiền</small>
                    <div id="detail-amount" class="text-success fw-bold fs-5">--</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-3 bg-light rounded">
                    <small class="text-muted d-block mb-1">Trạng thái</small>
                    <div id="detail-status" class="fs-5">--</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Thông tin bổ sung -->
          <div class="card border-secondary">
            <div class="card-header bg-secondary text-white">
              <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin bổ sung</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <small class="text-muted d-block mb-1">Tạo lúc</small>
                  <div id="detail-created" class="fw-bold">--</div>
                </div>
                <div class="col-md-4">
                  <small class="text-muted d-block mb-1">Cập nhật lúc</small>
                  <div id="detail-updated" class="fw-bold">--</div>
                </div>
                <div class="col-md-4">
                  <small class="text-muted d-block mb-1">Mô tả</small>
                  <div id="detail-description" class="text-muted fst-italic">--</div>
                </div>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Preload grouped payments data
const groupedPaymentsData = @json($groupedPayments);

$(document).ready(function() {
    const statusLabels = {
        pending: { badge: 'bg-warning text-dark', label: 'Chờ thanh toán' },
        processing: { badge: 'bg-primary', label: 'Đang xử lý' },
        paid: { badge: 'bg-success', label: 'Đã thanh toán' },
        canceled: { badge: 'bg-secondary', label: 'Đã hủy' },
        failed: { badge: 'bg-danger', label: 'Thất bại' },
    };

    const table = $('#job-payments-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        stateSave: true,
        searching: true,
        deferRender: true,
        autoWidth: false,
        columnDefs: [
            { orderable: false, targets: [3] },
            { className: 'text-center', targets: [3] }
        ],
        language: {
            lengthMenu: 'Hiển thị _MENU_ mục',
            zeroRecords: 'Không tìm thấy dữ liệu',
            info: 'Hiển thị _START_ đến _END_ của _TOTAL_ mục',
            infoEmpty: 'Hiển thị 0 đến 0 của 0 mục',
            infoFiltered: '(được lọc từ _MAX_ mục)',
            search: 'Tìm kiếm:',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Tiếp', previous: 'Trước' }
        }
    });

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount ?? 0) + ' đ';
    }

    // View client payments history button
    $('.view-client-payments-btn').on('click', function(e) {
        e.preventDefault();
        const clientIndex = $(this).data('client-index');
        
        // Find client data
        const clientData = groupedPaymentsData[clientIndex];
        
        if (!clientData) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không tìm thấy thông tin thanh toán'
            });
            return;
        }
        
        // Build payment history table with DataTables - grouped by jobs
        let paymentsHtml = `
            <div class="mb-3">
                <h6><i class="fas fa-user me-2"></i>Client: <strong>${clientData.client_name}</strong></h6>
                <p class="text-muted mb-1">Email: ${clientData.client_email}</p>
                <p class="text-muted">Tổng thanh toán (thành công): <span class="badge bg-success">${formatCurrency(clientData.total_amount)}</span></p>
                <p class="text-muted">Số lần thanh toán: <span class="badge bg-info">${clientData.payment_count} lần</span></p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="modalPaymentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Job</th>
                            <th>Mã đơn hàng</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Loop through all jobs of this client
        clientData.jobs.forEach(job => {
            job.payments.forEach(payment => {
                // Hiển thị TẤT CẢ giao dịch (paid, pending, failed...)
                paymentsHtml += `
                    <tr>
                        <td><small><strong>${job.job_title}</strong></small></td>
                        <td><code>${payment.orderCode}</code></td>
                        <td><span class="badge bg-info">${payment.amount_formatted} đ</span></td>
                        <td>${payment.status_badge}</td>
                        <td><small>${payment.created_at}</small></td>
                    </tr>
                `;
            });
        });
        
        paymentsHtml += `
                    </tbody>
                </table>
            </div>
        `;
        
        $('#jobPaymentsHistoryContent').html(paymentsHtml);
        
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
        
        $('#jobPaymentsHistoryModal').modal('show');
    });

    function applyStatus($row, status) {
        const config = statusLabels[status] ?? statusLabels['pending'];
        const badge = $row.find('.status-badge');
        badge.attr('class', 'badge status-badge ' + config.badge);
        badge.text(config.label);
    }

    $('.view-job-payment-btn').on('click', function() {
        const paymentId = $(this).data('payment-id');

        // Use preloaded data for instant display
        const payment = paymentDetailsData[paymentId];
        if (!payment) {
            Swal.fire('Lỗi', 'Không tìm thấy dữ liệu thanh toán.', 'error');
            return;
        }

        $('#detail-job-id').text(payment.job_id ?? '--');
        $('#detail-job-title').text(payment.job_title ?? '--');
        $('#detail-job-budget').text(payment.job_budget ? formatCurrency(payment.job_budget) : '--');
        $('#detail-client-id').text(payment.client_id ?? '--');
        $('#detail-client-fullname').text(payment.client_fullname ?? '--');
        $('#detail-client-email').text(payment.client_email ?? '--');
        $('#detail-order-code').text(payment.orderCode ?? '--');
        $('#detail-amount').text(payment.amount_formatted ? payment.amount_formatted + ' đ' : '--');
        
        const statusConfig = statusLabels[payment.status] ?? statusLabels['pending'];
        $('#detail-status').html(`<span class="badge ${statusConfig.badge}">${statusConfig.label}</span>`);
        
        $('#detail-created').text(payment.created_at ?? '--');
        $('#detail-updated').text(payment.updated_at ?? '--');
        $('#detail-description').text(payment.description ?? '--');

        const modal = new bootstrap.Modal(document.getElementById('jobPaymentDetailsModal'));
        modal.show();
    });

});
</script>
@endpush
