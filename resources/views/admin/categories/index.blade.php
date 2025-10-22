@extends('admin.layouts.app')

@section('title', 'Quản lý danh mục')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .stat-card {
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    #categories-table th,
    #categories-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
    }
    
    .badge {
        font-size: 0.875rem;
        padding: 0.35rem 0.65rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-folder-open"></i> Quản lý danh mục
        </h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Thêm danh mục
        </button>
    </div>

    <!-- Thống kê -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng danh mục
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCategories }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng công việc
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalJobs }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Trung bình jobs/danh mục
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $totalCategories > 0 ? round($totalJobs / $totalCategories) : 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng danh mục -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách danh mục</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="categories-table" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 25%;">Tên danh mục</th>
                            <th style="width: 37%;">Mô tả</th>
                            <th style="width: 15%;" class="text-center">Số lượng Jobs</th>
                            <th style="width: 15%;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr data-id="{{ $category->category_id }}">
                            <td>{{ $category->category_id }}</td>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td>{{ $category->description ?? 'Chưa có mô tả' }}</td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $category->jobs_count }} jobs</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info btn-view" 
                                        data-id="{{ $category->category_id }}"
                                        data-name="{{ $category->name }}"
                                        data-description="{{ $category->description ?? 'Chưa có mô tả' }}"
                                        data-jobs-count="{{ $category->jobs_count }}"
                                        title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning btn-edit" 
                                        data-id="{{ $category->category_id }}"
                                        data-name="{{ $category->name }}"
                                        data-description="{{ $category->description }}"
                                        title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete" 
                                        data-id="{{ $category->category_id }}"
                                        data-name="{{ $category->name }}"
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

<!-- Modal Thêm danh mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCategoryModalLabel">
                    <i class="fas fa-plus-circle"></i> Thêm danh mục mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCategoryForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa danh mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editCategoryModalLabel">
                    <i class="fas fa-edit"></i> Sửa danh mục
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_category_id" name="category_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xem chi tiết -->
<div class="modal fade" id="viewCategoryModal" tabindex="-1" aria-labelledby="viewCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewCategoryModalLabel">
                    <i class="fas fa-info-circle"></i> Chi tiết danh mục
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">ID:</label>
                    <p id="view_category_id" class="form-control-plaintext"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên danh mục:</label>
                    <p id="view_name" class="form-control-plaintext"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Mô tả:</label>
                    <p id="view_description" class="form-control-plaintext"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Số lượng Jobs:</label>
                    <p id="view_jobs_count" class="form-control-plaintext"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xóa danh mục -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCategoryModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Xác nhận xóa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa danh mục <strong id="delete_category_name"></strong>?</p>
                <p class="text-danger"><i class="fas fa-info-circle"></i> Lưu ý: Không thể xóa danh mục đang có công việc.</p>
                <input type="hidden" id="delete_category_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Setup CSRF token cho tất cả AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Thêm plugin sắp xếp tiếng Việt cho DataTables
    $.fn.dataTable.ext.type.order['vietnamese-pre'] = function(data) {
        // Loại bỏ HTML tags nếu có
        data = data.replace(/<.*?>/g, '');
        // Chuyển sang chữ thường và normalize Unicode
        return data.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    };

    // Khởi tạo DataTable
    const table = $('#categories-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
        },
        pageLength: 10,
        order: [[0, 'asc']], // Mặc định sắp xếp theo cột ID tăng dần
        columnDefs: [
            {
                targets: 0, // Cột ID (cột đầu tiên)
                type: 'num' // Sắp xếp như số, không phải chuỗi
            },
            {
                targets: 1, // Cột Tên danh mục
                type: 'vietnamese' // Sắp xếp theo tiếng Việt
            },
            {
                targets: 4, // Cột Thao tác (cột cuối)
                orderable: false // Không cho phép sắp xếp
            }
        ]
    });

    // Xử lý thêm danh mục
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: '{{ route("admin.categories.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#addCategoryModal').modal('hide');
                    $('#addCategoryForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: response.message || 'Có lỗi xảy ra'
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Lưu');
            }
        });
    });

    // Xử lý nút xem chi tiết - Load ngay lập tức từ data attributes
    $(document).on('click', '.btn-view', function() {
        const btn = $(this);
        const categoryId = btn.data('id');
        const name = btn.data('name');
        const description = btn.data('description');
        const jobsCount = btn.data('jobs-count');
        
        // Điền dữ liệu vào modal
        $('#view_category_id').text(categoryId);
        $('#view_name').text(name);
        $('#view_description').text(description);
        $('#view_jobs_count').text(jobsCount + ' jobs');
        
        // Hiển thị modal ngay lập tức
        $('#viewCategoryModal').modal('show');
    });

    // Xử lý nút sửa
    $(document).on('click', '.btn-edit', function() {
        const categoryId = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        
        $('#edit_category_id').val(categoryId);
        $('#edit_name').val(name);
        $('#edit_description').val(description);
        $('#editCategoryModal').modal('show');
    });

    // Xử lý form sửa
    $('#editCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        const categoryId = $('#edit_category_id').val();
        const formData = $(this).serialize() + '&_method=PUT';
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: `/admin/categories/${categoryId}`,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editCategoryModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: response.message || 'Có lỗi xảy ra'
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Cập nhật');
            }
        });
    });

    // Xử lý nút xóa
    $(document).on('click', '.btn-delete', function() {
        const categoryId = $(this).data('id');
        const categoryName = $(this).data('name');
        
        $('#delete_category_id').val(categoryId);
        $('#delete_category_name').text(categoryName);
        $('#deleteCategoryModal').modal('show');
    });

    // Xác nhận xóa
    $('#confirmDeleteBtn').on('click', function() {
        const categoryId = $('#delete_category_id').val();
        const btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: `/admin/categories/${categoryId}`,
            method: 'POST',
            data: {
                _method: 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteCategoryModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: response.message || 'Có lỗi xảy ra'
                });
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Xóa');
            }
        });
    });

    // Reset form khi đóng modal
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0]?.reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
    });
});
</script>
@endpush
