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
              value="{{ old('fullname', $profile->fullname ?? $account->name) }}" required readonly> {{-- readonly lúc đầu
            --}}
          </div>

          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" value="{{ old('username', $profile->username) }}" required
              readonly> {{-- readonly lúc đầu --}}
          </div>

          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email"
              value="{{ old('email', $profile->email ?? $account->email) }}" readonly> {{-- luôn khoá --}}
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
                <button type="submit" class="btn btn-link p-0 align-baseline" form="resendVerifyForm">
                  Gửi email xác thực
                </button>
              </div>
              @if (session('status') === 'verification-link-sent')
                <div class="text-success small mt-1">Link xác minh đã được gửi! Vui lòng kiểm tra hộp thư.</div>
              @endif
            @endif
          </div>

          <div class="col-md-6">
            <label class="form-label">Loại tài khoản</label>
            <input class="form-control" value="{{ $account->type->name ?? 'Guest' }}" disabled>
          </div>
        </div>

        {{-- Mô tả: disabled lúc đầu, mở khi bấm Chỉnh sửa --}}
        <div class="mb-2">
          <label class="form-label">Mô tả</label>
          <textarea id="descTextarea" class="form-control" name="description" rows="6" placeholder="Giới thiệu ngắn..."
            disabled>{{ old('description', $profile->description) }}</textarea>
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
              style="background: linear-gradient(135deg, #98a8d6ff, #1cc88a); color:#fefefe; text-shadow:0 1px 3px rgba(0,0,0,.2); transition:all .3s;">
              <i class="bi bi-pencil-square"></i>
              <span>Chỉnh sửa</span>
            </button>
          </div>
        </div>

      </form>
      <hr class="my-4">

      <div class="alert alert-danger d-flex align-items-start" role="alert">
        <i class="bi bi-trash3-fill me-2 mt-1"></i>
        <div>
          <div class="fw-semibold mb-1">Xoá vĩnh viễn tài khoản</div>
          <div class="small">
            Thao tác này <strong>không thể hoàn tác</strong>. Tài khoản của bạn và các dịch vụ liên quan sẽ bị xóa vĩnh
            viễn.
          </div>
          <button type="button" class="btn btn-danger btn-sm mt-3" data-bs-toggle="modal"
            data-bs-target="#deleteAccountModal">
            <i class="bi bi-person-dash me-1"></i> Xoá tài khoản
          </button>
        </div>
      </div>

      <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
          <form method="POST" action="{{ route('settings.account.destroy') }}" class="modal-content">
            @csrf
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title" id="deleteAccountModalLabel">
                <i class="bi bi-exclamation-octagon me-2"></i> Xác nhận xoá vĩnh viễn
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              @if ($errors->hasBag('delete') && $errors->delete->first('general'))
                <div class="alert alert-danger">
                  {{ $errors->delete->first('general') }}
                </div>
              @endif
              <div class="alert alert-warning small">
                <ul class="mb-0 ps-3">
                  <li>Bạn sẽ bị đăng xuất ngay sau khi xoá.</li>
                  <li>Hành động này là <strong>vĩnh viễn</strong> nếu CSDL của bạn không bật SoftDeletes.</li>
                  <li>Hãy đảm bảo đã sao lưu dữ liệu quan trọng.</li>
                </ul>
              </div>

              <div class="mb-3">
                <label class="form-label">Nhập mật khẩu hiện tại</label>
                <input type="password" class="form-control" name="password" required placeholder="Mật khẩu">
              </div>

              <div class="mb-3">
                <label class="form-label">Gõ <code>DELETE</code> để xác nhận</label>
                <input type="text" class="form-control" name="confirm_text" id="confirmDeleteText" placeholder="DELETE"
                  required>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="agreeDelete" name="agree">
                <label class="form-check-label" for="agreeDelete">
                  Tôi hiểu đây là hành động xoá vĩnh viễn và không thể hoàn tác.
                </label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i> Huỷ
              </button>
              <button id="btnConfirmDelete" type="submit" class="btn btn-danger" disabled>
                <i class="bi bi-trash3 me-1"></i> Xoá vĩnh viễn
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

    <style>
      /* Look khoá */
      .form-control:disabled,
      .form-control[readonly] {
        background-color: #f1f3f4 !important;
        color: #6c757d !important;
        border-color: #dee2e6 !important;
        cursor: not-allowed !important;
        opacity: 1 !important;
      }
    </style>
  </section>

  {{-- Form gửi lại email xác thực --}}
  <form id="resendVerifyForm" method="POST" action="{{ route('verification.send') }}" class="d-none">
    @csrf
  </form>

  @if(session('ok'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({ icon: 'success', title: 'Thành công', text: '{{ session('ok') }}', confirmButtonText: 'OK' });
      });
    </script>
  @endif

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action="{{ route('settings.myinfo.update') }}"]');

        // 3 trường sẽ được mở khi bấm Chỉnh sửa
        const fullname = form.querySelector('input[name="fullname"]');
        const username = form.querySelector('input[name="username"]');
        const descArea = form.querySelector('textarea[name="description"]');

        const btnEditSave = document.getElementById('btnEditSave');
        const btnCancel = document.getElementById('btnCancelEdit');

        let editing = false;

        function setEditing(on) {
          editing = on;

          // chỉ 3 trường này được bật/tắt
          fullname.readOnly = !on;
          username.readOnly = !on;
          descArea.disabled = !on;

          if (on) {
            btnEditSave.classList.add('btn-primary', 'text-white', 'shadow-sm');
            btnEditSave.innerHTML = '<i class="bi bi-save2"></i><span> Lưu thay đổi</span>';
            btnCancel.classList.remove('d-none');

            // focus vào Họ tên (hoặc tuỳ bạn)
            fullname.focus();
            // cho phép con trỏ ở cuối
            const v = fullname.value; fullname.value = ''; fullname.value = v;
          } else {
            btnEditSave.classList.remove('btn-primary', 'text-white', 'shadow-sm');
            btnEditSave.innerHTML = '<i class="bi bi-pencil-square"></i><span> Chỉnh sửa</span>';
            btnCancel.classList.add('d-none');
          }
        }

        btnEditSave.addEventListener('click', function () {
          if (!editing) {
            setEditing(true);
          } else {
            // spinner + submit
            btnEditSave.disabled = true;
            btnEditSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
            form.submit();
          }
        });

        btnCancel.addEventListener('click', function () {
          form.reset();     // về giá trị gốc server render
          setEditing(false);
        });

        // Chặn Enter submit khi chưa bật chỉnh sửa
        form.addEventListener('keydown', function (e) {
          if (!editing && e.key === 'Enter') e.preventDefault();
        });

        @if ($errors->any() && !$errors->hasBag('delete'))
          setEditing(true);
        @else
          setEditing(false);
        @endif

    });
      document.addEventListener('DOMContentLoaded', function () {
        const txt = document.getElementById('confirmDeleteText');
        const agree = document.getElementById('agreeDelete');
        const btn = document.getElementById('btnConfirmDelete');

        function updateBtn() {
          const ok = (txt.value.trim().toUpperCase() === 'DELETE') && agree.checked;
          btn.disabled = !ok;
        }
        if (txt && agree && btn) {
          txt.addEventListener('input', updateBtn);
          agree.addEventListener('change', updateBtn);
        }
      });
    </script>
  @endpush
  @if ($errors->hasBag('delete'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('deleteAccountModal');
        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
      });
    </script>
  @endif

@endsection