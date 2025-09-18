@extends('settings.layout')

@section('settings_content')
<section class="card border-0 shadow-sm">
  <div class="card-body" style="min-height:500px;">
    <h5 class="mb-4">My Info</h5>
    <form action="{{ route('settings.myinfo.update') }}" method="POST" class="vstack gap-3">
      @csrf @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Họ tên</label>
          <input class="form-control" name="fullname"
                 value="{{ old('fullname', $profile->fullname ?? $account->name) }}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email"
                 value="{{ old('email', $profile->email ?? $account->email) }}" disabled>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Loại tài khoản</label>
          <input class="form-control" value="{{ $account->type->name ?? 'Guest' }}" disabled>
        </div>
      </div>

      <div>
        <label class="form-label">Mô tả</label>
        <input class="form-control" name="description" maxlength="255"
               value="{{ old('description', $profile->description) }}" placeholder="Giới thiệu ngắn...">
      </div>
      <div class="text-end">
        <button class="btn btn-primary"><i class="bi bi-save2"></i> Lưu thay đổi</button>
      </div>
    </form>
  </div>
</section>
@endsection
