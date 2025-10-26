@extends('admin.layouts.app')

@section('title', 'Quản lý Kỹ năng')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
    .sortable {
        user-select: none;
    }
    .sortable:hover {
        background-color: #f8f9fc;
    }
    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        margin: 0 2px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags"></i> Quản lý Kỹ năng
        </h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSkillModal">
            <i class="fas fa-plus"></i> Thêm kỹ năng mới
        </button>
    </div>

    <!-- Thống kê -->
    <div class="row g-4 mb-4">
        <!-- Tổng số kỹ năng -->
        <div class="col-xl-12 col-md-12">
            <div class="card stat-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng số kỹ năng
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSkills }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng kỹ năng -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách Kỹ năng</h6>
        </div>
        <div class="card-body">
            <!-- Tìm kiếm và Hiển thị -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div style="width: 100%; max-width: 500px;">
                    <input type="text" 
                           id="search-input"
                           class="form-control" 
                           placeholder="Tìm kiếm theo ID hoặc tên kỹ năng..." 
                           autocomplete="off">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-danger btn-sm" id="delete-selected-btn" style="display: none;">
                        <i class="fas fa-trash"></i> Xóa đã chọn (<span id="selected-count">0</span>)
                    </button>
                    <div class="d-flex align-items-center">
                        <label class="mb-0 me-2">Hiển thị</label>
                        <select class="form-select form-select-sm" id="per-page-select" style="width: auto;">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <label class="mb-0 ms-2">mục</label>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="skills-table" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;" class="text-center">
                                <input type="checkbox" id="select-all-checkbox" title="Chọn tất cả">
                            </th>
                            <th style="width: 10%; cursor: pointer;" class="sortable" data-column="skill_id">
                                ID <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 55%; cursor: pointer;" class="sortable" data-column="name">
                                Tên kỹ năng <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 30%;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="skills-tbody">
                        @forelse($allSkills as $skill)
                        <tr data-skill-id="{{ $skill->skill_id }}" data-skill-name="{{ strtolower($skill->name) }}">
                            <td class="text-center">
                                <input type="checkbox" class="skill-checkbox" value="{{ $skill->skill_id }}">
                            </td>
                            <td>{{ $skill->skill_id }}</td>
                            <td><strong>{{ $skill->name }}</strong></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info btn-action btn-view" 
                                        data-skill-id="{{ $skill->skill_id }}"
                                        data-skill-name="{{ $skill->name }}"
                                        data-skill-display-name="{{ $skill->name }}"
                                        data-user-count="{{ $skill->user_count }}"
                                        title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning btn-action btn-edit" 
                                        data-skill-id="{{ $skill->skill_id }}"
                                        data-skill-name="{{ $skill->name }}"
                                        title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-action btn-delete" 
                                        data-skill-id="{{ $skill->skill_id }}"
                                        data-skill-name="{{ $skill->name }}"
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Chưa có kỹ năng nào</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Client-side -->
            <div class="d-flex justify-content-between align-items-center mt-4" id="pagination-container">
                <div class="text-muted" id="pagination-info">
                    <!-- JavaScript sẽ cập nhật nội dung này -->
                </div>
                <nav>
                    <ul class="pagination mb-0" id="pagination-links">
                        <!-- Pagination sẽ được tạo bởi JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Kỹ năng -->
<div class="modal fade" id="addSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm kỹ năng mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSkillForm">
                    <div class="mb-3">
                        <label for="add_skill_name" class="form-label">Tên kỹ năng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_skill_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnAddSkill">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xem Chi tiết -->
<div class="modal fade" id="viewSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Chi tiết kỹ năng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-4"><strong>ID:</strong></div>
                    <div class="col-8" id="view_skill_id"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Tên kỹ năng:</strong></div>
                    <div class="col-8" id="view_skill_name"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Số người dùng:</strong></div>
                    <div class="col-8">
                        <span class="badge bg-primary" id="view_user_count">0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chỉnh sửa -->
