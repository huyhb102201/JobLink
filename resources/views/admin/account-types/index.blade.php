@extends('admin.layouts.app')

@section('title', 'Quản lý Loại tài khoản')

@section('content')
<div class="container-fluid">
  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="fas fa-tags me-2"></i>Quản lý Loại tài khoản
    </h1>
  </div>

  <!-- Statistics Cards -->
  <div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng loại tài khoản</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $accountTypes->count() }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-tags fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đang hoạt động</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $accountTypes->count() }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-check-circle fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tổng tài khoản</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalAccounts }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-users fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Loại phổ biến nhất</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $mostPopularType }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-star fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Account Types Table -->
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Danh sách Loại tài khoản</h6>
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAccountTypeModal">
        <i class="fas fa-plus me-1"></i> Thêm loại mới
      </button>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="accountTypesTable">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Tên loại</th>
              <th>Mô tả</th>
              <th>Số tài khoản</th>
              <th>Ngày tạo</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach($accountTypes as $type)
            <tr>
              <td>{{ $type->account_type_id }}</td>
              <td><strong>{{ $type->name }}</strong></td>
              <td>{{ $type->description ?? 'N/A' }}</td>
              <td><span class="badge bg-info">{{ $type->accounts_count }} tài khoản</span></td>
              <td>{{ $type->created_at ? $type->created_at->format('d/m/Y') : 'N/A' }}</td>
              <td>
                <button class="btn btn-sm btn-warning edit-btn" 
                        data-id="{{ $type->account_type_id }}"
                        data-name="{{ $type->name }}"
                        data-description="{{ $type->description }}"
                        title="Chỉnh sửa">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-btn" 
                        data-id="{{ $type->account_type_id }}"
                        data-name="{{ $type->name }}"
                        title="Xóa">
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

<!-- Add Account Type Modal -->
<div class="modal fade" id="addAccountTypeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Thêm Loại tài khoản mới</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="addAccountTypeForm" method="POST" action="{{ route('admin.account-types.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tên loại <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary">Thêm mới</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Account Type Modal -->
<div class="modal fade" id="editAccountTypeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Chỉnh sửa Loại tài khoản</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="editAccountTypeForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <input type="hidden" id="edit-id" name="id">
          <div class="mb-3">
            <label class="form-label">Tên loại <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit-name" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea class="form-control" id="edit-description" name="description" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-warning">Cập nhật</button>
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
    // Initialize DataTable
    const table = $('#accountTypesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        order: [[0, 'asc']]
    });

    // Add Account Type Form - AJAX
    $('#addAccountTypeForm').on('submit', function(e) {
        e.preventDefault();
        showLoading('Đang thêm loại tài khoản...');

        $.ajax({
            url: '{{ route('admin.account-types.store') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#addAccountTypeModal').modal('hide');
                    $('#addAccountTypeForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                hideLoading();
                const message = xhr.responseJSON?.message || 'Có lỗi xảy ra';
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: message
                });
            }
        });
    });

    // Edit button
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        
        $('#edit-id').val(id);
        $('#edit-name').val(name);
        $('#edit-description').val(description);
        $('#editAccountTypeForm').data('id', id);
        
        $('#editAccountTypeModal').modal('show');
    });

    // Edit Account Type Form - AJAX
    $('#editAccountTypeForm').on('submit', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        showLoading('Đang cập nhật loại tài khoản...');

        $.ajax({
            url: `/admin/account-types/${id}`,
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                name: $('#edit-name').val(),
                description: $('#edit-description').val()
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#editAccountTypeModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                hideLoading();
                const message = xhr.responseJSON?.message || 'Có lỗi xảy ra';
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: message
                });
            }
        });
    });

    // Delete button - AJAX
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: `Bạn có chắc muốn xóa loại "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Đang xóa loại tài khoản...');
                
                $.ajax({
                    url: `/admin/account-types/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        const message = xhr.responseJSON?.message || 'Có lỗi xảy ra khi xóa';
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: message
                        });
                    }
                });
            }
        });
    });
});
</script>

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: '{{ session('success') }}',
        confirmButtonText: 'OK'
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Lỗi!',
        text: '{{ session('error') }}',
        confirmButtonText: 'OK'
    });
</script>
@endif
@endpush
