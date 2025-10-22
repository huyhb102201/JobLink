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
                 value="{{ old('fullname', $profile->fullname ?? $account->name) }}"
                 required readonly> {{-- readonly lúc đầu --}}
        </div>

        <div class="col-md-6">
          <label class="form-label">Username</label>
          <input class="form-control" name="username"
                 value="{{ old('username', $profile->username) }}"
                 required readonly> {{-- readonly lúc đầu --}}
        </div>

        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email"
                 value="{{ old('email', $profile->email ?? $account->email) }}"
                 readonly> {{-- luôn khoá --}}
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
        <textarea id="descTextarea" class="form-control" name="description"
                  rows="6" placeholder="Giới thiệu ngắn..." disabled>{{ old('description', $profile->description) }}</textarea>
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
  </div>

  <style>
    /* Look khoá */
    .form-control:disabled, .form-control[readonly]{
      background-color:#f1f3f4!important;
      color:#6c757d!important;
      border-color:#dee2e6!important;
      cursor:not-allowed!important;
      opacity:1!important;
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
      Swal.fire({icon:'success', title:'Thành công', text:'{{ session('ok') }}', confirmButtonText:'OK'});
    });
  </script>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[action="{{ route('settings.myinfo.update') }}"]');

  // 3 trường sẽ được mở khi bấm Chỉnh sửa
  const fullname  = form.querySelector('input[name="fullname"]');
  const username  = form.querySelector('input[name="username"]');
  const descArea  = form.querySelector('textarea[name="description"]');

  const btnEditSave = document.getElementById('btnEditSave');
  const btnCancel   = document.getElementById('btnCancelEdit');

  let editing = false;

  function setEditing(on){
    editing = on;

    // chỉ 3 trường này được bật/tắt
    fullname.readOnly = !on;
    username.readOnly = !on;
    descArea.disabled = !on;

    if(on){
      btnEditSave.classList.add('btn-primary','text-white','shadow-sm');
      btnEditSave.innerHTML = '<i class="bi bi-save2"></i><span> Lưu thay đổi</span>';
      btnCancel.classList.remove('d-none');

      // focus vào Họ tên (hoặc tuỳ bạn)
      fullname.focus();
      // cho phép con trỏ ở cuối
      const v = fullname.value; fullname.value = ''; fullname.value = v;
    }else{
      btnEditSave.classList.remove('btn-primary','text-white','shadow-sm');
      btnEditSave.innerHTML = '<i class="bi bi-pencil-square"></i><span> Chỉnh sửa</span>';
      btnCancel.classList.add('d-none');
    }
  }

  btnEditSave.addEventListener('click', function(){
    if(!editing){
      setEditing(true);
    }else{
      // spinner + submit
      btnEditSave.disabled = true;
      btnEditSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
      form.submit();
    }
  });

  btnCancel.addEventListener('click', function(){
    form.reset();     // về giá trị gốc server render
    setEditing(false);
  });

  // Chặn Enter submit khi chưa bật chỉnh sửa
  form.addEventListener('keydown', function(e){
    if(!editing && e.key === 'Enter') e.preventDefault();
  });

  @if ($errors->any())
    setEditing(true);   // nếu fail validate, mở lại để người dùng sửa
  @else
    setEditing(false);
  @endif
});
</script>
@endpush

@endsection
