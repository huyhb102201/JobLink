@extends('admin.layouts.app')

@section('title', 'Quản lý tài khoản')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    /* Responsive container cho zoom - ưu tiên cao nhất */
    * {
        box-sizing: border-box;
    }
    
    .h3.mb-4, h1.h3.mb-4 {
        max-width: 100%;
        word-wrap: break-word;
    }
    
    /* Responsive cho phần thống kê */
    .row.g-4.mb-4 {
        max-width: 100%;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    .row.g-4.mb-4 > .col-md-3 {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
    
    /* Card container */
    .card.shadow.mb-4 {
        max-width: 100%;
        overflow: visible;
    }
    
    .card-header {
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .card-header > div {
        min-width: max-content;
    }
    
    .card-body {
        padding: 1rem;
        max-width: 100%;
        overflow-x: hidden;
    }
    
    /* Table container với scroll */
    .table-responsive {
        overflow-x: auto !important;
        max-width: 100% !important;
        -webkit-overflow-scrolling: touch;
        margin: 0;
        padding: 0;
    }
    
    /* Fix layout cho bảng - giống bảng thanh toán job */
    #accounts-table {
        width: 100% !important;
        table-layout: fixed;
        margin-bottom: 0 !important;
    }
    
    /* DataTables wrapper */
    .dataTables_wrapper {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    .dataTables_wrapper .row {
        max-width: 100%;
        margin: 0;
    }
    
    /* Filter collapse */
    #filterCollapse {
        max-width: 100%;
    }
    
    #filterCollapse .card-body {
        overflow-x: auto;
    }
    
    #accounts-table th,
    #accounts-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Định width cho từng cột - sử dụng % thay vì px */
    #accounts-table th:nth-child(1),
    #accounts-table td:nth-child(1) { 
        width: 5%;
        text-align: center;
    } /* Checkbox */
    
    #accounts-table th:nth-child(2),
    #accounts-table td:nth-child(2) { 
        width: 20%;
        white-space: normal;
    } /* Người dùng */
    
    #accounts-table th:nth-child(3),
    #accounts-table td:nth-child(3) { 
        width: 18%;
    } /* Email */
    
    #accounts-table th:nth-child(4),
    #accounts-table td:nth-child(4) { 
        width: 12%;
    } /* Loại TK */
    
    #accounts-table th:nth-child(5),
    #accounts-table td:nth-child(5) { 
        width: 12%;
        text-align: center;
    } /* Trạng thái */
    
    #accounts-table th:nth-child(6),
    #accounts-table td:nth-child(6) { 
        width: 10%;
        text-align: center;
    } /* Xác minh */
    
    #accounts-table th:nth-child(7),
    #accounts-table td:nth-child(7) { 
        width: 12%;
        text-align: center;
    } /* Nhà cung cấp */
    
    #accounts-table th:nth-child(8),
    #accounts-table td:nth-child(8) { 
        width: 11%;
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
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-accounts-count">{{ $totalAccountsCount ?? 0 }}</div>
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
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-accounts-count">{{ $activeAccountsCount ?? 0 }}</div>
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
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="locked-accounts-count">{{ $lockedAccountsCount ?? 0 }}</div>
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
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="unverified-accounts-count">{{ $unverifiedAccountsCount ?? 0 }}</div>
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
      <!-- Filter Dropdown -->
      <div class="mb-3">
        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
          <i class="fas fa-filter me-1"></i> Bộ lọc
        </button>
        <button class="btn btn-outline-secondary btn-sm ms-2" type="button" id="clearFilters">
          <i class="fas fa-times me-1"></i> Xóa bộ lọc
        </button>
      </div>
      
      <div class="collapse" id="filterCollapse">
        <div class="card card-body mb-3">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-bold small">Tên người dùng</label>
              <input type="text" class="form-control form-control-sm column-filter" placeholder="Tìm kiếm..." data-column="1" id="filter-name">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold small">Email</label>
              <input type="text" class="form-control form-control-sm column-filter" placeholder="Tìm kiếm..." data-column="2" id="filter-email">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-bold small">Loại TK</label>
              <select class="form-select form-select-sm column-filter" data-column="3" id="filter-type">
                <option value="">Tất cả</option>
                @foreach($accountTypes as $type)
                  <option value="{{ $type->name }}">{{ $type->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-bold small">Trạng thái</label>
              <select class="form-select form-select-sm column-filter" data-column="4" id="filter-status">
                <option value="">Tất cả</option>
                <option value="Hoạt động">Hoạt động</option>
                <option value="Tạm khóa">Tạm khóa</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-bold small">Xác minh</label>
              <select class="form-select form-select-sm column-filter" data-column="5" id="filter-verified">
                <option value="">Tất cả</option>
                <option value="verified">Đã xác minh</option>
                <option value="unverified">Chưa xác minh</option>
              </select>
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-3">
              <label class="form-label fw-bold small">Nhà cung cấp</label>
              <select class="form-select form-select-sm column-filter" data-column="6" id="filter-provider">
                <option value="">Tất cả</option>
                <option value="google">Google</option>
                <option value="github">GitHub</option>
                <option value="facebook">Facebook</option>
                <option value="local">Đăng ký thường</option>
              </select>
            </div>
          </div>
        </div>
      </div>
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
              <td data-verification="{{ $account->email_verified_at ? 'verified' : 'unverified' }}">
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
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="createAccountModalLabel">
            <i class="fas fa-user-plus me-2"></i>Thêm tài khoản mới
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i>Đóng
            </button>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save me-1"></i>Tạo tài khoản
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal chỉnh sửa tài khoản -->
  <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="editAccountModalLabel">
            <i class="fas fa-user-edit me-2"></i>Chỉnh sửa tài khoản
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form id="editAccountForm" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Họ và tên</label>
              <input type="text" class="form-control" id="edit-fullname" name="fullname" readonly style="background-color: #e9ecef;">
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="edit-email" name="email" readonly style="background-color: #e9ecef;">
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
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i>Đóng
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i>Cập nhật
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal quản lý loại tài khoản -->
  <div class="modal fade" id="manageAccountTypesModal" tabindex="-1" aria-labelledby="manageAccountTypesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="manageAccountTypesModalLabel">
            <i class="fas fa-tags me-2"></i>Quản lý loại tài khoản
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Form thêm loại tài khoản mới -->
          <div class="card mb-4 border-info">
            <div class="card-header bg-info text-white">
              <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm loại tài khoản mới</h6>
            </div>
            <div class="card-body">
              <form id="addAccountTypeForm">
                <div class="row">
                  <div class="col-md-12">
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
          <div class="card border-primary">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách loại tài khoản hiện có</h6>
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Đóng
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal chỉnh sửa loại tài khoản -->
  <div class="modal fade" id="editAccountTypeModal" tabindex="-1" aria-labelledby="editAccountTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="editAccountTypeModalLabel">
            <i class="fas fa-edit me-2"></i>Chỉnh sửa loại tài khoản
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form id="editAccountTypeForm">
          <div class="modal-body">
            <input type="hidden" id="edit-type-id">
            <div class="mb-3">
              <label class="form-label">Tên loại tài khoản</label>
              <input type="text" class="form-control" id="edit-type-name" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Mô tả</label>
              <textarea class="form-control" id="edit-type-description" name="description" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i>Hủy
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i>Cập nhật
            </button>
          </div>
        </form>
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
    let allAccountIds = []; // Lưu tất cả ID tài khoản trong database
    let selectAllMode = false; // Chế độ chọn tất cả

    // Lấy tất cả account IDs từ bảng
    $('#accounts-table tbody tr').each(function() {
        const accountId = $(this).find('.row-checkbox').data('account-id');
        if (accountId) {
            allAccountIds.push(accountId);
        }
    });

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

    // Khởi tạo DataTables với 3-state sorting và pagination
    const table = $('#accounts-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        stateSave: false, // Tắt state save để reset được
        searching: true,
        searchDelay: 100,
        deferRender: true,
        autoWidth: false,
        orderCellsTop: true,
        search: {
            caseInsensitive: true
        },
        // Bật 3-state sorting: asc -> desc -> none (reset)
        orderMulti: false,
        columnDefs: [
            { orderable: false, targets: [0, 7] },
            { className: "text-center", targets: [0, 5, 6, 7] },
            { orderSequence: ['asc', 'desc', ''], targets: '_all' } // 3-state: A→Z, Z→A, Reset
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

    // Store current active filter
    let activeFilterColumn = null;
    let activeFilterValue = '';
    
    // Custom search and sort function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'accounts-table') {
            return true;
        }
        
        if (!activeFilterValue || activeFilterColumn === null) {
            return true;
        }
        
        const columnData = data[activeFilterColumn] || '';
        const searchLower = activeFilterValue.toLowerCase();
        const dataLower = columnData.toLowerCase();
        
        // Lọc: chỉ hiển thị dòng có chứa ký tự tìm kiếm
        return dataLower.includes(searchLower);
    });
    
    // Clear filters button
    $('#clearFilters').on('click', function() {
        // Clear all filter inputs
        $('.column-filter').val('');
        
        // Clear all column searches
        for (let i = 0; i < 8; i++) {
            table.column(i).search('');
        }
        
        // Reset active filter
        activeFilterColumn = null;
        activeFilterValue = '';
        
        // Reset to default order
        table.order([[1, 'asc']]);
        table.draw();
    });
    
    // Column filtering với logic ưu tiên đầu dòng
    $('.column-filter').on('keyup change', function() {
        const columnIndex = $(this).data('column');
        let searchValue = $(this).val().trim();
        
        // Clear all column searches
        for (let i = 0; i < 8; i++) {
            table.column(i).search('');
        }
        
        // Set active filter
        activeFilterColumn = searchValue ? columnIndex : null;
        activeFilterValue = searchValue;
        
        if (searchValue) {
            // Apply custom ordering: đẩy các giá trị bắt đầu bằng ký tự tìm kiếm lên đầu
            const searchLower = searchValue.toLowerCase();
            
            // Custom ordering function
            $.fn.dataTable.ext.order['starts-with-' + columnIndex] = function(settings, col) {
                return this.api().column(col, {order: 'index'}).nodes().map(function(td, i) {
                    const text = $(td).text().trim().toLowerCase();
                    // Trả về 0 nếu bắt đầu bằng ký tự tìm kiếm, 1 nếu không
                    return text.startsWith(searchLower) ? '0' + text : '1' + text;
                });
            };
            
            // Apply ordering
            table.column(columnIndex).order('asc');
            table.order([[columnIndex, 'asc']]);
        } else {
            // Reset về thứ tự mặc định
            table.order([[1, 'asc']]);
        }
        
        table.draw();
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
                    showLoading('Đang xóa tài khoản...');
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

    // Check all functionality - chọn TẤT CẢ tài khoản trong database
    $('#checkAll').on('change', function() {
        const isChecked = this.checked;
        selectAllMode = isChecked;
        
        if (isChecked) {
            // Thêm TẤT CẢ account IDs vào selectedAccounts
            allAccountIds.forEach(id => selectedAccounts.add(id));
        } else {
            // Xóa tất cả
            selectedAccounts.clear();
        }
        
        // Cập nhật checkbox trên trang hiện tại
        $('.row-checkbox:visible').each(function() {
            this.checked = isChecked;
        });
        
        updateButtonStates();
    });
    
    // Đồng bộ trạng thái checkAll khi chuyển trang
    table.on('draw', function() {
        // Cập nhật checkbox trên trang mới dựa vào selectedAccounts
        $('.row-checkbox').each(function() {
            const accountId = $(this).data('account-id');
            this.checked = selectedAccounts.has(accountId);
        });
        
        // Cập nhật trạng thái checkAll
        const allChecked = allAccountIds.every(id => selectedAccounts.has(id));
        $('#checkAll').prop('checked', allChecked);
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
                // Hiển thị loading
                Swal.fire({
                    title: 'Đang xử lý...',
                    text: `Đang ${actionText} tài khoản`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Gửi AJAX request thay vì submit form
                showLoading('Đang cập nhật trạng thái...');
                
                $.ajax({
                    url: '{{ route("admin.accounts.update-status-multiple") }}',
                    method: 'POST',
                    data: {
                        ids: accountIds.join(','),
                        status: status,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        hideLoading();
                        // Cập nhật UI ngay lập tức
                        accountIds.forEach(accountId => {
                            const row = $(`.row-checkbox[data-account-id="${accountId}"]`).closest('tr');
                            const statusCell = row.find('td:nth-child(5)');
                            
                            if (status === 1) {
                                statusCell.html('<span class="badge bg-success">Hoạt động</span>');
                            } else {
                                statusCell.html('<span class="badge bg-danger">Tạm khóa</span>');
                            }
                        });

                        // Cập nhật số liệu thống kê ngay lập tức
                        const countChange = accountIds.length;
                        const currentActive = parseInt($('#active-accounts-count').text());
                        const currentLocked = parseInt($('#locked-accounts-count').text());
                        
                        if (status === 1) {
                            // Mở khóa: tăng kích hoạt, giảm bị khóa
                            $('#active-accounts-count').text(currentActive + countChange);
                            $('#locked-accounts-count').text(Math.max(0, currentLocked - countChange));
                        } else {
                            // Khóa: giảm kích hoạt, tăng bị khóa
                            $('#active-accounts-count').text(Math.max(0, currentActive - countChange));
                            $('#locked-accounts-count').text(currentLocked + countChange);
                        }

                        // Reset checkboxes
                        $('.row-checkbox').prop('checked', false);
                        $('#checkAll').prop('checked', false);
                        selectedAccounts.clear();
                        updateButtonStates();

                        // Hiển thị thông báo thành công
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: response.message || `Đã ${actionText} ${accountIds.length} tài khoản`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        hideLoading();
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: xhr.responseJSON?.message || `Có lỗi xảy ra khi ${actionText} tài khoản`,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    }

    $('#bulk-lock-btn').on('click', function() {
        performBulkStatusUpdate(0);
    });

    $('#bulk-unlock-btn').on('click', function() {
        performBulkStatusUpdate(1);
    });

    // Bulk delete
    $('#bulk-delete-btn').on('click', function() {
        if (selectedAccounts.size === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn ít nhất một tài khoản để xóa', 'warning');
            return;
        }

        const accountIds = Array.from(selectedAccounts);

        Swal.fire({
            title: `Bạn có chắc chắn muốn xóa ${accountIds.length} tài khoản đã chọn?`,
            text: "Hành động này không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý, xóa!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Đang xóa tài khoản...');

                // Gửi AJAX request
                $.ajax({
                    url: '{{ route("admin.accounts.destroy-multiple") }}',
                    method: 'DELETE',
                    data: {
                        ids: accountIds.join(','),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            // Xóa các dòng khỏi bảng
                            accountIds.forEach(accountId => {
                                const row = $(`.row-checkbox[data-account-id="${accountId}"]`).closest('tr');
                                table.row(row).remove();
                            });
                            table.draw();

                            // Cập nhật số liệu thống kê
                            if (response.stats) {
                                const currentTotal = parseInt($('#total-accounts-count').text());
                                const currentActive = parseInt($('#active-accounts-count').text());
                                const currentLocked = parseInt($('#locked-accounts-count').text());
                                
                                $('#total-accounts-count').text(currentTotal - response.stats.removed_total);
                                $('#active-accounts-count').text(Math.max(0, currentActive - response.stats.removed_active));
                                $('#locked-accounts-count').text(Math.max(0, currentLocked - response.stats.removed_locked));
                            }

                            // Reset checkboxes
                            $('.row-checkbox').prop('checked', false);
                            $('#checkAll').prop('checked', false);
                            selectedAccounts.clear();
                            updateButtonStates();

                            // Hiển thị thông báo thành công
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: response.message || `Đã xóa ${accountIds.length} tài khoản`,
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
                            text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi xóa tài khoản',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });

    // Account Type Management
    $('#addAccountTypeForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#newAccountTypeName').val(),
            description: $('#newAccountTypeDescription').val()
        };
        
        showLoading('Đang thêm loại tài khoản...');
        
        $.ajax({
            url: '/admin/account-types',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();
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
                hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi thêm loại tài khoản'
                });
            }
        });
    });

    // Edit account type - Open modal
    $(document).on('click', '.edit-type-btn', function() {
        const typeId = $(this).data('type-id');
        const typeName = $(this).data('type-name');
        const typeDescription = $(this).data('type-description');
        
        // Populate modal fields
        $('#edit-type-id').val(typeId);
        $('#edit-type-name').val(typeName);
        $('#edit-type-description').val(typeDescription || '');
        
        // Show modal
        $('#editAccountTypeModal').modal('show');
    });

    // Edit account type - Submit form
    $('#editAccountTypeForm').on('submit', function(e) {
        e.preventDefault();
        
        const typeId = $('#edit-type-id').val();
        const name = $('#edit-type-name').val().trim();
        const description = $('#edit-type-description').val().trim();
        
        if (!name) {
            Swal.fire({
                icon: 'warning',
                title: 'Cảnh báo',
                text: 'Vui lòng nhập tên loại tài khoản'
            });
            return;
        }
        
        showLoading('Đang cập nhật loại tài khoản...');
        
        $.ajax({
            url: `/admin/account-types/${typeId}`,
            method: 'POST',
            data: {
                _method: 'PUT',
                name: name,
                description: description
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    // Close modal
                    $('#editAccountTypeModal').modal('hide');
                    
                    // Update row in table
                    const row = $(`.edit-type-btn[data-type-id="${typeId}"]`).closest('tr');
                    row.find('td:eq(1)').text(name);
                    row.find('td:eq(2)').text(description || 'Không có mô tả');
                    
                    // Update data attributes - QUAN TRỌNG: Phải update cả data() và attr()
                    const editBtn = $(`.edit-type-btn[data-type-id="${typeId}"]`);
                    editBtn.attr('data-type-name', name);
                    editBtn.attr('data-type-description', description);
                    editBtn.data('type-name', name);
                    editBtn.data('type-description', description);
                    
                    updateAccountTypeDropdowns();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã cập nhật loại tài khoản',
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
                    text: xhr.responseJSON?.message || 'Không thể cập nhật loại tài khoản'
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
                showLoading('Đang xóa loại tài khoản...');
                
                $.ajax({
                    url: `/admin/account-types/${typeId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        hideLoading();
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
                        hideLoading();
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
                
                $('select[name="account_type_id"]').each(function() {
                    const currentValue = $(this).val();
                    $(this).html('<option value="">Chọn loại tài khoản...</option>' + options);
                    $(this).val(currentValue);
                });
            }
        });
    }

    // Khởi tạo DataTables cho bảng loại tài khoản trong modal
    let accountTypesTableInstance = null;
    $('#manageAccountTypesModal').on('shown.bs.modal', function() {
        if (!accountTypesTableInstance) {
            accountTypesTableInstance = $('#accountTypesTable').DataTable({
                paging: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ordering: true,
                searching: false,
                info: true,
                orderMulti: false,
                columnDefs: [
                    { orderable: false, targets: [3] }, // Tắt sort cho cột Thao tác
                    { orderSequence: ['asc', 'desc', ''], targets: '_all' } // 3-state sorting
                ],
                language: {
                    lengthMenu: "Hiển thị _MENU_ mục",
                    zeroRecords: "Không tìm thấy dữ liệu",
                    info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                    infoEmpty: "Hiển thị 0 đến 0 của 0 mục",
                    paginate: { first: "Đầu", last: "Cuối", next: "Tiếp", previous: "Trước" }
                }
            });
        }
    });

    // Destroy DataTable khi đóng modal để tránh lỗi
    $('#manageAccountTypesModal').on('hidden.bs.modal', function() {
        if (accountTypesTableInstance) {
            accountTypesTableInstance.destroy();
            accountTypesTableInstance = null;
        }
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
