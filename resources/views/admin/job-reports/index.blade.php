@extends('admin.layouts.app')

@section('title', 'Quản lý báo cáo Job')

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
    
    #reports-table th,
    #reports-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
    }
    
    .badge {
        font-size: 0.875rem;
        padding: 0.35rem 0.65rem;
    }
    
    .report-preview {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .image-gallery {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    
    .image-gallery img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.3s ease;
        border: 2px solid #ddd;
    }
    
    .image-gallery img:hover {
        transform: scale(1.05);
        border-color: #4e73df;
    }
    
    .reporter-item {
        border: none;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 4px solid #667eea;
    }
    
    .reporter-item:hover {
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }
    
    .reporter-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .reporter-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 1.2rem;
        margin-right: 1rem;
    }
    
    .report-detail-item {
        background-color: #e8eaf6;
        border-left: 4px solid #667eea;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(102, 126, 234, 0.1);
    }
    
    .report-detail-item:hover {
        background-color: #dce0f5;
        border-left-color: #764ba2;
        box-shadow: 0 2px 6px rgba(102, 126, 234, 0.15);
    }
    
    .report-detail-item p {
        margin-bottom: 0.5rem;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    
    .report-detail-item strong {
        color: #2c3e50;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .image-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
    }
    
    .image-modal-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    
    .image-modal-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-flag"></i> Quản lý báo cáo Job
        </h1>
    </div>

    <!-- Thống kê -->
    <div class="row g-4 mb-4">
        <!-- Tổng báo cáo -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Tổng số báo cáo
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalReports }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Số job bị báo cáo -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Số job bị báo cáo
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalJobsReported }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job bị báo cáo tuần này -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tổng số báo cáo tuần này
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $jobsReportedThisWeek }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job bị báo cáo tháng này -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng số báo cáo tháng này
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $jobsReportedThisMonth }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng báo cáo -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách Job bị báo cáo</h6>
        </div>
        <div class="card-body">
            <!-- Tìm kiếm và Bulk Actions -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div style="width: 100%; max-width: 500px;">
                    <input type="text" 
                           id="search-input"
                           class="form-control" 
                           placeholder="Tìm kiếm theo Job ID, tên job hoặc chủ job..." 
                           autocomplete="off">
                </div>
                <div id="bulk-actions">
                    <button class="btn btn-warning btn-sm" id="bulk-lock-btn" disabled>
                        <i class="fas fa-lock"></i> Khóa
                    </button>
                    <button class="btn btn-success btn-sm" id="bulk-unlock-btn" disabled>
                        <i class="fas fa-unlock"></i> Mở khóa
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="reports-table" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;" class="text-center">
                                <input type="checkbox" id="select-all-checkbox" title="Chọn tất cả">
                            </th>
                            <th style="width: 8%; cursor: pointer;" class="sortable" data-column="job_id">
                                Job ID <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 24%; cursor: pointer;" class="sortable" data-column="job_title">
                                Tên Job <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 20%; cursor: pointer;" class="sortable" data-column="job_owner">
                                Chủ Job <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 10%; cursor: pointer;" class="text-center sortable" data-column="report_count">
                                Số báo cáo <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 10%; cursor: pointer;" class="text-center sortable" data-column="status">
                                Trạng thái <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 23%;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody">
                        @forelse($reportsData as $report)
                        <tr data-job-id="{{ $report['job_id'] }}" 
                            data-job-title="{{ strtolower($report['job_title']) }}" 
                            data-job-owner="{{ strtolower($report['job_owner']) }}"
                            data-report-count="{{ $report['report_count'] }}"
                            data-status="{{ $report['status'] }}">
                            <td class="text-center">
                                <input type="checkbox" class="report-checkbox" value="{{ $report['job_id'] }}">
                            </td>
                            <td>{{ $report['job_id'] }}</td>
                            <td>
                                <strong>{{ $report['job_title'] }}</strong>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $report['job_owner'] }}</strong><br>
                                    <small class="text-muted">{{ $report['job_owner_email'] }}</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger" style="font-size: 1rem;">
                                    {{ $report['report_count'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($report['status'] == 2)
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-lock"></i> Đã khóa
                                    </span>
                                @elseif($report['status'] == 0)
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle"></i> Từ chối
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-clock"></i> Chờ xử lý
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info btn-view-details" 
                                        data-job-id="{{ $report['job_id'] }}"
                                        data-job-title="{{ $report['job_title'] }}"
                                        data-job-owner="{{ $report['job_owner'] }}"
                                        data-job-owner-email="{{ $report['job_owner_email'] }}"
                                        data-total-reports="{{ $report['report_count'] }}"
                                        data-reporters="{{ json_encode($report['reporters']) }}"
                                        title="Xem chi tiết báo cáo">
                                    <i class="fas fa-eye"></i> Xem chi tiết
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <i class="fas fa-inbox"></i> Không có báo cáo nào
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Client-side -->
            <div class="d-flex justify-content-between align-items-center mt-4" id="pagination-container">
                <div class="text-muted" id="pagination-info">
                    Hiển thị 1 đến {{ min(10, $totalJobsReported) }} trong tổng số {{ $totalJobsReported }} kết quả
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

<!-- Modal Xem chi tiết báo cáo -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <h5 class="modal-title text-white" id="viewDetailsModalLabel">
                    <i class="fas fa-info-circle"></i> Chi tiết báo cáo Job
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background-color: #f8f9fc; padding: 2rem;">
                <!-- Thông tin Job -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; margin-right: 12px;">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h6 class="mb-0 text-primary" style="font-size: 1.1rem; font-weight: 600;">Thông tin Job</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-2">
                                    <i class="fas fa-hashtag text-muted me-2 mt-1" style="font-size: 0.9rem;"></i>
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">ID Job</small>
                                        <span id="detail_job_id" class="fw-bold" style="font-size: 1rem;"></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-file-alt text-muted me-2 mt-1" style="font-size: 0.9rem;"></i>
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Tên Job</small>
                                        <span id="detail_job_title" class="fw-bold" style="font-size: 1rem;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-2">
                                    <i class="fas fa-user text-muted me-2 mt-1" style="font-size: 0.9rem;"></i>
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Chủ Job</small>
                                        <span id="detail_job_owner" class="fw-bold" style="font-size: 1rem;"></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-envelope text-muted me-2 mt-1" style="font-size: 0.9rem;"></i>
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Email</small>
                                        <span id="detail_job_owner_email" class="text-muted" style="font-size: 0.95rem;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 p-3 rounded" style="background: linear-gradient(135deg, #667eea40 0%, #764ba240 100%); border-left: 4px solid #667eea;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chart-bar text-primary me-2" style="font-size: 1.2rem;"></i>
                                <span class="text-dark fw-bold">Tổng số báo cáo:</span>
                                <span id="detail_total_reports" class="badge bg-danger ms-2" style="font-size: 1rem; padding: 0.4rem 0.8rem;"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách người báo cáo -->
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; margin-right: 12px;">
                            <i class="fas fa-users"></i>
                        </div>
                        <h6 class="mb-0 text-danger" style="font-size: 1.1rem; font-weight: 600;">Danh sách người báo cáo</h6>
                    </div>
                    <div id="reporters_list" style="max-height: 450px; overflow-y: auto;">
                        <!-- Sẽ được load bằng JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f8f9fc; border-top: 1px solid #e3e6f0;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xóa tất cả báo cáo của job -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAllModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Xác nhận xóa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa <strong>TẤT CẢ</strong> báo cáo của job này?</p>
                <p class="text-danger"><i class="fas fa-info-circle"></i> Hành động này không thể hoàn tác!</p>
                <input type="hidden" id="delete_job_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAllBtn">
                    <i class="fas fa-trash"></i> Xóa tất cả
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal hiển thị ảnh full size -->
<div id="imageModal" class="image-modal">
    <span class="image-modal-close">&times;</span>
    <img class="image-modal-content" id="modalImage">
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

    // Xem chi tiết báo cáo - HIỂN THỊ NGAY LẬP TỨC (sử dụng event delegation)
    $(document).on('click', '.btn-view-details', function() {
        const jobId = $(this).data('job-id');
        const jobTitle = $(this).data('job-title');
        const jobOwner = $(this).data('job-owner');
        const jobOwnerEmail = $(this).data('job-owner-email');
        const totalReports = $(this).data('total-reports');
        const reporters = $(this).data('reporters');
        
        // Hiển thị thông tin job NGAY LẬP TỨC
        $('#detail_job_id').text(jobId);
        $('#detail_job_title').text(jobTitle);
        $('#detail_job_owner').text(jobOwner);
        $('#detail_job_owner_email').text(jobOwnerEmail);
        $('#detail_total_reports').text(totalReports);
        
        // Hiển thị danh sách người báo cáo NGAY LẬP TỨC
        let reportersHtml = '';
        
        if (!reporters || reporters.length === 0) {
            reportersHtml = '<p class="text-center text-muted">Không có báo cáo nào</p>';
        } else {
            reporters.forEach(function(reporter) {
                reportersHtml += `
                    <div class="reporter-item">
                        <div class="reporter-header">
                            <div class="reporter-avatar">
                                ${reporter.username.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold" style="color: #2c3e50; font-size: 1.1rem;">
                                    ${reporter.username}
                                </h6>
                                <small class="text-muted" style="font-size: 0.9rem;">
                                    <i class="fas fa-envelope me-1"></i>${reporter.email}
                                </small>
                            </div>
                            <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 0.9rem; padding: 0.4rem 0.8rem;">
                                ${reporter.report_count} báo cáo
                            </span>
                        </div>
                        
                        <div>
                `;
                
                reporter.reports.forEach(function(report) {
                    reportersHtml += `
                        <div class="report-detail-item">
                            <p class="mb-1"><strong>Lý do:</strong> ${report.reason}</p>
                            <p class="mb-1"><strong>Nội dung:</strong> ${report.message || 'Không có'}</p>
                            <p class="mb-1"><strong>Thời gian:</strong> ${report.created_at}</p>
                    `;
                    
                    // Hiển thị ảnh (tối đa 5 ảnh)
                    if (report.images && report.images.length > 0) {
                        reportersHtml += '<div class="image-gallery">';
                        report.images.forEach(function(imageUrl) {
                            reportersHtml += `
                                <img src="${imageUrl}" 
                                     alt="Ảnh báo cáo" 
                                     class="report-image"
                                     onclick="showImageModal('${imageUrl}')">
                            `;
                        });
                        reportersHtml += '</div>';
                    }
                    
                    reportersHtml += '</div>';
                });
                
                reportersHtml += `
                        </div>
                    </div>
                `;
            });
        }
        
        $('#reporters_list').html(reportersHtml);
        
        // Mở modal NGAY LẬP TỨC - không cần đợi AJAX
        $('#viewDetailsModal').modal('show');
    });

    // Toggle khóa/mở khóa báo cáo của job
    $(document).on('click', '.btn-toggle-lock', async function() {
        const jobId = $(this).data('job-id');
        const currentStatus = $(this).data('current-status');
        const $row = $(this).closest('tr');
        const jobTitle = $row.find('td:eq(2) strong').text();
        const isLocked = currentStatus === 'locked';
        const actionText = isLocked ? 'mở khóa' : 'khóa';
        
        const result = await Swal.fire({
            icon: isLocked ? 'info' : 'warning',
            title: `Xác nhận ${actionText}`,
            html: `Bạn có chắc chắn muốn ${actionText} báo cáo của job này?<br><br><strong>${jobTitle}</strong>`,
            showCancelButton: true,
            confirmButtonColor: isLocked ? '#28a745' : '#f6c23e',
            cancelButtonColor: '#3085d6',
            confirmButtonText: isLocked ? 'Mở khóa' : 'Khóa',
            cancelButtonText: 'Hủy'
        });
        
        if (!result.isConfirmed) {
            return;
        }
        
        $.ajax({
            url: `/admin/job-reports/job/${jobId}/toggle-lock`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Update row status in allRows
                    const rowIndex = allRows.findIndex(row => row.jobId == jobId);
                    if (rowIndex !== -1) {
                        allRows[rowIndex].status = response.new_status;
                    }
                    
                    // Re-render table
                    const searchValue = $('#search-input').val();
                    filterRows(searchValue);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra: ' + response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi thay đổi trạng thái báo cáo!'
                });
                console.error('Error:', xhr);
            }
        });
    });

    // Xóa tất cả báo cáo của job (sử dụng SweetAlert2)
    $(document).on('click', '.btn-delete-all', async function() {
        const jobId = $(this).data('job-id');
        const $row = $(this).closest('tr');
        const jobTitle = $row.find('td:eq(2) strong').text();
        
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa',
            html: `Bạn có chắc chắn muốn xóa <b>TẤT CẢ</b> báo cáo của job này?<br><br><strong>${jobTitle}</strong><br><small class="text-danger">Hành động này không thể hoàn tác!</small>`,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa tất cả',
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
        
        $.ajax({
            url: `/admin/job-reports/job/${jobId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Xóa khỏi allRows
                    const rowIndex = allRows.findIndex(row => row.jobId == jobId);
                    if (rowIndex !== -1) {
                        allRows.splice(rowIndex, 1);
                    }
                    
                    // Xóa khỏi selectedJobIds nếu có
                    const selectedIndex = selectedJobIds.indexOf(parseInt(jobId));
                    if (selectedIndex > -1) {
                        selectedJobIds.splice(selectedIndex, 1);
                    }
                    
                    // Re-render table
                    const searchValue = $('#search-input').val();
                    filterRows(searchValue);
                    
                    // Hiển thị thông báo thành công
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa!',
                        text: 'Đã xóa tất cả báo cáo của job này thành công.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    updateSelectedCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra: ' + response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi xóa báo cáo!'
                });
                console.error('Error:', xhr);
            }
        });
    });

    // Hiển thị modal ảnh full size
    window.showImageModal = function(imageUrl) {
        $('#modalImage').attr('src', imageUrl);
        $('#imageModal').fadeIn();
    };

    // Đóng modal ảnh
    $('.image-modal-close').on('click', function() {
        $('#imageModal').fadeOut();
    });

    // Đóng modal khi click bên ngoài ảnh
    $('#imageModal').on('click', function(e) {
        if (e.target.id === 'imageModal') {
            $(this).fadeOut();
        }
    });

    // ===== PAGINATION CLIENT-SIDE =====
    let allRows = [];
    let filteredRows = [];
    let currentPage = 1;
    let perPage = 10;
    let selectedJobIds = []; // Lưu trữ các Job ID đã chọn
    
    // Lưu tất cả rows khi load trang
    $('#reports-tbody tr').each(function() {
        const $row = $(this);
        const jobId = $row.data('job-id');
        
        // Chỉ lưu row có job-id (bỏ qua row empty)
        if (jobId) {
            allRows.push({
                html: $row[0].outerHTML,
                jobId: jobId,
                jobTitle: $row.data('job-title'),
                jobOwner: $row.data('job-owner'),
                reportCount: $row.data('report-count'),
                status: $row.data('status') || 'active'
            });
        }
    });
    filteredRows = allRows.slice();
    
    console.log('Total rows loaded:', allRows.length);
    console.log('All rows:', allRows);
    
    // Hàm render pagination
    function renderPagination() {
        const totalPages = Math.ceil(filteredRows.length / perPage);
        const start = (currentPage - 1) * perPage + 1;
        const end = Math.min(currentPage * perPage, filteredRows.length);
        
        if (filteredRows.length === 0) {
            $('#pagination-info').text('Không có kết quả');
        } else {
            $('#pagination-info').text(`Hiển thị ${start} đến ${end} trong tổng số ${filteredRows.length} kết quả`);
        }
        
        let paginationHtml = '';
        
        if (currentPage === 1) {
            paginationHtml += '<li class="page-item disabled"><span class="page-link">‹ Trước</span></li>';
        } else {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">‹ Trước</a></li>`;
        }
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }
        
        if (currentPage === totalPages || totalPages === 0) {
            paginationHtml += '<li class="page-item disabled"><span class="page-link">Tiếp ›</span></li>';
        } else {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Tiếp ›</a></li>`;
        }
        
        $('#pagination-links').html(paginationHtml);
        
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
        
        $('#reports-tbody').empty();
        
        if (pageRows.length === 0) {
            $('#reports-tbody').html(
                '<tr><td colspan="7" class="text-center text-muted py-4">' +
                '<i class="fas fa-search fa-2x mb-2"></i><br>' +
                'Không tìm thấy kết quả phù hợp' +
                '</td></tr>'
            );
        } else {
            pageRows.forEach(function(rowData) {
                $('#reports-tbody').append(rowData.html);
            });
            
            // Khôi phục trạng thái checkbox
            restoreCheckboxStates();
        }
        
        renderPagination();
    }
    
    // Hàm filter
    function filterRows(searchValue) {
        searchValue = searchValue.toLowerCase().trim();
        
        if (searchValue === '') {
            filteredRows = allRows.slice();
        } else {
            filteredRows = allRows.filter(function(rowData) {
                const jobId = rowData.jobId.toString().toLowerCase();
                const jobTitle = rowData.jobTitle;
                const jobOwner = rowData.jobOwner;
                return jobId.includes(searchValue) || jobTitle.includes(searchValue) || jobOwner.includes(searchValue);
            });
        }
        
        currentPage = 1;
        renderTable();
    }
    
    // Initial render
    renderTable();

    // Tự động tìm kiếm khi nhập - CLIENT-SIDE
    $('#search-input').on('input', function() {
        filterRows($(this).val());
    });

    // Xử lý sắp xếp bảng
    let sortStates = {};
    let currentSortColumn = null;
    let currentSortDirection = 0;
    
    $('.sortable').on('click', function() {
        const column = $(this).data('column');
        const $icon = $(this).find('i');
        
        if (!sortStates[column]) {
            sortStates[column] = 0;
        }
        
        sortStates[column] = (sortStates[column] + 1) % 3;
        currentSortColumn = column;
        currentSortDirection = sortStates[column];
        
        // Reset icon các cột khác
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
        
        // Sắp xếp allRows
        sortAllRows();
        
        // Re-render
        currentPage = 1;
        renderTable();
    });
    
    // Hàm sắp xếp allRows
    function sortAllRows() {
        if (currentSortDirection === 0) {
            // Default - sắp xếp theo report_count giảm dần
            allRows.sort(function(a, b) {
                return b.reportCount - a.reportCount;
            });
        } else {
            allRows.sort(function(a, b) {
                let aValue, bValue;
                
                if (currentSortColumn === 'job_id') {
                    aValue = a.jobId;
                    bValue = b.jobId;
                } else if (currentSortColumn === 'job_title') {
                    aValue = a.jobTitle;
                    bValue = b.jobTitle;
                } else if (currentSortColumn === 'job_owner') {
                    aValue = a.jobOwner;
                    bValue = b.jobOwner;
                } else if (currentSortColumn === 'report_count') {
                    aValue = a.reportCount;
                    bValue = b.reportCount;
                } else if (currentSortColumn === 'status') {
                    aValue = a.status || 'active';
                    bValue = b.status || 'active';
                }
                
                if (currentSortDirection === 1) {
                    return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
                } else {
                    return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
                }
            });
        }
        
        // Re-filter sau khi sort
        const searchValue = $('#search-input').val();
        filterRows(searchValue);
    }

    // ===== CHECKBOX VÀ XÓA HÀNG LOẠT =====
    
    // Chọn tất cả checkbox
    $('#select-all-checkbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        
        $('.report-checkbox').each(function() {
            const jobId = parseInt($(this).val());
            $(this).prop('checked', isChecked);
            
            if (isChecked) {
                if (!selectedJobIds.includes(jobId)) {
                    selectedJobIds.push(jobId);
                }
            } else {
                const index = selectedJobIds.indexOf(jobId);
                if (index > -1) {
                    selectedJobIds.splice(index, 1);
                }
            }
        });
        
        updateSelectedCount();
    });
    
    // Khi click vào checkbox riêng lẻ
    $(document).on('change', '.report-checkbox', function() {
        const jobId = parseInt($(this).val());
        const isChecked = $(this).is(':checked');
        
        if (isChecked) {
            if (!selectedJobIds.includes(jobId)) {
                selectedJobIds.push(jobId);
            }
        } else {
            const index = selectedJobIds.indexOf(jobId);
            if (index > -1) {
                selectedJobIds.splice(index, 1);
            }
        }
        
        updateSelectedCount();
        updateSelectAllCheckbox();
    });
    
    // Cập nhật trạng thái checkbox "Chọn tất cả"
    function updateSelectAllCheckbox() {
        const totalVisible = $('.report-checkbox').length;
        const totalChecked = $('.report-checkbox:checked').length;
        $('#select-all-checkbox').prop('checked', totalVisible > 0 && totalVisible === totalChecked);
    }
    
    // Cập nhật số lượng đã chọn
    function updateSelectedCount() {
        const count = selectedJobIds.length;
        $('#selected-count').text(count);
        
        if (count > 0) {
            // Kiểm tra trạng thái của các job đã chọn
            let hasLocked = false;
            let hasUnlocked = false;
            
            selectedJobIds.forEach(jobId => {
                const row = allRows.find(r => r.jobId == jobId);
                if (row) {
                    if (row.status == 2) {
                        hasLocked = true;
                    } else {
                        hasUnlocked = true;
                    }
                }
            });
            
            // Nếu tất cả đều đã khóa → disable nút Khóa, enable nút Mở khóa
            // Nếu tất cả đều chưa khóa → enable nút Khóa, disable nút Mở khóa
            // Nếu có cả 2 loại → enable cả 2 nút
            $('#bulk-lock-btn').prop('disabled', !hasUnlocked);
            $('#bulk-unlock-btn').prop('disabled', !hasLocked);
        } else {
            $('#bulk-lock-btn').prop('disabled', true);
            $('#bulk-unlock-btn').prop('disabled', true);
        }
    }
    
    // Khôi phục trạng thái checkbox sau khi render
    function restoreCheckboxStates() {
        $('.report-checkbox').each(function() {
            const jobId = $(this).val();
            if (selectedJobIds.includes(parseInt(jobId))) {
                $(this).prop('checked', true);
            }
        });
        
        updateSelectAllCheckbox();
    }
    
    // Khóa hàng loạt
    $('#bulk-lock-btn').on('click', async function() {
        if (selectedJobIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa chọn job!',
                text: 'Vui lòng chọn ít nhất một job để khóa báo cáo!'
            });
            return;
        }
        
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận khóa',
            html: `Bạn có chắc chắn muốn khóa báo cáo của <b>${selectedJobIds.length}</b> job đã chọn?`,
            showCancelButton: true,
            confirmButtonColor: '#f6c23e',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Khóa',
            cancelButtonText: 'Hủy'
        });
        
        if (!result.isConfirmed) {
            return;
        }
        
        performBulkStatusUpdate(2);
    });

    // Mở khóa hàng loạt
    $('#bulk-unlock-btn').on('click', async function() {
        if (selectedJobIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa chọn job!',
                text: 'Vui lòng chọn ít nhất một job để mở khóa báo cáo!'
            });
            return;
        }
        
        const result = await Swal.fire({
            icon: 'info',
            title: 'Xác nhận mở khóa',
            html: `Bạn có chắc chắn muốn mở khóa báo cáo của <b>${selectedJobIds.length}</b> job đã chọn?`,
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Mở khóa',
            cancelButtonText: 'Hủy'
        });
        
        if (!result.isConfirmed) {
            return;
        }
        
        performBulkStatusUpdate(1);
    });

    // Hàm thực hiện cập nhật trạng thái hàng loạt
    function performBulkStatusUpdate(newStatus) {
        const actionText = newStatus === 2 ? 'khóa' : 'mở khóa';
        const url = newStatus === 2 ? '/admin/job-reports/bulk-lock' : '/admin/job-reports/bulk-unlock';
        
        Swal.fire({
            title: 'Đang xử lý...',
            text: `Đang ${actionText} báo cáo`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                job_ids: selectedJobIds
            },
            success: function(response) {
                if (response.success) {
                    // Cập nhật status trong allRows và rebuild HTML
                    selectedJobIds.forEach(jobId => {
                        const rowIndex = allRows.findIndex(row => row.jobId == jobId);
                        if (rowIndex !== -1) {
                            allRows[rowIndex].status = newStatus;
                            
                            // Rebuild HTML cho row này
                            const $row = $(`tr[data-job-id="${jobId}"]`).clone();
                            $row.attr('data-status', newStatus);
                            
                            // Bỏ tích checkbox
                            $row.find('.report-checkbox').prop('checked', false);
                            
                            // Cập nhật badge trạng thái
                            const $statusCell = $row.find('td:eq(5)');
                            if (newStatus === 2) {
                                $statusCell.html('<span class="badge bg-secondary"><i class="fas fa-lock"></i> Đã khóa</span>');
                            } else if (newStatus === 0) {
                                $statusCell.html('<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Từ chối</span>');
                            } else {
                                $statusCell.html('<span class="badge bg-success"><i class="fas fa-clock"></i> Chờ xử lý</span>');
                            }
                            
                            allRows[rowIndex].html = $row[0].outerHTML;
                        }
                    });
                    
                    // Re-render table
                    const searchValue = $('#search-input').val();
                    filterRows(searchValue);
                    
                    // Reset checkboxes
                    selectedJobIds = [];
                    $('#select-all-checkbox').prop('checked', false);
                    updateSelectedCount();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi ' + actionText + ' báo cáo!'
                });
                console.error('Error:', xhr);
            }
        });
    }
    
    // Xóa các báo cáo đã chọn
    $('#delete-selected-btn').on('click', async function() {
        if (selectedJobIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa chọn job!',
                text: 'Vui lòng chọn ít nhất một job để xóa báo cáo!'
            });
            return;
        }
        
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa',
            html: `Bạn có chắc chắn muốn xóa <b>TẤT CẢ</b> báo cáo của <b>${selectedJobIds.length}</b> job đã chọn?`,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa tất cả',
            cancelButtonText: 'Hủy'
        });
        
        if (!result.isConfirmed) {
            return;
        }
        
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xóa...');
        
        // Xóa từng job report
        let deletedCount = 0;
        let errorCount = 0;
        const idsToDelete = [...selectedJobIds];
        
        idsToDelete.forEach((jobId) => {
            $.ajax({
                url: `/admin/job-reports/${jobId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    deletedCount++;
                    
                    // Xóa khỏi allRows
                    const rowIndex = allRows.findIndex(row => row.jobId == jobId);
                    if (rowIndex !== -1) {
                        allRows.splice(rowIndex, 1);
                    }
                    
                    // Xóa khỏi selectedJobIds
                    const selectedIndex = selectedJobIds.indexOf(jobId);
                    if (selectedIndex > -1) {
                        selectedJobIds.splice(selectedIndex, 1);
                    }
                    
                    if (deletedCount + errorCount === idsToDelete.length) {
                        finishBulkDelete();
                    }
                },
                error: function(xhr) {
                    errorCount++;
                    console.error('Error deleting job report ' + jobId, xhr);
                    
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
                    html: `Đã xóa <b>${deletedCount}/${idsToDelete.length}</b> báo cáo.<br>${errorCount} báo cáo không thể xóa.`
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: `Đã xóa thành công báo cáo của ${deletedCount} job!`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            
            // Re-render table
            const searchValue = $('#search-input').val();
            filterRows(searchValue);
            
            // Reset checkboxes
            $('#select-all-checkbox').prop('checked', false);
            updateSelectedCount();
        }
    });
});
</script>
@endpush
