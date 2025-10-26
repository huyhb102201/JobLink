@extends('admin.layouts.app')

@section('title', 'Cài đặt hệ thống')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .info-card {
        border-left: 4px solid #4e73df;
    }
    .action-card {
        transition: transform 0.2s;
    }
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endpush

@section('content')
  <h1 class="h3 mb-4"><i class="fa-solid fa-gear me-2"></i>Cài đặt hệ thống</h1>

  <!-- System Information -->
  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="card shadow info-card">
        <div class="card-header">
          <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-server me-2"></i>Thông tin hệ thống</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td width="40%"><strong>PHP Version:</strong></td>
                <td>{{ $systemInfo['php_version'] }}</td>
              </tr>
              <tr>
                <td><strong>Laravel Version:</strong></td>
                <td>{{ $systemInfo['laravel_version'] }}</td>
              </tr>
              <tr>
                <td><strong>Server:</strong></td>
                <td>{{ $systemInfo['server_software'] }}</td>
              </tr>
              <tr>
                <td><strong>Database:</strong></td>
                <td>{{ $systemInfo['database_type'] }}</td>
              </tr>
              <tr>
                <td><strong>Cache Driver:</strong></td>
                <td>{{ $systemInfo['cache_driver'] }}</td>
              </tr>
              <tr>
                <td><strong>Queue Driver:</strong></td>
                <td>{{ $systemInfo['queue_driver'] }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow info-card">
        <div class="card-header">
          <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-database me-2"></i>Thống kê dữ liệu</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td width="40%"><strong>Tài khoản:</strong></td>
                <td>{{ number_format($dbStats['total_accounts']) }}</td>
              </tr>
              <tr>
                <td><strong>Công việc:</strong></td>
                <td>{{ number_format($dbStats['total_jobs']) }}</td>
              </tr>
              <tr>
                <td><strong>Giao dịch:</strong></td>
                <td>{{ number_format($dbStats['total_payments']) }}</td>
              </tr>
              <tr>
                <td><strong>Admin Logs:</strong></td>
                <td>{{ number_format($dbStats['total_logs']) }}</td>
              </tr>
              <tr>
                <td><strong>Kích thước DB:</strong></td>
                <td>{{ $dbStats['database_size'] }} MB</td>
              </tr>
              <tr>
                <td><strong>Cache đang hoạt động:</strong></td>
                <td>{{ $activeCaches }}/{{ $totalCacheKeys }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card shadow action-card h-100">
        <div class="card-body text-center">
          <i class="fas fa-broom fa-3x text-primary mb-3"></i>
          <h5 class="card-title">Xóa Cache</h5>
          <p class="card-text text-muted">Xóa cache để cập nhật dữ liệu mới nhất</p>
          <div class="btn-group-vertical w-100" role="group">
            <form action="{{ route('admin.settings.clear-cache') }}" method="POST" class="mb-2">
              @csrf
              <input type="hidden" name="type" value="all">
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-trash-alt me-1"></i> Xóa toàn bộ cache
              </button>
            </form>
            <form action="{{ route('admin.settings.clear-cache') }}" method="POST" class="mb-2">
              @csrf
              <input type="hidden" name="type" value="application">
              <button type="submit" class="btn btn-outline-primary w-100">
                <i class="fas fa-database me-1"></i> Cache ứng dụng
              </button>
            </form>
            <form action="{{ route('admin.settings.clear-cache') }}" method="POST" class="mb-2">
              @csrf
              <input type="hidden" name="type" value="view">
              <button type="submit" class="btn btn-outline-primary w-100">
                <i class="fas fa-eye me-1"></i> Cache view
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow action-card h-100">
        <div class="card-body text-center">
          <i class="fas fa-rocket fa-3x text-success mb-3"></i>
          <h5 class="card-title">Tối ưu hóa</h5>
          <p class="card-text text-muted">Tối ưu hóa ứng dụng để tăng hiệu suất</p>
          <form action="{{ route('admin.settings.optimize') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success w-100">
              <i class="fas fa-bolt me-1"></i> Tối ưu hóa ngay
            </button>
          </form>
          <div class="mt-3">
            <small class="text-muted">
              <i class="fas fa-info-circle me-1"></i>
              Bao gồm: cache config, routes, views
            </small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow action-card h-100">
        <div class="card-body text-center">
          <i class="fas fa-file-alt fa-3x text-warning mb-3"></i>
          <h5 class="card-title">Quản lý Logs</h5>
          <p class="card-text text-muted">Xóa logs cũ để giải phóng dung lượng</p>
          <form action="{{ route('admin.settings.clear-logs') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label">Xóa logs cũ hơn:</label>
              <select name="days" class="form-select">
                <option value="7">7 ngày</option>
                <option value="30" selected>30 ngày</option>
                <option value="60">60 ngày</option>
                <option value="90">90 ngày</option>
              </select>
            </div>
            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Bạn có chắc chắn muốn xóa logs cũ?')">
              <i class="fas fa-trash me-1"></i> Xóa logs cũ
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Additional Settings -->
  <div class="row g-4">
    <div class="col-12">
      <div class="card shadow">
        <div class="card-header">
          <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cogs me-2"></i>Thông tin bổ sung</h6>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="fw-bold">Môi trường</h6>
              <p class="mb-1"><strong>App Environment:</strong> {{ app()->environment() }}</p>
              <p class="mb-1"><strong>Debug Mode:</strong> {{ config('app.debug') ? 'Enabled' : 'Disabled' }}</p>
              <p class="mb-1"><strong>App URL:</strong> {{ config('app.url') }}</p>
            </div>
            <div class="col-md-6">
              <h6 class="fw-bold">Bảo mật</h6>
              <p class="mb-1"><strong>Session Driver:</strong> {{ config('session.driver') }}</p>
              <p class="mb-1"><strong>Session Lifetime:</strong> {{ config('session.lifetime') }} phút</p>
              <p class="mb-1"><strong>Timezone:</strong> {{ config('app.timezone') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
<script>
    $(document).ready(function() {
        Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: '{{ session('success') }}',
            timer: 3000,
            showConfirmButton: false
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

<script>
$(document).ready(function() {
    // Thêm loading cho tất cả form submit trong trang settings
    $('form[action*="clear-cache"], form[action*="optimize"], form[action*="clean-logs"]').on('submit', function() {
        const formAction = $(this).attr('action');
        let message = 'Đang xử lý...';
        
        if (formAction.includes('clear-cache')) {
            message = 'Đang xóa cache...';
        } else if (formAction.includes('optimize')) {
            message = 'Đang tối ưu hóa...';
        } else if (formAction.includes('clean-logs')) {
            message = 'Đang xóa logs...';
        }
        
        showLoading(message);
    });
});
</script>
@endpush
