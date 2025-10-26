@extends('admin.layouts.app')

@section('title', 'Lịch sử hoạt động Admin')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
    }

    #logs-table {
        width: 100% !important;
        table-layout: fixed;
    }

    #logs-table th,
    #logs-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #logs-table th:nth-child(1),
    #logs-table td:nth-child(1) {
        width: 50px;
        text-align: center;
    }

    #logs-table th:nth-child(2),
    #logs-table td:nth-child(2) {
        width: 180px;
        white-space: normal;
    }

    #logs-table th:nth-child(3),
    #logs-table td:nth-child(3) {
        width: 110px;
    }

    #logs-table th:nth-child(4),
    #logs-table td:nth-child(4) {
        width: 120px;
    }

    #logs-table th:nth-child(5),
    #logs-table td:nth-child(5) {
        width: 250px;
        white-space: normal;
    }

    #logs-table th:nth-child(6),
    #logs-table td:nth-child(6) {
        width: 130px;
    }

    #logs-table th:nth-child(7),
    #logs-table td:nth-child(7) {
        width: 60px;
        text-align: center;
    }

    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }

    .json-viewer {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        max-height: 300px;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
  <h1 class="h3 mb-4"><i class="fa-solid fa-history me-2"></i>Lịch sử hoạt động Admin</h1>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng số logs</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalLogs ?? 0) }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-database fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Hôm nay</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $todayLogs ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Số admin hoạt động</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $uniqueAdmins ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-users fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">24 giờ qua</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $recentActions ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary"><i class="fa-solid fa-list me-2"></i>Danh sách hoạt động</h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="logs-table">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Admin</th>
              <th>Hành động</th>
              <th>Model</th>
              <th>Mô tả</th>
              <th>Thời gian</th>
              <th>Chi tiết</th>
            </tr>
          </thead>
          <tbody>
            @foreach($logs as $log)
            <tr>
              <td>{{ $log->id }}</td>
              <td>
                <div><strong>{{ $log->admin->profile->fullname ?? $log->admin->email ?? 'System' }}</strong></div>
                <div class="text-muted small">{{ $log->admin->email ?? 'N/A' }}</div>
              </td>
              <td>
                @php
                  $actionBadge = match($log->action) {
                    'create' => 'bg-success',
                    'update' => 'bg-primary',
                    'delete', 'bulk_delete' => 'bg-danger',
                    'approve', 'bulk_approve' => 'bg-info',
                    'reject', 'bulk_reject' => 'bg-warning text-dark',
                    'status_change' => 'bg-secondary',
                    default => 'bg-secondary',
                  };
                  $actionLabel = match($log->action) {
                    'create' => 'Tạo mới',
                    'update' => 'Cập nhật',
                    'delete' => 'Xóa',
                    'approve' => 'Duyệt',
                    'reject' => 'Từ chối',
                    'status_change' => 'Đổi TT',
                    'bulk_delete' => 'Xóa loạt',
                    'bulk_approve' => 'Duyệt loạt',
                    'bulk_reject' => 'Từ chối loạt',
                    'bulk_lock' => 'Khóa loạt',
                    'bulk_unlock' => 'Mở loạt',
                    default => ucfirst($log->action),
                  };
                @endphp
                <span class="badge {{ $actionBadge }}">{{ $actionLabel }}</span>
              </td>
              <td>
                <div><strong>{{ $log->model }}</strong></div>
                @if($log->model_id)
                  <div class="text-muted small">ID: {{ $log->model_id }}</div>
                @endif
              </td>
              <td title="{{ $log->description }}">{{ $log->description ?? '--' }}</td>
              <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
              <td>
                <button class="btn btn-info btn-sm view-log-btn" data-log-id="{{ $log->id }}" title="Xem chi tiết">
                  <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal chi tiết log -->
  <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="logDetailsModalLabel">Chi tiết hoạt động</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <h6 class="fw-bold">Thông tin Admin</h6>
              <ul class="list-unstyled mb-0">
                <li><strong>Tên:</strong> <span id="detail-admin-name">--</span></li>
                <li><strong>Email:</strong> <span id="detail-admin-email">--</span></li>
                <li><strong>IP:</strong> <span id="detail-ip">--</span></li>
              </ul>
            </div>
            <div class="col-md-6">
              <h6 class="fw-bold">Thông tin hành động</h6>
              <ul class="list-unstyled mb-0">
                <li><strong>Hành động:</strong> <span id="detail-action">--</span></li>
                <li><strong>Model:</strong> <span id="detail-model">--</span></li>
                <li><strong>Model ID:</strong> <span id="detail-model-id">--</span></li>
                <li><strong>Thời gian:</strong> <span id="detail-created">--</span></li>
              </ul>
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <h6 class="fw-bold">Mô tả</h6>
            <p id="detail-description" class="text-muted">--</p>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <h6 class="fw-bold">Giá trị cũ</h6>
              <div id="detail-old-values" class="json-viewer">--</div>
            </div>
            <div class="col-md-6">
              <h6 class="fw-bold">Giá trị mới</h6>
              <div id="detail-new-values" class="json-viewer">--</div>
            </div>
          </div>

          <hr>

          <div class="mb-0">
            <h6 class="fw-bold">User Agent</h6>
            <p id="detail-user-agent" class="text-muted small mb-0">--</p>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Preload log details from server
const logDetailsData = @json($logDetails ?? []);

$(document).ready(function() {
    const table = $('#logs-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true,
        order: [[0, 'desc']],
        paging: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        stateSave: true,
        searching: true,
        deferRender: true,
        autoWidth: false,
        columnDefs: [
            { orderable: false, targets: [6] },
            { className: 'text-center', targets: [0, 6] }
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

    function formatJSON(data) {
        if (!data || Object.keys(data).length === 0) {
            return '<span class="text-muted">Không có dữ liệu</span>';
        }
        return '<pre class="mb-0">' + JSON.stringify(data, null, 2) + '</pre>';
    }

    $('.view-log-btn').on('click', function() {
        const logId = $(this).data('log-id');
        const log = logDetailsData[logId];
        
        if (!log) {
            Swal.fire('Lỗi', 'Không tìm thấy dữ liệu log.', 'error');
            return;
        }

        $('#detail-admin-name').text(log.admin_name ?? '--');
        $('#detail-admin-email').text(log.admin_email ?? '--');
        $('#detail-ip').text(log.ip_address ?? '--');
        $('#detail-action').html(`<span class="badge bg-primary">${log.action_label}</span>`);
        $('#detail-model').text(log.model ?? '--');
        $('#detail-model-id').text(log.model_id ?? '--');
        $('#detail-created').text(log.created_at ?? '--');
        $('#detail-description').text(log.description ?? 'Không có mô tả');
        $('#detail-old-values').html(formatJSON(log.old_values));
        $('#detail-new-values').html(formatJSON(log.new_values));
        $('#detail-user-agent').text(log.user_agent ?? '--');

        const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
        modal.show();
    });
});
</script>
@endpush
