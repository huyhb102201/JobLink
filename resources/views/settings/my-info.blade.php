@extends('settings.layout')

@section('settings_content')
  <section class="card border-0 shadow-sm">
    <div class="card-body" style="min-height:500px;">
      <h5 class="mb-4 d-flex justify-content-between align-items-center">
        <span>Thông tin cá nhân</span>
        <a href="{{ route('portfolios.show', $profile->username) }}" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-person-circle me-1"></i> Xem trang cá nhân
        </a>
      </h5>

      <form action="{{ route('settings.myinfo.update') }}" method="POST" class="vstack gap-3">
        @csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Họ tên</label>
            <input class="form-control" name="fullname"
              value="{{ old('fullname', $profile->fullname ?? $account->name) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" value="{{ old('username', $profile->username) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email"
              value="{{ old('email', $profile->email ?? $account->email) }}" disabled>

            @php $isVerified = !is_null($account->email_verified_at); @endphp

            @if($isVerified)
              <span class="badge bg-success-subtle text-success-emphasis mt-2">
                <i class="bi bi-shield-check me-1"></i> Đã xác thực
              </span>
            @else
              <div class="d-flex align-items-center gap-2 mt-2">
                <span class="badge bg-warning-subtle text-warning-emphasis">
                  <i class="bi bi-shield-exclamation me-1"></i> Chưa xác thực
                </span>

                {{-- Nút này submit form #resendVerifyForm (POST /email/verification-notification) --}}
                <button type="submit" class="btn btn-link p-0 align-baseline" form="resendVerifyForm">
                  Gửi email xác thực
                </button>
              </div>

              @if (session('status') === 'verification-link-sent')
                <div class="text-success small mt-1">
                  Link xác minh đã được gửi! Vui lòng kiểm tra hộp thư.
                </div>
              @endif
            @endif
          </div>
          <div class="col-md-6">
            <label class="form-label">Loại tài khoản</label>
            <input class="form-control" value="{{ $account->type->name ?? 'Guest' }}" disabled>
          </div>

        </div>
        <div>
          <label class="form-label">Giới thiệu</label>
          <input class="form-control" name="description" maxlength="255"
            value="{{ old('description', $profile->description) }}" placeholder="Giới thiệu ngắn...">
        </div>
        <div class="text-end">
          <button class="btn btn-primary"><i class="bi bi-save2"></i> Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </section>
  {{-- Form riêng để gửi lại email xác thực (không hiển thị) --}}
  <form id="resendVerifyForm" method="POST" action="{{ route('verification.send') }}" class="d-none">
    @csrf
  </form>

  @if(session('ok'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
          icon: 'success',
          title: 'Thành công',
          text: '{{ session('ok') }}',
          confirmButtonText: 'OK'
        });
      });
    </script>
  @endif

@endsection