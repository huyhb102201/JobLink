@extends('layouts.app')
@section('title', 'Cài đặt tài khoản')

@section('content')
<div class="container" style="max-width:1100px;">
  @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif

  <div class="row g-4">
    {{-- Sidebar --}}
    <div class="col-lg-3">
      <div class="list-group list-group-flush sticky-top" style="top:80px;">
        <a href="#my-info" class="list-group-item list-group-item-action active">My info</a>
        <a href="#billing" class="list-group-item list-group-item-action">Billing & Payments</a>
        <a href="#security" class="list-group-item list-group-item-action">Password & Security</a>
        <a href="#membership" class="list-group-item list-group-item-action">Membership Settings</a>
        <a href="#teams" class="list-group-item list-group-item-action">Teams</a>
        <a href="#notifications" class="list-group-item list-group-item-action">Notification Settings</a>
        <a href="#members" class="list-group-item list-group-item-action">Members & Permissions</a>
        <a href="#tax" class="list-group-item list-group-item-action">Tax Information</a>
        <a href="#connected" class="list-group-item list-group-item-action">Connected Services</a>
        <a href="#appeals" class="list-group-item list-group-item-action">Appeals Tracker</a>
      </div>
    </div>

    {{-- Content --}}
    <div class="col-lg-9 vstack gap-4">

      {{-- My info --}}
      <section id="my-info" class="card border-0 shadow-sm">
        <div class="card-body">
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
                  value="{{ old('email', $profile->email ?? $account->email) }}">
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Company name</label>
                <input class="form-control" name="company_name"
                       value="{{ old('company_name', $account->company_name) }}">
              </div>
              <div class="col-md-6">
                <label class="form-label">Loại tài khoản</label>
                <input class="form-control" value="{{ $account->type->name ?? 'Guest' }}" disabled>
              </div>
            </div>

            <div>
              <label class="form-label">Mô tả</label>
              <input class="form-control" name="description"
                     value="{{ old('description', $profile->description) }}" maxlength="255"
                     placeholder="Giới thiệu ngắn...">
            </div>

            <div>
              <label class="form-label">Kỹ năng (ngăn cách dấu phẩy)</label>
              <input class="form-control" name="skill" value="{{ old('skill', $profile->skill) }}"
                     placeholder="laravel, react, mysql">
            </div>

            <div class="text-end">
              <button class="btn btn-primary"><i class="bi bi-save2"></i> Lưu thay đổi</button>
            </div>
          </form>
        </div>
      </section>

      {{-- Billing & Payments (placeholder UI) --}}
      <section id="billing" class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="mb-3">Billing & Payments</h5>
          <p class="text-muted">Tích hợp phương thức thanh toán (VD: thẻ, ví) ở đây.</p>
          <button class="btn btn-outline-secondary btn-sm">Add payment method</button>
        </div>
      </section>

      {{-- Security --}}
      <section id="security" class="card border-0 shadow-sm">
        <div class="card-body">
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

      {{-- Membership --}}
      <section id="membership" class="card border-0 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-start">
          <div>
            <h5 class="mb-1">Membership Settings</h5>
            <div class="text-muted">Gói hiện tại: <b>{{ $account->type->name ?? 'Guest' }}</b></div>
          </div>
          <form action="{{ route('settings.membership.change') }}" method="POST" class="d-flex gap-2">
            @csrf
            <select class="form-select form-select-sm" name="account_type_id" style="width: 240px;">
              @foreach(\App\Models\AccountType::where('status',1)->orderBy('account_type_id')->get() as $t)
                <option value="{{ $t->account_type_id }}"
                        @selected($t->account_type_id == $account->account_type_id)>
                  {{ $t->name }}
                </option>
              @endforeach
            </select>
            <button class="btn btn-outline-primary btn-sm">Đổi gói</button>
          </form>
        </div>
      </section>

      {{-- Teams / Members / Tax / Connected / Appeals: placeholders --}}
      <section id="teams" class="card border-0 shadow-sm"><div class="card-body">
        <h5 class="mb-1">Teams</h5><div class="text-muted">Quản lý team, mời coworker...</div>
      </div></section>

      <section id="notifications" class="card border-0 shadow-sm"><div class="card-body">
        <h5 class="mb-3">Notification Settings</h5>
        <form action="{{ route('settings.notifications.update') }}" method="POST" class="vstack gap-2">
          @csrf @method('PUT')
          <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="online_for_messages"
                   @checked(($settings['online_for_messages'] ?? true))>
            <span class="form-check-label">Online for messages</span>
          </label>
          <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="notify_messages"
                   @checked(($settings['notify_messages'] ?? true))>
            <span class="form-check-label">Notify on new messages</span>
          </label>
          <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="notify_new_proposals"
                   @checked(($settings['notify_new_proposals'] ?? true))>
            <span class="form-check-label">Notify on new proposals</span>
          </label>
          <div class="text-end mt-2">
            <button class="btn btn-primary btn-sm">Lưu</button>
          </div>
        </form>
      </div></section>

      <section id="members" class="card border-0 shadow-sm"><div class="card-body">
        <h5 class="mb-1">Members & Permissions</h5><div class="text-muted">Phân quyền cho team (nếu là Client/Agency).</div>
      </div></section>

      <section id="tax" class="card border-0 shadow-sm"><div class="card-body">
        <h5 class="mb-1">Tax Information</h5><div class="text-muted">Tải/nhập thông tin thuế (mẫu W-8/W-9...).</div>
      </div></section>

      <section id="connected" class="card border-0 shadow-sm"><div class="card-body">
        <h5 class="mb-3">Connected Services</h5>
        <ul class="list-unstyled vstack gap-2">
          <li><i class="bi bi-google me-2"></i> Google: <b>{{ $account->provider==='google' ? 'Connected' : 'Not connected' }}</b></li>
          <li><i class="bi bi-facebook me-2"></i> Facebook: <b>{{ $account->provider==='facebook' ? 'Connected' : 'Not connected' }}</b></li>
        </ul>
      </div></section>

      <section id="appeals" class="card border-0 shadow-sm"><div class="card-body">
        <h5 class="mb-1">Appeals Tracker</h5><div class="text-muted">Theo dõi khiếu nại/tạm khoá (nếu có).</div>
      </div></section>

    </div>
  </div>
</div>

{{-- Giữ tab/section khi refresh --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const links = document.querySelectorAll('.list-group a');
  links.forEach(a => a.addEventListener('click', () => setTimeout(()=>history.replaceState(null,null,a.getAttribute('href')), 0)));
});
</script>
@endpush
@endsection
