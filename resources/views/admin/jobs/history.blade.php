@extends('admin.layouts.app')

@section('title', 'Lịch sử duyệt bài đăng')

@push('styles')
<style>
    .badge-approved { background-color: #28a745; }
    .badge-rejected { background-color: #dc3545; }
</style>
@endpush

@section('content')
<h1 class="h3 mb-4"><i class="fas fa-history me-2"></i>Lịch sử duyệt bài đăng</h1>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng số bài đăng</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jobs->count() }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-list fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đã duyệt</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jobs->where('status', 'open')->count() }}</div>
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
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Từ chối</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jobs->where('status', 'cancelled')->count() }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Chờ duyệt</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ \App\Models\Job::where('status', 'pending')->count() }}</div>
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
        <h6 class="m-0 font-weight-bold text-primary">Danh sách bài đăng đã xử lý</h6>
        <a href="{{ route('admin.jobs.pending') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Quay lại trang duyệt
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="history-table">
                <thead class="table-light">
                    <tr>
                        <th>Tiêu đề công việc</th>
                        <th>Người đăng</th>
                        <th>Ngân sách</th>
                        <th>Trạng thái</th>
                        <th>Ngày xử lý</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td>
                            <strong>{{ $job->title }}</strong>
                        </td>
                        <td>
                            <div>{{ $job->account->profile->fullname ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $job->account->email }}</small>
                        </td>
                        <td>
                            <span class="badge bg-info">${{ number_format($job->budget) }}</span>
                        </td>
                        <td>
                            @if($job->status === 'open')
                                <span class="badge badge-approved">
                                    <i class="fas fa-check me-1"></i> Đã duyệt
                                </span>
                            @elseif($job->status === 'cancelled')
                                <span class="badge badge-rejected">
                                    <i class="fas fa-times me-1"></i> Từ chối
                                </span>
                            @endif
                        </td>
                        <td>{{ $job->updated_at->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info view-job-btn" 
                                    data-job-id="{{ $job->job_id }}"
                                    title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Chưa có bài đăng nào được xử lý</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal chi tiết công việc -->
<div class="modal fade" id="jobDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết công việc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="jobDetailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    const table = $('#history-table').DataTable({
        processing: false,
        serverSide: false,
        ordering: true,
        order: [[4, 'desc']], // Sort by date
        paging: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        searching: true,
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

    // View job details
    $('.view-job-btn').on('click', function() {
        const jobId = $(this).data('job-id');
        
        $.ajax({
            url: `/admin/jobs/${jobId}/details`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const job = response.job;
                    $('#jobDetailContent').html(`
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card border-primary h-100">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Thông tin công việc</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="text-muted small mb-1">Tiêu đề:</label>
                                            <div class="fw-bold">${job.title}</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small mb-1">Mô tả:</label>
                                            <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                                ${job.description}
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label class="text-muted small mb-1">Ngân sách:</label>
                                                <div class="fw-bold text-success">${job.budget}</div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="text-muted small mb-1">Loại thanh toán:</label>
                                                <div><span class="badge bg-info">${job.payment_type}</span></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label class="text-muted small mb-1">Deadline:</label>
                                                <div class="fw-bold">${job.deadline}</div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="text-muted small mb-1">Danh mục:</label>
                                                <div><span class="badge bg-secondary">${job.category_name}</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-info h-100">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin người đăng</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="text-muted small mb-1">Tên:</label>
                                            <div class="fw-bold">${job.client_name}</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small mb-1">Email:</label>
                                            <div><a href="mailto:${job.client_email}" class="text-decoration-none">${job.client_email}</a></div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small mb-1">Ngày đăng:</label>
                                            <div class="fw-bold">${job.created_at}</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small mb-1">Trạng thái:</label>
                                            <div>${job.status_badge}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                    $('#jobDetailModal').modal('show');
                }
            },
            error: function() {
                Swal.fire('Lỗi', 'Không thể tải thông tin công việc', 'error');
            }
        });
    });
});
</script>
@endpush
