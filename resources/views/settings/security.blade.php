@extends('settings.layout')

@section('settings_content')
<section class="card border-0 shadow-sm">
  <div class="card-body" style="min-height:500px;">
    <h5 class="mb-3">Password & Security</h5>
    <form action="{{ route('settings.security.update') }}" method="POST" class="row g-3">
      @csrf @method('PUT')
      <div class="col-md-6">
        <label class="form-label">Mật khẩu hiện tại</label>
        <input type="password" class="form-control" name="password_current" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Mật khẩu mới</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Nhập lại mật khẩu mới</label>
        <input type="password" class="form-control" name="password_confirmation" required>
      </div>
      <div class="col-12 text-end">
        <button class="btn btn-primary btn-sm">Đổi mật khẩu</button>
      </div>
    </form>
  </div>
</section>
@endsection