<div class="modal fade" id="editSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Chỉnh sửa kỹ năng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSkillForm">
                    <input type="hidden" id="edit_skill_id">
                    <div class="mb-3">
                        <label for="edit_skill_name" class="form-label">Tên kỹ năng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_skill_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-warning" id="btnEditSkill">
                    <i class="fas fa-save"></i> Cập nhật
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xóa -->
<div class="modal fade" id="deleteSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="delete_skill_id">
                <p>Bạn có chắc chắn muốn xóa kỹ năng <strong id="delete_skill_name"></strong>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Hành động này không thể hoàn tác!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="btnDeleteSkill">
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
    // Setup CSRF token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Lưu tất cả rows
    let allRows = [];
    let filteredRows = [];
    let currentPage = 1;
    let perPage = 10;
    let selectedSkillIds = []; // Lưu trữ các ID đã chọn
    
    // Lưu tất cả rows khi load trang (lưu HTML string thay vì jQuery object)
    $('#skills-tbody tr').each(function() {
        const $row = $(this);
        allRows.push({
            html: $row[0].outerHTML,
            skillId: $row.data('skill-id'),
            skillName: $row.data('skill-name')
        });
    });
    filteredRows = allRows.slice();
    
    // Hàm render pagination
    function renderPagination() {
        const totalPages = Math.ceil(filteredRows.length / perPage);
        const start = (currentPage - 1) * perPage + 1;
        const end = Math.min(currentPage * perPage, filteredRows.length);
        
        // Update info
        if (filteredRows.length === 0) {
            $('#pagination-info').text('Không có kết quả');
        } else {
            $('#pagination-info').text(`Hiển thị ${start} đến ${end} trong tổng số ${filteredRows.length} kết quả`);
        }
        
        // Update pagination links
        let paginationHtml = '';
        
        // Previous
        if (currentPage === 1) {
            paginationHtml += '<li class="page-item disabled"><span class="page-link">‹ Trước</span></li>';
        } else {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">‹ Trước</a></li>`;
        }
        
        // Pages
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }
        
        // Next
        if (currentPage === totalPages || totalPages === 0) {
            paginationHtml += '<li class="page-item disabled"><span class="page-link">Tiếp ›</span></li>';
        } else {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Tiếp ›</a></li>`;
        }
        
        $('#pagination-links').html(paginationHtml);
        
        // Bind click events
        $('#pagination-links a').on('click', function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            renderTable();
        });
    }
    
    // Hàm render table
    function renderTable() {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        const pageRows = filteredRows.slice(start, end);
        
        $('#skills-tbody').empty();
        
        if (pageRows.length === 0) {
            $('#skills-tbody').html(
                '<tr><td colspan="4" class="text-center text-muted py-4">' +
                '<i class="fas fa-search fa-2x mb-2"></i><br>' +
                'Không tìm thấy kết quả phù hợp' +
                '</td></tr>'
            );
        } else {
            pageRows.forEach(function(rowData) {
                $('#skills-tbody').append(rowData.html);
            });
            
            // Khôi phục trạng thái checkbox sau khi render
            restoreCheckboxStates();
        }
        
        renderPagination();
    }
    
    // Khôi phục trạng thái checkbox
    function restoreCheckboxStates() {
        $('.skill-checkbox').each(function() {
            const skillId = $(this).val();
            if (selectedSkillIds.includes(parseInt(skillId))) {
                $(this).prop('checked', true);
            }
        });
        
        // Cập nhật trạng thái "Chọn tất cả"
        updateSelectAllCheckbox();
    }
    
    // Hàm filter
    function filterRows(searchValue, resetPage = true) {
        searchValue = searchValue.toLowerCase().trim();
        
        if (searchValue === '') {
            filteredRows = allRows.slice();
        } else {
            filteredRows = allRows.filter(function(rowData) {
                const skillId = rowData.skillId.toString().toLowerCase();
                const skillName = rowData.skillName;
                return skillId.includes(searchValue) || skillName.includes(searchValue);
            });
        }
        
        if (resetPage) {
            currentPage = 1; // Reset về trang 1 khi tìm kiếm
        }
        renderTable();
    }
    
    // Xử lý thay đổi số mục hiển thị - TỨC KHẮC
    $('#per-page-select').on('change', function() {
        perPage = parseInt($(this).val());
        currentPage = 1;
        renderTable();
    });

    // Tự động tìm kiếm - TỨC KHẮC
    $('#search-input').on('input', function() {
        filterRows($(this).val());
    });
    
    // Initial render
    renderTable();

    // Xử lý sắp xếp bảng
    let sortStates = {};
    let currentSortColumn = null;
    let currentSortDirection = 0; // 0=default, 1=asc, 2=desc
    
    $('.sortable').on('click', function() {
        const column = $(this).data('column');
        const $icon = $(this).find('i');
        
        if (!sortStates[column]) {
            sortStates[column] = 0;
        }
        
        sortStates[column] = (sortStates[column] + 1) % 3;
        currentSortColumn = column;
        currentSortDirection = sortStates[column];
        
        // Reset icon của các cột khác
        $('.sortable').not(this).find('i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort text-muted');
        $('.sortable').not(this).each(function() {
            const col = $(this).data('column');
            sortStates[col] = 0;
        });
        
        // Update icon
        if (sortStates[column] === 0) {
            $icon.removeClass('fa-sort-up fa-sort-down').addClass('fa-sort text-muted');
        } else if (sortStates[column] === 1) {
            $icon.removeClass('fa-sort fa-sort-down text-muted').addClass('fa-sort-up');
        } else {
            $icon.removeClass('fa-sort fa-sort-up text-muted').addClass('fa-sort-down');
        }
        
        // Sắp xếp allRows và filteredRows
        sortAllRows();
        
        // Re-render table
        currentPage = 1;
        renderTable();
    });
    
    // Hàm sắp xếp allRows
    function sortAllRows() {
        if (currentSortDirection === 0) {
            // Default - sắp xếp theo skill_id tăng dần
            allRows.sort(function(a, b) {
                return a.skillId - b.skillId;
            });
        } else {
            allRows.sort(function(a, b) {
                let aValue, bValue;
                
                if (currentSortColumn === 'skill_id') {
                    aValue = a.skillId;
                    bValue = b.skillId;
                } else if (currentSortColumn === 'name') {
                    aValue = a.skillName;
                    bValue = b.skillName;
                }
                
                if (currentSortDirection === 1) {
                    // Ascending
                    return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
                } else {
                    // Descending
                    return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
                }
            });
        }
        
        // Re-filter sau khi sort
        const searchValue = $('#search-input').val();
        filterRows(searchValue);
    }

    // Xem chi tiết - HIỂN THỊ NGAY LẬP TỨC (không cần AJAX)
    $(document).on('click', '.btn-view', function() {
        const skillId = $(this).data('skill-id');
        const skillName = $(this).data('skill-display-name');
        const userCount = $(this).data('user-count') || 0;
        
        if (!skillId) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không tìm thấy ID kỹ năng!'
            });
            return;
        }
        
        // Hiển thị thông tin ngay lập tức
        $('#view_skill_id').text(skillId);
        $('#view_skill_name').text(skillName);
        $('#view_user_count').text(userCount);
        
        // Mở modal ngay lập tức
        const modal = new bootstrap.Modal(document.getElementById('viewSkillModal'));
        modal.show();
    });

    // Mở modal chỉnh sửa (sử dụng event delegation)
    $(document).on('click', '.btn-edit', function() {
        const skillId = $(this).data('skill-id');
        const skillName = $(this).data('skill-name');
        
        $('#edit_skill_id').val(skillId);
        $('#edit_skill_name').val(skillName).prop('disabled', false).prop('readonly', false);
        $('#edit_skill_name').removeClass('is-invalid');
        
        // Bootstrap 5
        const modal = new bootstrap.Modal(document.getElementById('editSkillModal'));
        modal.show();
    });

    // Xóa kỹ năng (sử dụng SweetAlert2 thay vì modal)
    $(document).on('click', '.btn-delete', async function() {
        const skillId = $(this).data('skill-id');
        const skillName = $(this).data('skill-name');
        
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa',
            html: `Bạn có chắc chắn muốn xóa kỹ năng <b>${skillName}</b>?<br><small class="text-danger">Hành động này không thể hoàn tác!</small>`,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đã xóa',
            cancelButtonText: 'Hủy'
        });
        
        if (!result.isConfirmed) {
            return;
        }
        
        // Hiển thị loading
        Swal.fire({
            title: 'Đang xóa...',
            text: 'Vui lòng đợi',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Gọi API xóa
        $.ajax({
            url: `/admin/skills/${skillId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Xóa khỏi allRows
                const rowIndex = allRows.findIndex(row => row.skillId == skillId);
                if (rowIndex !== -1) {
                    allRows.splice(rowIndex, 1);
                }
                
                // Xóa khỏi selectedSkillIds nếu có
                const selectedIndex = selectedSkillIds.indexOf(parseInt(skillId));
                if (selectedIndex > -1) {
                    selectedSkillIds.splice(selectedIndex, 1);
                }
                
                // Re-render table (giữ nguyên trang hiện tại)
                const searchValue = $('#search-input').val();
                filterRows(searchValue, false);
                
                // Hiển thị thông báo thành công
                Swal.fire({
                    icon: 'success',
                    title: 'Đã xóa!',
                    text: `Kỹ năng "${skillName}" đã được xóa thành công.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                updateSelectedCount();
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: xhr.responseJSON.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra khi xóa kỹ năng!'
                    });
                }
            }
        });
    });

    // Thêm kỹ năng
    $('#btnAddSkill').on('click', function() {
        const $btn = $(this);
        const $input = $('#add_skill_name');
        const skillName = $input.val().trim();
        
        if (!skillName) {
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text('Tên kỹ năng không được để trống');
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
        
        $.ajax({
            url: '/admin/skills',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { name: skillName },
            success: function(response) {
                // Tạo row mới
                const newSkillId = response.skill_id;
                const newRowHtml = `
                    <tr data-skill-id="${newSkillId}" data-skill-name="${skillName.toLowerCase()}">
                        <td class="text-center">
                            <input type="checkbox" class="skill-checkbox" value="${newSkillId}">
                        </td>
                        <td>${newSkillId}</td>
                        <td><strong>${skillName}</strong></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info btn-action btn-view" 
                                    data-skill-id="${newSkillId}"
                                    data-skill-name="${skillName}"
                                    data-skill-display-name="${skillName}"
                                    data-user-count="0"
                                    title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-action btn-edit" 
                                    data-skill-id="${newSkillId}"
                                    data-skill-name="${skillName}"
                                    title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-action btn-delete" 
                                    data-skill-id="${newSkillId}"
                                    data-skill-name="${skillName}"
                                    title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                // Thêm vào allRows
                allRows.push({
                    html: newRowHtml,
                    skillId: newSkillId,
                    skillName: skillName.toLowerCase()
                });
                
                // Re-filter và re-render (reset về trang 1 vì thêm mới)
                const searchValue = $('#search-input').val();
                filterRows(searchValue, true);
                
                // Đóng modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addSkillModal'));
                modal.hide();
                $('#addSkillForm')[0].reset();
                $btn.prop('disabled', false).html(originalHtml);
                
                // Hiển thị thông báo thành công
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: `Đã thêm kỹ năng "${skillName}" thành công!`,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $input.addClass('is-invalid');
                    $input.siblings('.invalid-feedback').text(xhr.responseJSON.errors.name[0]);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra khi thêm kỹ năng!'
                    });
                }
            }
        });
    });

    // Cập nhật kỹ năng
    $('#btnEditSkill').on('click', function() {
        const $btn = $(this);
        const skillId = $('#edit_skill_id').val();
        const $input = $('#edit_skill_name');
        const skillName = $input.val().trim();
        
        if (!skillName) {
            $input.addClass('is-invalid');
            $input.siblings('.invalid-feedback').text('Tên kỹ năng không được để trống');
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...');
        
        $.ajax({
            url: `/admin/skills/${skillId}`,
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { name: skillName },
            success: function(response) {
                // Cập nhật trong allRows
                const rowIndex = allRows.findIndex(row => row.skillId == skillId);
                if (rowIndex !== -1) {
                    allRows[rowIndex].skillName = skillName.toLowerCase();
                    // Cập nhật HTML
                    const $tempRow = $(allRows[rowIndex].html);
                    $tempRow.find('td:eq(2) strong').text(skillName);
                    $tempRow.data('skill-name', skillName.toLowerCase());
                    $tempRow.find('.btn-view').data('skill-name', skillName);
                    $tempRow.find('.btn-view').data('skill-display-name', skillName);
                    $tempRow.find('.btn-view').attr('data-skill-name', skillName);
                    $tempRow.find('.btn-view').attr('data-skill-display-name', skillName);
                    $tempRow.find('.btn-edit').data('skill-name', skillName);
                    $tempRow.find('.btn-edit').attr('data-skill-name', skillName);
                    $tempRow.find('.btn-delete').data('skill-name', skillName);
                    $tempRow.find('.btn-delete').attr('data-skill-name', skillName);
                    allRows[rowIndex].html = $tempRow[0].outerHTML;
                }
                
                // Re-filter và re-render (giữ nguyên trang hiện tại)
                const searchValue = $('#search-input').val();
                filterRows(searchValue, false);
                
                // Đóng modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editSkillModal'));
                modal.hide();
                $btn.prop('disabled', false).html(originalHtml);
                
                // Hiển thị thông báo thành công
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: `Đã cập nhật kỹ năng "${skillName}" thành công!`,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
                
                console.log('Error response:', xhr);
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $input.addClass('is-invalid');
                    $input.siblings('.invalid-feedback').text(xhr.responseJSON.errors.name[0]);
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: xhr.responseJSON.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra khi cập nhật kỹ năng! (Status: ' + xhr.status + ')'
                    });
                }
            }
        });
    });

    // Code xóa kỹ năng đã được chuyển sang SweetAlert2 ở trên (dòng 545)

    // Reset form khi đóng modal (Bootstrap 5)
    document.getElementById('addSkillModal').addEventListener('hidden.bs.modal', function() {
        $('#addSkillForm')[0].reset();
        $('#add_skill_name').removeClass('is-invalid');
        $('#btnAddSkill').prop('disabled', false).html('<i class="fas fa-save"></i> Lưu');
    });

    document.getElementById('editSkillModal').addEventListener('hidden.bs.modal', function() {
        $('#edit_skill_name').removeClass('is-invalid');
        $('#btnEditSkill').prop('disabled', false).html('<i class="fas fa-save"></i> Cập nhật');
    });

    // ===== CHECKBOX VÀ XÓA HÀNG LOẠT =====
    
    // Chọn tất cả checkbox (trên trang hiện tại)
    $('#select-all-checkbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        
        $('.skill-checkbox').each(function() {
            const skillId = parseInt($(this).val());
            $(this).prop('checked', isChecked);
            
            if (isChecked) {
                // Thêm vào selectedSkillIds nếu chưa có
                if (!selectedSkillIds.includes(skillId)) {
                    selectedSkillIds.push(skillId);
                }
            } else {
                // Xóa khỏi selectedSkillIds
                const index = selectedSkillIds.indexOf(skillId);
                if (index > -1) {
                    selectedSkillIds.splice(index, 1);
                }
            }
        });
        
        updateSelectedCount();
    });
    
    // Khi click vào checkbox riêng lẻ
    $(document).on('change', '.skill-checkbox', function() {
        const skillId = parseInt($(this).val());
        const isChecked = $(this).is(':checked');
        
        if (isChecked) {
            // Thêm vào selectedSkillIds nếu chưa có
            if (!selectedSkillIds.includes(skillId)) {
                selectedSkillIds.push(skillId);
            }
        } else {
            // Xóa khỏi selectedSkillIds
            const index = selectedSkillIds.indexOf(skillId);
            if (index > -1) {
                selectedSkillIds.splice(index, 1);
            }
        }
        
        updateSelectedCount();
        updateSelectAllCheckbox();
    });
    
    // Cập nhật trạng thái checkbox "Chọn tất cả"
    function updateSelectAllCheckbox() {
        const totalVisible = $('.skill-checkbox').length;
        const totalChecked = $('.skill-checkbox:checked').length;
        $('#select-all-checkbox').prop('checked', totalVisible > 0 && totalVisible === totalChecked);
    }
    
    // Cập nhật số lượng đã chọn
    function updateSelectedCount() {
        const count = selectedSkillIds.length;
        $('#selected-count').text(count);
        
        if (count > 0) {
            $('#delete-selected-btn').show();
        } else {
            $('#delete-selected-btn').hide();
        }
    }
    
    // Xóa các kỹ năng đã chọn
    $('#delete-selected-btn').on('click', async function() {
        if (selectedSkillIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa chọn kỹ năng!',
                text: 'Vui lòng chọn ít nhất một kỹ năng để xóa!'
            });
            return;
        }
        
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa',
            text: `Bạn có chắc chắn muốn xóa ${selectedSkillIds.length} kỹ năng đã chọn?`,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        });
        
        if (!result.isConfirmed) {
            return;
        }
        
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xóa...');
        
        // Xóa từng skill
        let deletedCount = 0;
        let errorCount = 0;
        const idsToDelete = [...selectedSkillIds]; // Copy array
        
        idsToDelete.forEach((skillId, index) => {
            $.ajax({
                url: `/admin/skills/${skillId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    deletedCount++;
                    
                    // Xóa khỏi allRows
                    const rowIndex = allRows.findIndex(row => row.skillId == skillId);
                    if (rowIndex !== -1) {
                        allRows.splice(rowIndex, 1);
                    }
                    
                    // Xóa khỏi selectedSkillIds
                    const selectedIndex = selectedSkillIds.indexOf(skillId);
                    if (selectedIndex > -1) {
                        selectedSkillIds.splice(selectedIndex, 1);
                    }
                    
                    // Nếu đã xóa hết
                    if (deletedCount + errorCount === idsToDelete.length) {
                        finishBulkDelete();
                    }
                },
                error: function(xhr) {
                    errorCount++;
                    console.error('Error deleting skill ' + skillId, xhr);
                    
                    // Nếu đã xóa hết
                    if (deletedCount + errorCount === idsToDelete.length) {
                        finishBulkDelete();
                    }
                }
            });
        });
        
        function finishBulkDelete() {
            $btn.prop('disabled', false).html(originalHtml);
            
            if (errorCount > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Hoàn thành!',
                    html: `Đã xóa <b>${deletedCount}/${idsToDelete.length}</b> kỹ năng.<br>${errorCount} kỹ năng không thể xóa.`
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: `Đã xóa thành công ${deletedCount} kỹ năng!`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            
            // Re-render table (giữ nguyên trang hiện tại)
            const searchValue = $('#search-input').val();
            filterRows(searchValue, false);
            
            // Reset checkboxes
            $('#select-all-checkbox').prop('checked', false);
            updateSelectedCount();
        }
    });
});
</script>
@endpush
