@extends('admin.layouts.app')

@section('title', 'Dashboard - Thống kê hệ thống')

@push('styles')
<style>
    /* Làm chữ legend rõ hơn */
    canvas {
        filter: contrast(1.1);
    }
    
    /* Animation cho cards */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Animation cho cards */
    .stat-card {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }
    .stat-card:nth-child(5) { animation-delay: 0.5s; }
    .stat-card:nth-child(6) { animation-delay: 0.6s; }
    .stat-card:nth-child(7) { animation-delay: 0.7s; }
    .stat-card:nth-child(8) { animation-delay: 0.8s; }

    .chart-container {
        animation: slideInRight 0.8s ease-out forwards;
        opacity: 0;
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }

    .chart-container:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .chart-container:nth-child(1) { animation-delay: 0.2s; }
    .chart-container:nth-child(2) { animation-delay: 0.4s; }
    .chart-container:nth-child(3) { animation-delay: 0.6s; }

    /* Responsive */
    @media (max-width: 768px) {
        .stat-card {
            margin-bottom: 1rem;
        }
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 10px;
    }

    .section-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(to right, #4e73df, #1cc88a);
        border-radius: 2px;
    }

    .table-container {
        animation: fadeInUp 0.8s ease-out forwards;
        opacity: 0;
        animation-delay: 0.5s;
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .badge-role {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Đảm bảo các row có chiều cao đồng đều */
    .row.g-4 {
        display: flex;
        flex-wrap: wrap;
    }
    
    .row.g-4 > [class*='col-'] {
        display: flex;
        flex-direction: column;
    }


    /* Loading animation */
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">
            <i class="fas fa-chart-line me-2"></i>Dashboard - Thống kê hệ thống
        </h1>
        <div class="text-muted">
            <i class="far fa-calendar-alt me-2"></i>{{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row g-4 mb-4">
        <!-- Tổng số người dùng -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng tài khoản</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalUsers) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tài khoản kích hoạt -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kích hoạt</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($activeUsers) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tài khoản bị khóa -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bị khóa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($lockedUsers) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chưa xác minh email -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Chưa xác minh</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($unverifiedUsers) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Stats -->
    <div class="row g-4 mb-4">
        <!-- Tổng số job được đăng -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng job đăng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalJobs) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job đang mở -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Job đang mở</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($openJobs) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job đã thanh toán -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Job đã thanh toán</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($paidJobs) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-check-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doanh thu -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Doanh thu</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($systemRevenue, 0, ',', '.') }}₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Biểu đồ tròn 1: Thống kê theo vai trò -->
        <div class="col-xl-4 col-lg-6">
            <div class="chart-container">
                <h5 class="section-title">Thống kê theo vai trò</h5>
                <div style="height: 400px; position: relative;">
                    <canvas id="userRoleChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Biểu đồ tròn 2: Job theo trạng thái -->
        <div class="col-xl-4 col-lg-6">
            <div class="chart-container">
                <h5 class="section-title">Job theo trạng thái</h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="jobStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Biểu đồ tròn 3: Thanh toán theo trạng thái -->
        <div class="col-xl-4 col-lg-6">
            <div class="chart-container">
                <h5 class="section-title">Thanh toán theo trạng thái</h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="paymentStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Table: Users Breakdown -->
    <div class="row g-4">
        <div class="col-12">
            <div class="table-container">
                <h5 class="section-title">Chi tiết người dùng theo vai trò</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fas fa-hashtag me-2"></i>STT</th>
                                <th><i class="fas fa-user-tag me-2"></i>Vai trò</th>
                                <th><i class="fas fa-users me-2"></i>Số lượng</th>
                                <th><i class="fas fa-percentage me-2"></i>Tỷ lệ</th>
                                <th><i class="fas fa-chart-bar me-2"></i>Biểu đồ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usersByRole as $index => $role)
                            @php
                                $roleNameMap = [
                                    'Admin' => 'Admin',
                                    'Freelancer Basic' => 'Freelancer Basic',
                                    'Freelancer Plus' => 'Freelancer Plus',
                                    'Client' => 'Client',
                                    'Business' => 'Business',
                                    'Agency' => 'Agency',
                                    'Guest' => 'Guest',
                                    'Test-Guest' => 'Test-Guest',
                                    'Moderator' => 'Moderator'
                                ];
                                $displayName = $roleNameMap[$role->type_name] ?? $role->type_name;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge-role bg-primary text-white">
                                        {{ $displayName }}
                                    </span>
                                </td>
                                <td><strong>{{ number_format($role->total) }}</strong></td>
                                <td>{{ number_format(($role->total / $totalUsers) * 100, 2) }}%</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: {{ ($role->total / $totalUsers) * 100 }}%"
                                             aria-valuenow="{{ ($role->total / $totalUsers) * 100 }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold">Tổng cộng:</td>
                                <td colspan="3" class="fw-bold">{{ number_format($totalUsers) }} người dùng</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Job và Thanh toán theo trạng thái -->
    <div class="row g-4 mt-2">
        <!-- Bảng: Job theo trạng thái -->
        <div class="col-xl-6 col-lg-12">
            <div class="table-container">
                <h5 class="section-title">Job theo trạng thái</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fas fa-hashtag me-2"></i>STT</th>
                                <th><i class="fas fa-briefcase me-2"></i>Trạng thái</th>
                                <th><i class="fas fa-list-ol me-2"></i>Số lượng</th>
                                <th><i class="fas fa-percentage me-2"></i>Tỷ lệ</th>
                                <th><i class="fas fa-chart-bar me-2"></i>Biểu đồ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $jobStatusMap = [
                                    'open' => 'Đang mở',
                                    'in_progress' => 'Đang thực hiện',
                                    'completed' => 'Hoàn thành',
                                    'cancelled' => 'Đã hủy',
                                    'pending' => 'Chờ duyệt',
                                    'rejected' => 'Bị từ chối'
                                ];
                                $totalJobsForChart = $jobsByStatus->sum('total');
                            @endphp
                            @foreach($jobsByStatus as $index => $jobStatus)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge-role bg-info text-white">
                                        {{ $jobStatusMap[$jobStatus->status] ?? $jobStatus->status }}
                                    </span>
                                </td>
                                <td><strong>{{ number_format($jobStatus->total) }}</strong></td>
                                <td>{{ $totalJobsForChart > 0 ? number_format(($jobStatus->total / $totalJobsForChart) * 100, 2) : 0 }}%</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: {{ $totalJobsForChart > 0 ? ($jobStatus->total / $totalJobsForChart) * 100 : 0 }}%"
                                             aria-valuenow="{{ $totalJobsForChart > 0 ? ($jobStatus->total / $totalJobsForChart) * 100 : 0 }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold">Tổng cộng:</td>
                                <td colspan="3" class="fw-bold">{{ number_format($totalJobsForChart) }} job</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bảng: Thanh toán theo trạng thái -->
        <div class="col-xl-6 col-lg-12">
            <div class="table-container">
                <h5 class="section-title">Thanh toán theo trạng thái</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fas fa-hashtag me-2"></i>STT</th>
                                <th><i class="fas fa-credit-card me-2"></i>Trạng thái</th>
                                <th><i class="fas fa-list-ol me-2"></i>Số lượng</th>
                                <th><i class="fas fa-percentage me-2"></i>Tỷ lệ</th>
                                <th><i class="fas fa-chart-bar me-2"></i>Biểu đồ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $paymentStatusMap = [
                                    'PAID' => 'Đã thanh toán',
                                    'paid' => 'Đã thanh toán',
                                    'pending' => 'Chờ thanh toán',
                                    'success' => 'Thành công',
                                    'failed' => 'Thất bại',
                                    'PENDING' => 'Chờ thanh toán',
                                    'CANCELLED' => 'Đã hủy',
                                    'FAILED' => 'Thất bại'
                                ];
                                $totalPaymentsForChart = $paymentsByStatus->sum('total');
                            @endphp
                            @foreach($paymentsByStatus as $index => $paymentStatus)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge-role bg-success text-white">
                                        {{ $paymentStatusMap[$paymentStatus->status] ?? $paymentStatus->status }}
                                    </span>
                                </td>
                                <td><strong>{{ number_format($paymentStatus->total) }}</strong></td>
                                <td>{{ $totalPaymentsForChart > 0 ? number_format(($paymentStatus->total / $totalPaymentsForChart) * 100, 2) : 0 }}%</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: {{ $totalPaymentsForChart > 0 ? ($paymentStatus->total / $totalPaymentsForChart) * 100 : 0 }}%"
                                             aria-valuenow="{{ $totalPaymentsForChart > 0 ? ($paymentStatus->total / $totalPaymentsForChart) * 100 : 0 }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold">Tổng cộng:</td>
                                <td colspan="3" class="fw-bold">{{ number_format($totalPaymentsForChart) }} thanh toán</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Màu sắc đẹp cho biểu đồ (20 màu khác nhau)
    const colors = [
        '#667eea', // Admin - Xanh tím
        '#764ba2', // Freelancer Basic - Tím đậm
        '#f093fb', // Client - Hồng nhạt
        '#f5576c', // Business - Đỏ
        '#4facfe', // Freelancer Plus - Xanh dương nhạt
        '#00d4aa', // Agency - Xanh lá ngọc (thay đổi)
        '#43e97b', // Guest - Xanh lá
        '#feca57', // Test-Guest - Vàng (thay đổi)
        '#fa709a', // Moderator - Hồng đậm
        '#fee140', // Vàng chanh
        '#30cfd0', // Xanh ngọc nhạt
        '#ff6b6b', // Đỏ san hô
        '#48dbfb', // Xanh dương sáng
        '#ff9ff3', // Hồng tím
        '#54a0ff', // Xanh dương
        '#00d2d3', // Xanh ngọc
        '#1dd1a1', // Xanh lá mint
        '#feca57', // Vàng
        '#ee5a6f', // Đỏ hồng
        '#c44569'  // Đỏ tím
    ];

    // 1. Biểu đồ tròn: Thống kê theo vai trò
    const userRoleData = @json($usersByRole);
    console.log('User Role Data:', userRoleData);
    
    if (userRoleData && userRoleData.length > 0) {
        const roleMap = {
            'Admin': 'Admin',
            'Freelancer Basic': 'Freelancer Basic',
            'Freelancer Plus': 'Freelancer Plus',
            'Client': 'Client',
            'Business': 'Business',
            'Agency': 'Agency',
            'Guest': 'Guest',
            'Test-Guest': 'Test-Guest',
            'Moderator': 'Moderator'
        };
        
        const userRoleCtx = document.getElementById('userRoleChart').getContext('2d');
        new Chart(userRoleCtx, {
            type: 'doughnut',
            data: {
                labels: userRoleData.map(item => roleMap[item.type_name] || item.type_name),
                datasets: [{
                    data: userRoleData.map(item => item.total),
                    backgroundColor: colors.slice(0, userRoleData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 8,
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            boxWidth: 12,
                            boxHeight: 12,
                            color: '#2d3748'
                        },
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.index;
                            const chart = legend.chart;
                            const meta = chart.getDatasetMeta(0);
                            meta.data[index].hidden = !meta.data[index].hidden;
                            chart.update();
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(2);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
    } else {
        document.getElementById('userRoleChart').parentElement.innerHTML = '<p class="text-center text-muted py-5">Không có dữ liệu</p>';
    }

    // 2. Biểu đồ tròn: Job theo trạng thái
    const jobStatusData = @json($jobsByStatus);
    console.log('Job Status Data:', jobStatusData);
    
    if (jobStatusData && jobStatusData.length > 0) {
        const jobStatusCtx = document.getElementById('jobStatusChart').getContext('2d');
        new Chart(jobStatusCtx, {
            type: 'doughnut',
            data: {
                labels: jobStatusData.map(item => {
                    const statusMap = {
                        'open': 'Đang mở',
                        'in_progress': 'Đang thực hiện',
                        'completed': 'Hoàn thành',
                        'cancelled': 'Đã hủy',
                        'pending': 'Chờ duyệt',
                        'rejected': 'Bị từ chối'
                    };
                    return statusMap[item.status] || item.status;
                }),
                datasets: [{
                    data: jobStatusData.map(item => item.total),
                    backgroundColor: colors.slice(0, jobStatusData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#2d3748'
                        },
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.index;
                            const chart = legend.chart;
                            const meta = chart.getDatasetMeta(0);
                            meta.data[index].hidden = !meta.data[index].hidden;
                            chart.update();
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(2);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
    } else {
        document.getElementById('jobStatusChart').parentElement.innerHTML = '<p class="text-center text-muted py-5">Không có dữ liệu</p>';
    }

    // 3. Biểu đồ tròn: Thanh toán theo trạng thái
    const paymentStatusData = @json($paymentsByStatus);
    console.log('Payment Status Data:', paymentStatusData);
    
    if (paymentStatusData && paymentStatusData.length > 0) {
        const paymentStatusCtx = document.getElementById('paymentStatusChart').getContext('2d');
        new Chart(paymentStatusCtx, {
            type: 'doughnut',
            data: {
                labels: paymentStatusData.map(item => {
                    const statusMap = {
                        'PAID': 'Đã thanh toán',
                        'pending': 'Chờ thanh toán',
                        'success': 'Thành công',
                        'failed': 'Thất bại',
                        'PENDING': 'Chờ thanh toán',
                        'CANCELLED': 'Đã hủy',
                        'FAILED': 'Thất bại'
                    };
                    return statusMap[item.status] || item.status;
                }),
                datasets: [{
                    data: paymentStatusData.map(item => item.total),
                    backgroundColor: colors.slice(0, paymentStatusData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#2d3748'
                        },
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.index;
                            const chart = legend.chart;
                            const meta = chart.getDatasetMeta(0);
                            meta.data[index].hidden = !meta.data[index].hidden;
                            chart.update();
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(2);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
    } else {
        document.getElementById('paymentStatusChart').parentElement.innerHTML = '<p class="text-center text-muted py-5">Không có dữ liệu</p>';
    }
});
</script>
@endpush
