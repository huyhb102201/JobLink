@extends('admin.layouts.app')

@section('title', 'Duyệt bài đăng')

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Fix layout cho bảng */
    #pending-jobs-table {
        width: 100% !important;
        table-layout: fixed;
    }
    
    #pending-jobs-table th,
    #pending-jobs-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Custom sorting cho DataTables */
    #pending-jobs-table th.sorting,
    #pending-jobs-table th.sorting_asc,
    #pending-jobs-table th.sorting_desc {
        cursor: pointer;
        position: relative;
        padding-right: 30px !important;
    }
    
    #pending-jobs-table th.sorting:after,
    #pending-jobs-table th.sorting_asc:after,
    #pending-jobs-table th.sorting_desc:after {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 12px;
        color: #6c757d;
    }
    
    #pending-jobs-table th.sorting:after {
        content: "\f0dc"; /* fa-sort */
    }
    
    #pending-jobs-table th.sorting_asc:after {
        content: "\f0de"; /* fa-sort-up */
        color: #0d6efd;
    }
    
    #pending-jobs-table th.sorting_desc:after {
        content: "\f0dd"; /* fa-sort-down */
        color: #0d6efd;
    }
    
    /* Định width cho từng cột */
    #pending-jobs-table th:nth-child(1),
    #pending-jobs-table td:nth-child(1) { width: 50px; } /* Checkbox */
    
    #pending-jobs-table th:nth-child(2),
    #pending-jobs-table td:nth-child(2) { 
        width: 300px; 
        white-space: normal;
    } /* Tiêu đề */
    
    #pending-jobs-table th:nth-child(3),
    #pending-jobs-table td:nth-child(3) { width: 180px; } /* Người đăng */
    
    #pending-jobs-table th:nth-child(4),
    #pending-jobs-table td:nth-child(4) { width: 120px; } /* Ngân sách */
    
    #pending-jobs-table th:nth-child(5),
    #pending-jobs-table td:nth-child(5) { width: 130px; } /* Ngày đăng */
    
    #pending-jobs-table th:nth-child(6),
    #pending-jobs-table td:nth-child(6) { 
        width: 200px; 
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
    
    /* Job title styling */
    .job-title {
        font-weight: 600;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .job-description {
        font-size: 0.85rem;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Duyệt bài đăng</h1>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Tổng số bài đăng</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($statistics['total_jobs']) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Đã duyệt</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($statistics['approved_jobs']) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Từ chối</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($statistics['rejected_jobs']) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Chờ duyệt</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($statistics['pending_jobs']) }}</div>
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
        <h6 class="m-0 font-weight-bold text-primary">Danh sách bài đăng chờ duyệt</h6>
        <div>
            <button id="bulk-approve-btn" type="button" class="btn btn-success btn-sm me-2" disabled>
                <i class="fas fa-check me-1"></i> Duyệt hàng loạt
            </button>
            <button id="bulk-reject-btn" type="button" class="btn btn-danger btn-sm" disabled>
                <i class="fas fa-times me-1"></i> Từ chối hàng loạt
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="pending-jobs-table">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" class="form-check-input" id="checkAll"></th>
                        <th>Tiêu đề công việc</th>
                        <th>Người đăng</th>
                        <th>Ngân sách</th>
                        <th>Ngày đăng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jobs as $job)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input row-checkbox" data-job-id="{{ $job->job_id }}">
                        </td>
                        <td>
                            <div class="job-title">{{ $job->title }}</div>
                            <div class="job-description">{{ Str::limit($job->description, 100) }}</div>
                        </td>
                        <td>
                            @if($job->client && $job->client->profile && $job->client->profile->fullname)
                                {{ $job->client->profile->fullname }}
                            @elseif($job->client && $job->client->name)
                                {{ $job->client->name }}
                            @elseif($job->client && $job->client->email)
                                {{ $job->client->email }}
                            @else
                                <span class="text-muted">Không có thông tin</span>
                            @endif
                        </td>
                        <td>
                            @if($job->budget)
                                <span class="badge bg-info">${{ number_format($job->budget) }}</span>
                            @else
                                <span class="badge bg-secondary">Thỏa thuận</span>
                            @endif
                        </td>
                        <td>{{ $job->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-info btn-sm view-job-btn" 
                                        data-job-id="{{ $job->job_id }}" 
                                        title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-success btn-sm approve-job-btn" 
                                        data-job-id="{{ $job->job_id }}" 
                                        title="Duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-danger btn-sm reject-job-btn" 
                                        data-job-id="{{ $job->job_id }}" 
                                        title="Từ chối">
                                    <i class="fas fa-times"></i>
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

<!-- Modal xem chi tiết job -->
<div class="modal fade" id="jobDetailModal" tabindex="-1" aria-labelledby="jobDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="jobDetailModalLabel">
                    <i class="fas fa-briefcase me-2"></i>Chi tiết công việc
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="jobDetailContent">
                <!-- Content sẽ được load bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Đóng
                </button>
                <button type="button" class="btn btn-success" id="modalApproveBtn">
                    <i class="fas fa-check me-1"></i>Duyệt
                </button>
                <button type="button" class="btn btn-danger" id="modalRejectBtn">
                    <i class="fas fa-ban me-1"></i>Từ chối
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Version: 2025-10-16 - Preload job details for instant modal display
const jobDetailsData = @json($jobDetails);

$(document).ready(function() {
    let selectedJobs = new Set();
    let columnStates = {}; // Lưu trạng thái sort của từng cột
    let table; // Khai báo trước để tránh lỗi

    function updateButtonStates() {
        const hasSelection = selectedJobs.size > 0;
        const count = selectedJobs.size;
        
        $('#bulk-approve-btn').prop('disabled', !hasSelection);
        $('#bulk-reject-btn').prop('disabled', !hasSelection);
        
        // Cập nhật text hiển thị số lượng
        if (count > 0) {
            $('#bulk-approve-btn').html(`<i class="fas fa-check me-1"></i> Duyệt hàng loạt (${count})`);
            $('#bulk-reject-btn').html(`<i class="fas fa-times me-1"></i> Từ chối hàng loạt (${count})`);
        } else {
            $('#bulk-approve-btn').html('<i class="fas fa-check me-1"></i> Duyệt hàng loạt');
            $('#bulk-reject-btn').html('<i class="fas fa-times me-1"></i> Từ chối hàng loạt');
        }
    }
    
    function updateCheckAllState() {
        // Kiểm tra xem table đã được khởi tạo chưa
        if (!table) {
            return;
        }
        
        // Kiểm tra TẤT CẢ job (trên mọi trang)
        const totalJobs = table.rows().count();
        const selectedCount = selectedJobs.size;
        
        if (selectedCount === 0) {
            $('#checkAll').prop('checked', false);
            $('#checkAll').prop('indeterminate', false);
        } else if (selectedCount === totalJobs) {
            $('#checkAll').prop('checked', true);
            $('#checkAll').prop('indeterminate', false);
        } else {
            $('#checkAll').prop('checked', false);
            $('#checkAll').prop('indeterminate', true);
        }
    }

    // Khởi tạo DataTables với 3-state sorting
    table = $('#pending-jobs-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true,
        order: [], // Không sort mặc định
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        stateSave: true,
        searchDelay: 0,
        columnDefs: [
            { orderable: false, targets: [0, 5] }, // Tắt sort cho checkbox và action columns
            { className: "text-center", targets: [0, 5] }
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
                const jobId = $(this).data('job-id');
                if (selectedJobs.has(jobId)) {
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

    // Check all functionality - Chọn tất cả trên MỌI TRANG
    $('#checkAll').on('change', function() {
        const isChecked = this.checked;
        
        if (isChecked) {
            // Chọn TẤT CẢ job (trên mọi trang)
            selectedJobs.clear();
            table.rows().nodes().to$().each(function() {
                const checkbox = $(this).find('.row-checkbox');
                const jobId = checkbox.data('job-id');
                
                if (jobId) {
                    checkbox.prop('checked', true);
                    selectedJobs.add(jobId);
                }
            });
        } else {
            // Bỏ chọn TẤT CẢ
            table.rows().nodes().to$().each(function() {
                const checkbox = $(this).find('.row-checkbox');
                checkbox.prop('checked', false);
            });
            selectedJobs.clear();
        }
        
        updateButtonStates();
    });

    // Initial bind
    bindEvents();

    // Custom 3-state sorting
    $('#pending-jobs-table thead th').on('click', function() {
        const columnIndex = $(this).index();
        
        // Bỏ qua cột checkbox và thao tác
        if (columnIndex === 0 || columnIndex === 5) return;
        
        // Khởi tạo state nếu chưa có
        if (!columnStates[columnIndex]) {
            columnStates[columnIndex] = 'none';
        }
        
        // Reset tất cả cột khác
        Object.keys(columnStates).forEach(key => {
            if (key != columnIndex) {
                columnStates[key] = 'none';
                $(`#pending-jobs-table thead th:eq(${key})`).removeClass('sorting_asc sorting_desc').addClass('sorting');
            }
        });
        
        // Cycle through states: none -> asc -> desc -> none
        switch(columnStates[columnIndex]) {
            case 'none':
                columnStates[columnIndex] = 'asc';
                table.order([columnIndex, 'asc']).draw();
                $(this).removeClass('sorting sorting_desc').addClass('sorting_asc');
                break;
            case 'asc':
                columnStates[columnIndex] = 'desc';
                table.order([columnIndex, 'desc']).draw();
                $(this).removeClass('sorting sorting_asc').addClass('sorting_desc');
                break;
            case 'desc':
                columnStates[columnIndex] = 'none';
                // Trở về thứ tự ban đầu
                table.order([]).draw();
                $(this).removeClass('sorting_asc sorting_desc').addClass('sorting');
                break;
        }
    });

    function bindEvents() {
        // Checkbox events
        $('.row-checkbox').off('change').on('change', function() {
            const jobId = $(this).data('job-id');
            if (this.checked) {
                selectedJobs.add(jobId);
                console.log('Added job:', jobId, '| Total:', selectedJobs.size);
            } else {
                selectedJobs.delete(jobId);
                console.log('Removed job:', jobId, '| Total:', selectedJobs.size);
            }
            updateButtonStates();
            updateCheckAllState();
        });

        // Approve button events
        $('.approve-job-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const jobId = $(this).data('job-id');
            
            Swal.fire({
                title: 'Duyệt bài đăng?',
                text: "Bạn chắc chắn muốn duyệt bài đăng này?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý duyệt',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('Đang duyệt bài đăng...');
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/jobs/${jobId}/approve`
                    }).append('@csrf');
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // Reject button events
        $('.reject-job-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const jobId = $(this).data('job-id');
            
            Swal.fire({
                title: 'Từ chối bài đăng?',
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
                    showLoading('Đang từ chối bài đăng...');
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/jobs/${jobId}/reject`
                    }).append(
                        '@csrf',
                        $('<input>', { type: 'hidden', name: 'reject_reason', value: result.value })
                    );
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // View job button events - Sử dụng dữ liệu đã preload
        $('.view-job-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const jobId = $(this).data('job-id');
            
            // Show job detail instantly from preloaded data
            showJobDetailFromPreload(jobId);
        });
    }

    // Function to show job detail from preloaded data
    function showJobDetailFromPreload(jobId) {
        const jobDetail = jobDetailsData.find(job => job.job_id == jobId);
        
        if (!jobDetail) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không tìm thấy thông tin công việc'
            });
            return;
        }
        
        // Build HTML content
        let html = `
            <div class="row">
                <div class="col-md-12">
                    <h4 class="mb-3">${jobDetail.title}</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô tả công việc:</label>
                        <textarea class="form-control" rows="8" readonly style="resize: none; background-color: #f8f9fa;">${jobDetail.description || 'Không có mô tả'}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ngân sách:</label>
                                <input type="text" class="form-control" value="${jobDetail.budget ? '$' + new Intl.NumberFormat().format(jobDetail.budget) : 'Thỏa thuận'}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Thời hạn:</label>
                                <input type="text" class="form-control" value="${jobDetail.deadline || 'Không có'}" readonly>
                            </div>
                        </div>
                    </div>
                    
                    ${jobDetail.requirements ? `
                    <div class="mb-3">
                        <label class="form-label fw-bold">Yêu cầu:</label>
                        <textarea class="form-control" rows="5" readonly style="resize: none; background-color: #f8f9fa;">${jobDetail.requirements}</textarea>
                    </div>
                    ` : ''}
                    
                    ${jobDetail.skills && jobDetail.skills.length > 0 ? `
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kỹ năng yêu cầu:</label>
                        <div class="border rounded p-2" style="background-color: #f8f9fa; min-height: 50px;">
                            ${jobDetail.skills.map(skill => `<span class="badge bg-primary me-1 mb-1">${skill.skill_name}</span>`).join('')}
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Thông tin chi tiết</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Người đăng:</label>
                                <input type="text" class="form-control" value="${jobDetail.client_name || 'N/A'}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email:</label>
                                <input type="text" class="form-control" value="${jobDetail.client_email || 'N/A'}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ngày đăng:</label>
                                <input type="text" class="form-control" value="${jobDetail.created_at}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Danh mục:</label>
                                <input type="text" class="form-control" value="${jobDetail.category_name || 'Không có'}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Update modal content
        $('#jobDetailContent').html(html);
        
        // Set job ID to modal buttons
        $('#modalApproveBtn').data('job-id', jobId);
        $('#modalRejectBtn').data('job-id', jobId);
        
        // Show modal
        $('#jobDetailModal').modal('show');
    }

    // Bulk operations
    function performBulkApprove() {
        if (selectedJobs.size === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn ít nhất một bài đăng', 'warning');
            return;
        }

        const jobIds = Array.from(selectedJobs);

        Swal.fire({
            title: `Bạn có chắc chắn muốn duyệt ${jobIds.length} bài đăng đã chọn?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý duyệt',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Đang duyệt hàng loạt...');
                const form = $('<form>', {
                    method: 'POST',
                    action: '/admin/jobs/batch-approve'
                }).append(
                    '@csrf',
                    $('<input>', { type: 'hidden', name: 'job_ids', value: jobIds.join(',') })
                );
                $('body').append(form);
                form.submit();
            }
        });
    }

    function performBulkReject() {
        if (selectedJobs.size === 0) {
            Swal.fire('Thông báo', 'Vui lòng chọn ít nhất một bài đăng', 'warning');
            return;
        }

        const jobIds = Array.from(selectedJobs);

        Swal.fire({
            title: `Từ chối ${jobIds.length} bài đăng đã chọn?`,
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
                const form = $('<form>', {
                    method: 'POST',
                    action: '/admin/jobs/batch-reject'
                }).append(
                    '@csrf',
                    $('<input>', { type: 'hidden', name: 'job_ids', value: jobIds.join(',') }),
                    $('<input>', { type: 'hidden', name: 'reject_reason', value: result.value || '' })
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
        const jobId = $(this).data('job-id');
        if (jobId) {
            Swal.fire({
                title: 'Duyệt bài đăng?',
                text: "Bạn có chắc chắn muốn duyệt bài đăng này?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý duyệt',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('Đang duyệt bài đăng...');
                    const form = $('<form>', {
                        method: 'POST',
                        action: `/admin/jobs/${jobId}/approve`
                    }).append('@csrf');
                    $('body').append(form);
                    form.submit();
                }
            });
        }
    });

    $('#modalRejectBtn').on('click', function() {
        const jobId = $(this).data('job-id');
        if (jobId) {
            // Đóng modal trước khi hiện SweetAlert
            const modal = bootstrap.Modal.getInstance(document.getElementById('jobDetailModal'));
            if (modal) {
                modal.hide();
            }
            
            // Đợi modal đóng xong rồi mới hiện SweetAlert
            setTimeout(() => {
                Swal.fire({
                    title: 'Từ chối bài đăng?',
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
                        showLoading('Đang từ chối bài đăng...');
                        const form = $('<form>', {
                            method: 'POST',
                            action: `/admin/jobs/${jobId}/reject`
                        }).append(
                            '@csrf',
                            $('<input>', { type: 'hidden', name: 'reject_reason', value: result.value })
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });
            }, 300); // Đợi 300ms để modal đóng hoàn toàn
        }
    });

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
