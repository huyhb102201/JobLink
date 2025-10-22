@extends('admin.layouts.app')

@section('title', 'Quản lý tài khoản')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Fix layout cho bảng */
    #accounts-table {
        width: 100% !important;
        table-layout: fixed;
    }
    
    #accounts-table th,
    #accounts-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Loại bỏ custom sorting CSS để tăng tốc */
    
    /* Định width cho từng cột */
    #accounts-table th:nth-child(1),
    #accounts-table td:nth-child(1) { width: 50px; } /* Checkbox */
    
    #accounts-table th:nth-child(2),
    #accounts-table td:nth-child(2) { 
        width: 250px; 
        white-space: normal;
    } /* Người dùng */
    
    #accounts-table th:nth-child(3),
    #accounts-table td:nth-child(3) { width: 200px; } /* Email */
    
    #accounts-table th:nth-child(4),
    #accounts-table td:nth-child(4) { width: 140px; } /* Loại TK */
    
    #accounts-table th:nth-child(5),
    #accounts-table td:nth-child(5) { width: 120px; } /* Trạng thái */
    
    #accounts-table th:nth-child(6),
    #accounts-table td:nth-child(6) { width: 100px; text-align: center; } /* Xác minh */
    
    #accounts-table th:nth-child(7),
    #accounts-table td:nth-child(7) { width: 100px; text-align: center; } /* Nhà cung cấp */
    
    #accounts-table th:nth-child(8),
    #accounts-table td:nth-child(8) { 
        width: 140px; 
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
    
    /* Provider icons */
    .provider-icon {
        font-size: 1.2rem;
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
  <h1 class="h3 mb-4">Quản lý tài khoản</h1>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng tài khoản</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalAccountsCount ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-users fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kích hoạt</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeAccountsCount ?? 0 }}</div>
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
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Bị khoá</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $lockedAccountsCount ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-times-circle fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Chưa xác minh email</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $unverifiedAccountsCount ?? 0 }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-envelope fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Danh sách tài khoản</h6>
      <div>
        <button type="button" class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#createAccountModal">
          <i class="fa-solid fa-user-plus me-1"></i> Thêm tài khoản
        </button>
        <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#manageAccountTypesModal">
          <i class="fa-solid fa-tags me-1"></i> Quản lý loại TK
        </button>
        <button id="bulk-unlock-btn" type="button" class="btn btn-warning btn-sm me-2" disabled>
          <i class="fa-solid fa-unlock me-1"></i> Mở khóa
        </button>
        <button id="bulk-lock-btn" type="button" class="btn btn-secondary btn-sm me-2" disabled>
          <i class="fa-solid fa-lock me-1"></i> Khóa
        </button>
        <button id="bulk-delete-btn" type="button" class="btn btn-danger btn-sm" disabled>
          <i class="fa-solid fa-trash me-1"></i> Xóa
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="accounts-table">
          <thead class="table-light">
            <tr>
              <th><input type="checkbox" class="form-check-input" id="checkAll"></th>
              <th>Người dùng</th>
              <th>Email</th>
              <th>Loại TK</th>
              <th>Trạng thái</th>
              <th>Xác minh</th>
              <th>Nhà cung cấp</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach($accounts as $account)
            <tr>
              <td>
                <input type="checkbox" class="form-check-input row-checkbox" data-account-id="{{ $account->account_id }}">
              </td>
              <td>
                <div class="user-info">
                  <img src="{{ $account->avatar_url ?: asset('images/man.jpg') }}" 
                       alt="Avatar" 
                       class="user-avatar" 
                       onerror="this.src='{{ asset('images/man.jpg') }}'"
                       loading="lazy">
                  <div class="user-details">
                    <div class="user-name">{{ $account->profile->fullname ?? 'N/A' }}</div>
                    <div class="user-id">ID: {{ $account->account_id }}</div>
                  </div>
                </div>
              </td>
              <td title="{{ $account->email }}">{{ $account->email }}</td>
              <td>
                <span class="badge bg-secondary">{{ $account->accountType->name ?? 'N/A' }}</span>
              </td>
              <td>
                @if($account->status == 1)
                  <span class="badge bg-success">Hoạt động</span>
                @else
                  <span class="badge bg-danger">Tạm khóa</span>
                @endif
              </td>
              <td>
                @if($account->email_verified_at)
                  <i class="fa-solid fa-circle-check text-success" title="Đã xác minh"></i>
                @else
                  <i class="fa-solid fa-circle-xmark text-danger" title="Chưa xác minh"></i>
                @endif
              </td>
              <td data-order="{{ $account->provider ?? 'local' }}">
                @if($account->provider == 'google')
                  <i class="fa-brands fa-google text-danger provider-icon" title="Google"></i>
                @elseif($account->provider == 'github')
                  <i class="fa-brands fa-github text-dark provider-icon" title="GitHub"></i>
                @elseif($account->provider == 'facebook')
                  <i class="fa-brands fa-facebook text-primary provider-icon" title="Facebook"></i>
                @else
                  <i class="fa-solid fa-user-circle text-muted provider-icon" title="Đăng ký thường"></i>
                @endif
              </td>
              <td>
                <div class="btn-group" role="group">
                  <button class="btn btn-primary btn-sm edit-account-btn" 
                          data-account-id="{{ $account->account_id }}"
                          data-fullname="{{ $account->profile->fullname ?? '' }}"
                          data-email="{{ $account->email }}"
                          data-account-type-id="{{ $account->account_type_id }}"
                          title="Chỉnh sửa">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-danger btn-sm delete-account-btn" 
                          data-account-id="{{ $account->account_id }}" 
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

  <!-- Modal tạo tài khoản -->
  <div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createAccountModalLabel">Thêm tài khoản mới</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="{{ route('admin.accounts.store') }}" method="POST">
          @csrf
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Họ và tên</label>
              <input type="text" class="form-control" name="fullname" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Mật khẩu</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Loại tài khoản</label>
              <select class="form-select" name="account_type_id" required>
                @foreach($accountTypes as $type)
                  <option value="{{ $type->account_type_id }}">{{ $type->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal chỉnh sửa tài khoản -->
  <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editAccountModalLabel">Chỉnh sửa tài khoản</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="editAccountForm" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Họ và tên</label>
              <input type="text" class="form-control" id="edit-fullname" name="fullname" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="edit-email" name="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Loại tài khoản</label>
              <select class="form-select" id="edit-account-type" name="account_type_id" required>
                @foreach($accountTypes as $type)
                  <option value="{{ $type->account_type_id }}">{{ $type->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
              <input type="password" class="form-control" name="password" placeholder="Nhập mật khẩu mới...">
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

  <!-- Modal quản lý loại tài khoản -->
  <div class="modal fade" id="manageAccountTypesModal" tabindex="-1" aria-labelledby="manageAccountTypesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="manageAccountTypesModalLabel">Quản lý loại tài khoản</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Form thêm loại tài khoản mới -->
          <div class="card mb-3">
            <div class="card-header">
              <h6 class="mb-0">Thêm loại tài khoản mới</h6>
            </div>
            <div class="card-body">
              <form id="addAccountTypeForm">
                <div class="row">
                  <div class="col-md-6">
                    <label class="form-label">Mã loại tài khoản</label>
                    <select class="form-select" id="newAccountTypeId" name="account_type_id" required>
                      <option value="">Chọn mã loại...</option>
                      @php
                        $existingIds = $accountTypes->pluck('account_type_id')->toArray();
                        $allIds = range(1, 20); // Tăng lên 20 để có nhiều lựa chọn hơn
                        $availableIds = array_diff($allIds, $existingIds);
                      @endphp
                      @foreach($availableIds as $id)
                        <option value="{{ $id }}">{{ $id }}</option>
                      @endforeach
                      <option value="custom">➕ Nhập mã khác...</option>
                    </select>
                    <input type="number" class="form-control mt-2 d-none" id="customAccountTypeId" placeholder="Nhập mã tùy chỉnh (VD: 21, 50, 100...)" min="1" max="999">
                    <small class="text-muted d-none" id="customIdHint">Mã phải là số nguyên dương và chưa tồn tại</small>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Tên loại tài khoản</label>
                    <input type="text" class="form-control" id="newAccountTypeName" name="name" required placeholder="Nhập tên loại...">
                  </div>
                </div>
                <div class="row mt-3">
                  <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" id="newAccountTypeDescription" name="description" rows="2" placeholder="Mô tả về loại tài khoản này..."></textarea>
                  </div>
                </div>
                <div class="mt-3">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Thêm loại tài khoản
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Danh sách loại tài khoản hiện có -->
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">Danh sách loại tài khoản hiện có</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-hover" id="accountTypesTable">
                  <thead class="table-light">
                    <tr>
                      <th width="10%">Mã</th>
                      <th width="30%">Tên</th>
                      <th width="40%">Mô tả</th>
                      <th width="20%">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody id="accountTypesTableBody">
                    @foreach($accountTypes as $type)
                    <tr data-type-id="{{ $type->account_type_id }}">
                      <td>{{ $type->account_type_id }}</td>
                      <td>{{ $type->name }}</td>
                      <td>{{ $type->description ?? 'Không có mô tả' }}</td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary edit-type-btn" 
                                data-type-id="{{ $type->account_type_id }}"
                                data-type-name="{{ $type->name }}"
                                data-type-description="{{ $type->description ?? '' }}">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-type-btn" 
                                data-type-id="{{ $type->account_type_id }}">
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let selectedAccounts = new Set();
    let originalOrder = []; // Lưu thứ tự ban đầu
    let columnStates = {}; // Lưu trạng thái sort của từng cột

    function updateButtonStates() {
        const selectedCount = selectedAccounts.size;
        const isDisabled = selectedCount === 0;
        $('#bulk-unlock-btn').prop('disabled', isDisabled);
        $('#bulk-lock-btn').prop('disabled', isDisabled);
        $('#bulk-delete-btn').prop('disabled', isDisabled);
    }

    // Lưu thứ tự ban đầu
    $('#accounts-table tbody tr').each(function(index) {
        originalOrder.push($(this).clone(true));
    });

    // Khởi tạo DataTables tối ưu - vừa đẹp vừa nhanh
    const table = $('#accounts-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true, // Bật lại sorting cơ bản
        paging: true,
        pageLength: 25, // Trở lại 25 items
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        stateSave: true, // Bật lại state save
        searching: true,
        searchDelay: 100, // Giảm delay xuống 100ms
        deferRender: true,
        autoWidth: false,
        columnDefs: [
            { orderable: false, targets: [0, 7] }, // Chỉ tắt sort cho checkbox và action
            { className: "text-center", targets: [0, 5, 6, 7] }
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

    // Loại bỏ 3-state sorting để tăng tốc độ

    function bindEvents() {
        // Checkbox events
        $('.row-checkbox').off('change').on('change', function() {
            const accountId = $(this).data('account-id');
            if (this.checked) {
                selectedAccounts.add(accountId);
            } else {
                selectedAccounts.delete(accountId);
            }
            updateButtonStates();
        });

        // Delete button events
        $('.delete-account-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const accountId = $(this).data('account-id');
            
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                text: "Bạn sẽ xóa tài khoản này. Hành động này không thể hoàn tác!",
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
                        action: `/admin/accounts/${accountId}`
                    }).append(
                        '@csrf',
                        '@method("DELETE")'
                    );
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // Edit button events
        $('.edit-account-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const accountId = $(this).data('account-id');
            const fullname = $(this).data('fullname');
            const email = $(this).data('email');
            const accountTypeId = $(this).data('account-type-id');
            
            // Populate modal fields
            $('#edit-fullname').val(fullname);
            $('#edit-email').val(email);
            $('#edit-account-type').val(accountTypeId);
            
            // Set form action
            $('#editAccountForm').attr('action', `/admin/accounts/${accountId}`);
            
            // Show modal
            $('#editAccountModal').modal('show');
        });
    }

    // Check all functionality
    $('#checkAll').on('change', function() {
        const isChecked = this.checked;
        $('.row-checkbox').each(function() {
            this.checked = isChecked;
            const accountId = $(this).data('account-id');
            if (isChecked) {
                selectedAccounts.add(accountId);
            } else {
                selectedAccounts.delete(accountId);
            }
        });
        updateButtonStates();
    });

    // Bulk operations
    function performBulkStatusUpdate(status) {
        if (selectedAccounts.size === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn ít nhất một tài khoản', 'warning');
            return;
        }

        const actionText = status === 1 ? 'mở khóa' : 'tạm khóa';
        const accountIds = Array.from(selectedAccounts);

        Swal.fire({
            title: `Bạn có chắc chắn muốn ${actionText} ${accountIds.length} tài khoản đã chọn?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `Đồng ý ${actionText}`,
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = $('<form>', {
                    method: 'POST',
                    action: '{{ route("admin.accounts.update-status-multiple") }}'
                }).append(
                    '@csrf',
                    $('<input>', { type: 'hidden', name: 'ids', value: accountIds.join(',') }),
                    $('<input>', { type: 'hidden', name: 'status', value: status })
                );
                $('body').append(form);
                form.submit();
            }
        });
    }

    $('#bulk-lock-btn').on('click', function() {
        performBulkStatusUpdate(0);
    });

    $('#bulk-unlock-btn').on('click', function() {
        performBulkStatusUpdate(1);
    });

    // Account Type Management
    // Toggle custom ID input
    $('#newAccountTypeId').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customAccountTypeId').removeClass('d-none').attr('required', true);
            $('#customIdHint').removeClass('d-none');
            $(this).removeAttr('required');
        } else {
            $('#customAccountTypeId').addClass('d-none').removeAttr('required').val('');
            $('#customIdHint').addClass('d-none');
            $(this).attr('required', true);
        }
    });

    $('#addAccountTypeForm').on('submit', function(e) {
        e.preventDefault();
        
        // Xác định mã tài khoản (từ dropdown hoặc custom input)
        let accountTypeId = $('#newAccountTypeId').val();
        if (accountTypeId === 'custom') {
            accountTypeId = $('#customAccountTypeId').val();
            if (!accountTypeId || accountTypeId < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu thông tin',
                    text: 'Vui lòng nhập mã loại tài khoản hợp lệ (số nguyên dương)'
                });
                return;
            }
        }
        
        const formData = {
            account_type_id: accountTypeId,
            name: $('#newAccountTypeName').val(),
            description: $('#newAccountTypeDescription').val()
        };
        
        $.ajax({
            url: '/admin/account-types',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Add new row to table
                    const newRow = `
                        <tr data-type-id="${response.accountType.account_type_id}">
                            <td>${response.accountType.account_type_id}</td>
                            <td>${response.accountType.name}</td>
                            <td>${response.accountType.description || 'Không có mô tả'}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-type-btn" 
                                        data-type-id="${response.accountType.account_type_id}"
                                        data-type-name="${response.accountType.name}"
                                        data-type-description="${response.accountType.description || ''}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-type-btn" 
                                        data-type-id="${response.accountType.account_type_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    $('#accountTypesTableBody').append(newRow);
                    
                    // Reset form
                    $('#addAccountTypeForm')[0].reset();
                    $('#customAccountTypeId').addClass('d-none').removeAttr('required').val('');
                    $('#customIdHint').addClass('d-none');
                    $('#newAccountTypeId').attr('required', true);
                    
                    // Update dropdowns in other forms
                    updateAccountTypeDropdowns();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã thêm loại tài khoản mới',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi thêm loại tài khoản'
                });
            }
        });
    });

    // Delete account type
    $(document).on('click', '.delete-type-btn', function() {
        const typeId = $(this).data('type-id');
        const row = $(this).closest('tr');
        
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Xóa loại tài khoản này sẽ ảnh hưởng đến các tài khoản đang sử dụng!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý xóa!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/account-types/${typeId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(300, function() {
                                $(this).remove();
                            });
                            updateAccountTypeDropdowns();
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã xóa!',
                                text: 'Loại tài khoản đã được xóa',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: xhr.responseJSON?.message || 'Không thể xóa loại tài khoản này'
                        });
                    }
                });
            }
        });
    });

    function updateAccountTypeDropdowns() {
        // Update all account type dropdowns in the page
        $.get('/admin/account-types', function(response) {
            if (response.success) {
                // Update dropdown tên loại tài khoản (cho form tạo/sửa account)
                const options = response.accountTypes.map(type => 
                    `<option value="${type.account_type_id}">${type.name}</option>`
                ).join('');
                
                $('select[name="account_type_id"]:not(#newAccountTypeId)').each(function() {
                    const currentValue = $(this).val();
                    $(this).html('<option value="">Chọn loại tài khoản...</option>' + options);
                    $(this).val(currentValue);
                });
                
                // Update dropdown mã loại tài khoản (cho form thêm account type mới)
                const existingIds = response.accountTypes.map(type => type.account_type_id);
                const allIds = Array.from({length: 20}, (_, i) => i + 1); // 1-20
                const availableIds = allIds.filter(id => !existingIds.includes(id));
                
                const idOptions = availableIds.map(id => 
                    `<option value="${id}">${id}</option>`
                ).join('');
                
                $('#newAccountTypeId').html('<option value="">Chọn mã loại...</option>' + idOptions);
            }
        });
    }

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
