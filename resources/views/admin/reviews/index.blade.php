@extends('admin.layouts.app')

@section('title', 'Quản lý đánh giá')

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
    
    #reviews-table th,
    #reviews-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
    }
    
    .badge {
        font-size: 0.875rem;
        padding: 0.35rem 0.65rem;
    }
    
    .rating-stars {
        color: #ffc107;
    }
    
    .comment-preview {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .rating-box {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .rating-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        background-color: #ffffff !important;
    }
    
    .stat-filter-card.active {
        border: 2px solid #4e73df;
        box-shadow: 0 0 15px rgba(78, 115, 223, 0.4) !important;
    }
    
    .rating-filter-box.active {
        border: 2px solid #f6c23e !important;
        box-shadow: 0 0 15px rgba(246, 194, 62, 0.5) !important;
        background-color: #fff !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-star"></i> Quản lý đánh giá
        </h1>
    </div>

    <!-- Thống kê -->
    <div class="row g-4 mb-4">
        <!-- Tổng đánh giá -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-primary shadow h-100 py-2 stat-filter-card" data-filter="all" style="cursor: pointer;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng đánh giá
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalReviews }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đánh giá hôm nay -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-success shadow h-100 py-2 stat-filter-card" data-filter="today" style="cursor: pointer;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Đánh giá hôm nay
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $todayReviews }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đánh giá tuần này -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-info shadow h-100 py-2 stat-filter-card" data-filter="week" style="cursor: pointer;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Đánh giá tuần này
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $weekReviews }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đánh giá tháng này -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-left-warning shadow h-100 py-2 stat-filter-card" data-filter="month" style="cursor: pointer;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Đánh giá tháng này
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $monthReviews }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê theo Rating -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3" style="cursor: pointer;" id="ratingToggleHeader">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Phân loại theo đánh giá
                        <i class="fas fa-chevron-up ms-2" id="ratingToggleIcon"></i>
                    </h6>
                </div>
                <div class="card-body" id="ratingContent">
                    <div class="row">
                        <!-- 5 sao -->
                        <div class="col-md-2 col-6 mb-3">
                            <div class="border rounded p-3 h-100 text-center bg-light rating-box rating-filter-box" data-rating="5">
                                <div class="rating-stars text-warning mb-2">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <h4 class="font-weight-bold text-success mb-1">{{ $fiveStarReviews }}</h4>
                                <small class="text-muted">đánh giá</small>
                            </div>
                        </div>

                        <!-- 4 sao -->
                        <div class="col-md-2 col-6 mb-3">
                            <div class="border rounded p-3 h-100 text-center bg-light rating-box rating-filter-box" data-rating="4">
                                <div class="rating-stars text-warning mb-2">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <h4 class="font-weight-bold text-info mb-1">{{ $fourStarReviews }}</h4>
                                <small class="text-muted">đánh giá</small>
                            </div>
                        </div>

                        <!-- 3 sao -->
                        <div class="col-md-2 col-6 mb-3">
                            <div class="border rounded p-3 h-100 text-center bg-light rating-box rating-filter-box" data-rating="3">
                                <div class="rating-stars text-warning mb-2">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <h4 class="font-weight-bold text-primary mb-1">{{ $threeStarReviews }}</h4>
                                <small class="text-muted">đánh giá</small>
                            </div>
                        </div>

                        <!-- 2 sao -->
                        <div class="col-md-2 col-6 mb-3">
                            <div class="border rounded p-3 h-100 text-center bg-light rating-box rating-filter-box" data-rating="2">
                                <div class="rating-stars text-warning mb-2">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <h4 class="font-weight-bold text-warning mb-1">{{ $twoStarReviews }}</h4>
                                <small class="text-muted">đánh giá</small>
                            </div>
                        </div>

                        <!-- 1 sao -->
                        <div class="col-md-2 col-6 mb-3">
                            <div class="border rounded p-3 h-100 text-center bg-light rating-box rating-filter-box" data-rating="1">
                                <div class="rating-stars text-warning mb-2">
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <h4 class="font-weight-bold text-danger mb-1">{{ $oneStarReviews }}</h4>
                                <small class="text-muted">đánh giá</small>
                            </div>
                        </div>

                        <!-- Tổng -->
                        <div class="col-md-2 col-6 mb-3">
                            <div class="border rounded p-3 h-100 text-center bg-light rating-box rating-filter-box" data-rating="all">
                                <div class="mb-2">
                                    <i class="fas fa-list fa-2x text-gray-400"></i>
                                </div>
                                <h4 class="font-weight-bold text-dark mb-1">{{ $totalReviews }}</h4>
                                <small class="text-muted">tổng cộng</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng đánh giá -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách đánh giá</h6>
            <button class="btn btn-sm btn-danger" id="deleteSelectedBtn" style="display: none;">
                <i class="fas fa-trash"></i> Xóa đã chọn
            </button>
        </div>
        <div class="card-body">
            <!-- Bộ lọc -->
            <div class="filter-section mb-3 p-2 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-2" style="cursor: pointer;" id="filterToggleHeader">
                    <small class="mb-0 text-primary font-weight-bold">
                        <i class="fas fa-filter"></i> Bộ lọc
                        <i class="fas fa-chevron-down ms-1" id="filterToggleIcon" style="font-size: 0.75rem;"></i>
                    </small>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="clearFiltersBtn" style="font-size: 0.75rem;">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </button>
                </div>
                <!-- Input ẩn để lưu rating filter (được set khi click vào rating box) -->
                <input type="hidden" id="filterRating" value="">
                
                <div class="row" id="filterContent" style="display: none;">
                    <!-- Tìm kiếm người đánh giá -->
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.75rem; font-weight: 600;">Người đánh giá</label>
                        <input type="text" class="form-control form-control-sm" id="filterReviewer" placeholder="Tìm kiếm..." style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                    </div>

                    <!-- Tìm kiếm người được đánh giá -->
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.75rem; font-weight: 600;">Người được đánh giá</label>
                        <input type="text" class="form-control form-control-sm" id="filterReviewee" placeholder="Tìm kiếm..." style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                    </div>

                    <!-- Tìm kiếm nhận xét -->
                    <div class="col-md-4 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.75rem; font-weight: 600;">Nhận xét</label>
                        <input type="text" class="form-control form-control-sm" id="filterComment" placeholder="Tìm kiếm..." style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="reviews-table" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;" class="text-center">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 20%;">Người đánh giá</th>
                            <th style="width: 20%;">Người được đánh giá</th>
                            <th style="width: 12%;" class="text-center">Đánh giá</th>
                            <th style="width: 25%;">Nhận xét</th>
                            <th style="width: 10%;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                        <tr data-id="{{ $review->review_id }}" data-created-at="{{ $review->created_at }}">
                            <td class="text-center">
                                <input type="checkbox" class="review-checkbox" value="{{ $review->review_id }}">
                            </td>
                            <td>{{ $review->review_id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($review->reviewer)
                                        <div>
                                            <strong>{{ $review->reviewer->fullname ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $review->reviewer->email ?? ($review->reviewer->account ? $review->reviewer->account->email : 'N/A') }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted">ID: {{ $review->reviewer_id }} (Không tìm thấy)</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($review->reviewee)
                                        <div>
                                            <strong>{{ $review->reviewee->fullname ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $review->reviewee->email ?? ($review->reviewee->account ? $review->reviewee->account->email : 'N/A') }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted">ID: {{ $review->reviewee_id }} (Không tìm thấy)</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="rating-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->rating)
                                            <i class="fas fa-star"></i>
                                        @else
                                            <i class="far fa-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <small class="text-muted">({{ $review->rating }}/5)</small>
                            </td>
                            <td>
                                <div class="comment-preview" title="{{ $review->comment }}">
                                    {{ $review->comment ?? 'Không có nhận xét' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info btn-view" 
                                        data-id="{{ $review->review_id }}"
                                        data-reviewer="{{ $review->reviewer->fullname ?? 'N/A' }}"
                                        data-reviewer-email="{{ $review->reviewer->email ?? ($review->reviewer->account ? $review->reviewer->account->email : 'N/A') }}"
                                        data-reviewee="{{ $review->reviewee->fullname ?? 'N/A' }}"
                                        data-reviewee-email="{{ $review->reviewee->email ?? ($review->reviewee->account ? $review->reviewee->account->email : 'N/A') }}"
                                        data-rating="{{ $review->rating }}"
                                        data-comment="{{ $review->comment ?? 'Không có nhận xét' }}"
                                        data-created="{{ $review->created_at }}"
                                        title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete" 
                                        data-id="{{ $review->review_id }}"
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

<!-- Modal Xem chi tiết -->
<div class="modal fade" id="viewReviewModal" tabindex="-1" aria-labelledby="viewReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewReviewModalLabel">
                    <i class="fas fa-info-circle"></i> Chi tiết đánh giá
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ID đánh giá:</label>
                            <p id="view_review_id" class="form-control-plaintext"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Người đánh giá:</label>
                            <p id="view_reviewer" class="form-control-plaintext"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email người đánh giá:</label>
                            <p id="view_reviewer_email" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Đánh giá:</label>
                            <p id="view_rating" class="form-control-plaintext"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Người được đánh giá:</label>
                            <p id="view_reviewee" class="form-control-plaintext"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email người được đánh giá:</label>
                            <p id="view_reviewee_email" class="form-control-plaintext"></p>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nhận xét:</label>
                    <p id="view_comment" class="form-control-plaintext border p-3 bg-light rounded"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Thời gian tạo:</label>
                    <p id="view_created_at" class="form-control-plaintext"></p>
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

<!-- Modal Xóa đánh giá -->
<div class="modal fade" id="deleteReviewModal" tabindex="-1" aria-labelledby="deleteReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteReviewModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Xác nhận xóa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa đánh giá này?</p>
                <p class="text-danger"><i class="fas fa-info-circle"></i> Hành động này không thể hoàn tác!</p>
                <input type="hidden" id="delete_review_id">
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

<!-- Modal Xóa nhiều đánh giá -->
<div class="modal fade" id="deleteMultipleModal" tabindex="-1" aria-labelledby="deleteMultipleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMultipleModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Xác nhận xóa nhiều
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa <strong id="delete_count"></strong> đánh giá đã chọn?</p>
                <p class="text-danger"><i class="fas fa-info-circle"></i> Hành động này không thể hoàn tác!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteMultipleBtn">
                    <i class="fas fa-trash"></i> Xóa tất cả
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

    // Khởi tạo DataTable
    const table = $('#reviews-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
        },
        pageLength: 10,
        order: [[1, 'desc']]
    });

    // ===== BỘ LỌC =====
    // Biến lưu filter thời gian hiện tại
    let currentTimeFilter = 'all';
    
    // Highlight card "Tổng đánh giá" mặc định khi load trang
    $('.stat-filter-card[data-filter="all"]').addClass('active');
    
    // Không highlight rating box nào khi load trang
    // (chỉ highlight khi user click vào rating box)

    // Click vào card thống kê để lọc
    $('.stat-filter-card').on('click', function() {
        const filterType = $(this).data('filter');
        const isActive = $(this).hasClass('active');
        
        // Nếu click vào card đang active (lần 2) -> Tắt và trả về "Tổng đánh giá"
        if (isActive && filterType !== 'all') {
            // Reset về "Tổng đánh giá"
            currentTimeFilter = 'all';
            $('.stat-filter-card').removeClass('active');
            $('.stat-filter-card[data-filter="all"]').addClass('active');
            
            // Redraw table
            table.draw();
            return;
        }
        
        // Nếu click vào card khác -> Highlight và lọc
        currentTimeFilter = filterType;
        
        // Highlight card được chọn
        $('.stat-filter-card').removeClass('active');
        $(this).addClass('active');
        
        // Reset rating filter khi click vào card thống kê thời gian
        $('#filterRating').val('');
        
        // Xóa TẤT CẢ highlight của rating boxes (không highlight cái nào)
        $('.rating-filter-box').removeClass('active');
        
        // Redraw table với filter mới
        table.draw();
    });

    // Click vào rating box để lọc theo rating
    $('.rating-filter-box').on('click', function() {
        const rating = $(this).data('rating');
        const isActive = $(this).hasClass('active');
        
        // Nếu click vào ô đang active (lần 2) -> Tắt và trả về mặc định
        if (isActive) {
            // Xóa tất cả highlight
            $('.rating-filter-box').removeClass('active');
            
            // Reset rating filter
            $('#filterRating').val('');
            
            // Redraw table (hiển thị tất cả)
            table.draw();
            return;
        }
        
        // Nếu click vào ô khác -> Highlight và lọc
        $('.rating-filter-box').removeClass('active');
        $(this).addClass('active');
        
        // Set giá trị cho rating filter
        if (rating === 'all') {
            $('#filterRating').val('');
        } else {
            $('#filterRating').val(rating);
        }
        
        // Reset time filter khi click vào rating box
        currentTimeFilter = 'all';
        $('.stat-filter-card').removeClass('active');
        $('.stat-filter-card[data-filter="all"]').addClass('active');
        
        // Redraw table với filter mới
        table.draw();
    });

    // Toggle hiển thị/ẩn bộ lọc
    $('#filterToggleHeader').on('click', function(e) {
        // Không toggle nếu click vào nút "Xóa bộ lọc"
        if ($(e.target).closest('#clearFiltersBtn').length) {
            return;
        }
        
        $('#filterContent').slideToggle(300);
        const icon = $('#filterToggleIcon');
        if (icon.hasClass('fa-chevron-up')) {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });

    // Toggle hiển thị/ẩn card Phân loại theo đánh giá
    $('#ratingToggleHeader').on('click', function() {
        $('#ratingContent').slideToggle(300);
        const icon = $('#ratingToggleIcon');
        if (icon.hasClass('fa-chevron-up')) {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });

    // Custom search function cho DataTables
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            // data[2] = Người đánh giá
            // data[3] = Người được đánh giá
            // data[4] = Rating (HTML)
            // data[5] = Nhận xét
            
            const reviewerFilter = $('#filterReviewer').val().toLowerCase();
            const revieweeFilter = $('#filterReviewee').val().toLowerCase();
            const ratingFilter = $('#filterRating').val();
            const commentFilter = $('#filterComment').val().toLowerCase();
            
            const reviewerText = data[2] ? data[2].toLowerCase() : '';
            const revieweeText = data[3] ? data[3].toLowerCase() : '';
            const commentText = data[5] ? data[5].toLowerCase() : '';
            
            // Lọc người đánh giá
            if (reviewerFilter && !reviewerText.includes(reviewerFilter)) {
                return false;
            }
            
            // Lọc người được đánh giá
            if (revieweeFilter && !revieweeText.includes(revieweeFilter)) {
                return false;
            }
            
            // Lọc rating
            if (ratingFilter) {
                const ratingHtml = data[4];
                const ratingMatch = ratingHtml.match(/\((\d)\/5\)/);
                if (!ratingMatch || ratingMatch[1] !== ratingFilter) {
                    return false;
                }
            }
            
            // Lọc nhận xét
            if (commentFilter && !commentText.includes(commentFilter)) {
                return false;
            }
            
            // Lọc theo thời gian (từ card thống kê)
            if (currentTimeFilter && currentTimeFilter !== 'all') {
                const row = table.row(dataIndex).node();
                const createdAt = $(row).data('created-at');
                
                if (createdAt) {
                    const reviewDate = new Date(createdAt);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (currentTimeFilter === 'today') {
                        const reviewDateOnly = new Date(reviewDate);
                        reviewDateOnly.setHours(0, 0, 0, 0);
                        if (reviewDateOnly.getTime() !== today.getTime()) {
                            return false;
                        }
                    } else if (currentTimeFilter === 'week') {
                        // Lấy ngày đầu tuần (Thứ Hai)
                        const startOfWeek = new Date(today);
                        const day = startOfWeek.getDay();
                        const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1); // Điều chỉnh nếu là Chủ Nhật
                        startOfWeek.setDate(diff);
                        startOfWeek.setHours(0, 0, 0, 0);
                        
                        // Lấy ngày cuối tuần (Chủ Nhật)
                        const endOfWeek = new Date(startOfWeek);
                        endOfWeek.setDate(startOfWeek.getDate() + 6);
                        endOfWeek.setHours(23, 59, 59, 999);
                        
                        if (reviewDate < startOfWeek || reviewDate > endOfWeek) {
                            return false;
                        }
                    } else if (currentTimeFilter === 'month') {
                        // Lấy ngày đầu tháng
                        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        startOfMonth.setHours(0, 0, 0, 0);
                        
                        // Lấy ngày cuối tháng
                        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        endOfMonth.setHours(23, 59, 59, 999);
                        
                        if (reviewDate < startOfMonth || reviewDate > endOfMonth) {
                            return false;
                        }
                    }
                }
            }
            
            return true;
        }
    );

    // Xử lý sự kiện thay đổi bộ lọc
    $('#filterReviewer, #filterReviewee, #filterRating, #filterComment').on('keyup change', function() {
        table.draw();
    });

    // Xóa bộ lọc
    $('#clearFiltersBtn').on('click', function(e) {
        e.stopPropagation(); // Ngăn không cho toggle bộ lọc
        $('#filterReviewer').val('');
        $('#filterReviewee').val('');
        $('#filterRating').val('');
        $('#filterComment').val('');
        
        // Reset filter thời gian và highlight
        currentTimeFilter = 'all';
        $('.stat-filter-card').removeClass('active');
        $('.stat-filter-card[data-filter="all"]').addClass('active');
        
        // Xóa tất cả rating box highlight (không highlight cái nào)
        $('.rating-filter-box').removeClass('active');
        
        table.draw();
    });

    // Xử lý checkbox chọn tất cả
    $('#selectAll').on('change', function() {
        $('.review-checkbox').prop('checked', $(this).prop('checked'));
        updateDeleteButton();
    });

    // Xử lý checkbox từng item
    $(document).on('change', '.review-checkbox', function() {
        updateDeleteButton();
        
        // Cập nhật checkbox "Chọn tất cả"
        const totalCheckboxes = $('.review-checkbox').length;
        const checkedCheckboxes = $('.review-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Cập nhật hiển thị nút xóa nhiều
    function updateDeleteButton() {
        const checkedCount = $('.review-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#deleteSelectedBtn').show().text(`Xóa đã chọn (${checkedCount})`);
        } else {
            $('#deleteSelectedBtn').hide();
        }
    }

    // Xử lý nút xem chi tiết - Load ngay lập tức từ data attributes
    $(document).on('click', '.btn-view', function() {
        const btn = $(this);
        const reviewId = btn.data('id');
        const reviewer = btn.data('reviewer');
        const reviewerEmail = btn.data('reviewer-email');
        const reviewee = btn.data('reviewee');
        const revieweeEmail = btn.data('reviewee-email');
        const rating = btn.data('rating');
        const comment = btn.data('comment');
        const createdAt = btn.data('created');
        
        // Điền dữ liệu vào modal
        $('#view_review_id').text(reviewId);
        $('#view_reviewer').text(reviewer);
        $('#view_reviewer_email').text(reviewerEmail);
        $('#view_reviewee').text(reviewee);
        $('#view_reviewee_email').text(revieweeEmail);
        $('#view_comment').text(comment);
        $('#view_created_at').text(new Date(createdAt).toLocaleString('vi-VN'));
        
        // Hiển thị rating
        let ratingHtml = '<span class="rating-stars">';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                ratingHtml += '<i class="fas fa-star"></i> ';
            } else {
                ratingHtml += '<i class="far fa-star"></i> ';
            }
        }
        ratingHtml += `</span> (${rating}/5)`;
        $('#view_rating').html(ratingHtml);
        
        // Hiển thị modal ngay lập tức
        $('#viewReviewModal').modal('show');
    });

    // Xử lý nút xóa đơn
    $(document).on('click', '.btn-delete', function() {
        const reviewId = $(this).data('id');
        $('#delete_review_id').val(reviewId);
        $('#deleteReviewModal').modal('show');
    });

    // Xác nhận xóa đơn
    $('#confirmDeleteBtn').on('click', function() {
        const reviewId = $('#delete_review_id').val();
        const btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: `/admin/reviews/${reviewId}`,
            method: 'POST',
            data: {
                _method: 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteReviewModal').modal('hide');
                    
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

    // Xử lý xóa nhiều
    $('#deleteSelectedBtn').on('click', function() {
        const checkedCount = $('.review-checkbox:checked').length;
        $('#delete_count').text(checkedCount);
        $('#deleteMultipleModal').modal('show');
    });

    // Xác nhận xóa nhiều
    $('#confirmDeleteMultipleBtn').on('click', function() {
        const selectedIds = $('.review-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: '{{ route("admin.reviews.destroy-multiple") }}',
            method: 'POST',
            data: {
                ids: selectedIds
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteMultipleModal').modal('hide');
                    
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
                btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Xóa tất cả');
            }
        });
    });
});
</script>
@endpush
