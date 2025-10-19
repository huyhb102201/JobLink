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
              value="{{ old('fullname', $profile->fullname ?? $account->name) }}" required disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" value="{{ old('username', $profile->username) }}" required
              disabled>
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
          <label class="form-label">Mô tả</label>
          <input class="form-control" name="description" maxlength="255"
            value="{{ old('description', $profile->description) }}" placeholder="Giới thiệu ngắn..." disabled>
        </div>
        <div class="text-end">
          <div class="d-flex justify-content-end gap-2">
            <button id="btnCancelEdit" type="button"
              class="btn btn-light btn-sm rounded-pill px-4 py-2 fw-semibold shadow-sm d-none"
              style="transition: all .3s;">
              <i class="bi bi-x-circle me-1"></i> Hủy
            </button>

            <button id="btnEditSave" type="button"
              class="btn btn-sm fw-semibold d-inline-flex align-items-center gap-2 rounded-pill px-4 py-2 border-0 shadow-sm"
              style="
      background: linear-gradient(135deg, #98a8d6ff, #1cc88a);
      color: #fefefe;               /* trắng sáng */
      text-shadow: 0 1px 3px rgba(0,0,0,0.2); /* giúp chữ nổi */
      transition: all .3s;
    ">
              <i class="bi bi-pencil-square"></i>
              <span>Chỉnh sửa</span>
            </button>

          </div>
        </div>


      </form>
    </div>
    <style>
      /* Style cho input/textarea bị disabled */
      .form-control:disabled,
      .form-control[readonly] {
        background-color: #f1f3f4 !important;
        /* xám nhạt */
        color: #6c757d !important;
        /* chữ xám */
        border-color: #dee2e6 !important;
        /* viền nhẹ */
        cursor: not-allowed !important;
        opacity: 1 !important;
        /* giữ nguyên độ sáng */
      }
    </style>

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

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action="{{ route('settings.myinfo.update') }}"]');

        // Các ô cho phép chỉnh sửa
        const fields = [
          form.querySelector('input[name="fullname"]'),
          form.querySelector('input[name="username"]'),
          form.querySelector('input[name="description"]')
        ].filter(Boolean);

        const btnEditSave = document.getElementById('btnEditSave');
        const btnCancel = document.getElementById('btnCancelEdit');

        // true = đang chỉnh sửa
        let editing = false;

        function setEditing(on) {
          editing = on;

          // bật/tắt input
          fields.forEach(el => el.disabled = !on);

          // đổi giao diện nút + hiện/ẩn Hủy
          if (on) {
            btnEditSave.classList.remove('btn-outline-primary');
            btnEditSave.classList.add('btn-primary', 'text-white', 'shadow-sm');
            btnEditSave.innerHTML = '<i class="bi bi-save2"></i><span> Lưu thay đổi</span>';

            btnCancel.classList.remove('d-none');
          } else {
            btnEditSave.classList.add('btn-outline-primary');
            btnEditSave.classList.remove('btn-primary', 'text-white', 'shadow-sm');
            btnEditSave.innerHTML = '<i class="bi bi-pencil-square"></i><span> Chỉnh sửa</span>';

            btnCancel.classList.add('d-none');
          }
        }

        // Click nút chính
        btnEditSave.addEventListener('click', function () {
          if (!editing) {
            setEditing(true);
            // focus ô đầu tiên
            if (fields[0]) fields[0].focus();
          } else {
            // Submit với spinner
            const original = btnEditSave.innerHTML;
            btnEditSave.disabled = true;
            btnEditSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
            form.submit();
            // (không reset lại ở đây — để server trả về)
          }
        });

        // Nút Hủy
        btnCancel.addEventListener('click', function () {
          form.reset();      // trả về giá trị ban đầu
          setEditing(false); // quay về chế độ xem
        });

        // Chặn Enter submit khi đang ở chế độ xem
        form.addEventListener('keydown', function (e) {
          if (!editing && e.key === 'Enter') e.preventDefault();
        });

        // Nếu có lỗi validate -> tự bật chế độ chỉnh sửa
        @if ($errors->any())
          setEditing(true);
        @else
          setEditing(false);
        @endif
        });
    </script>
  @endpush

@endsection