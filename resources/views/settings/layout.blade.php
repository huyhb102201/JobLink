{{-- resources/views/settings/layout.blade.php (hoặc view settings) --}}
@extends('layouts.app')
@section('title', 'Cài đặt tài khoản')

@section('content')
<div class="container settings" style="margin-top: 50px;">
  <div class="row g-4 align-items-start">

    {{-- Sidebar trái --}}
<aside class="col-12 col-lg-3 col-xl-3">
  <nav class="nav flex-column fs-5 sidebar-simple">
    <a href="{{ route('settings.myinfo') }}"
       class="nav-link @if(request()->routeIs('settings.myinfo')) active @endif">
      My info
    </a>
    <a href="{{ route('settings.billing') }}"
       class="nav-link @if(request()->routeIs('settings.billing')) active @endif">
      Billing & Payments
    </a>
    <a href="{{ route('settings.security') }}"
       class="nav-link @if(request()->routeIs('settings.security')) active @endif">
      Password & Security
    </a>
    <a href="{{ route('settings.membership') }}"
       class="nav-link @if(request()->routeIs('settings.membership')) active @endif">
      Membership Settings
    </a>
    <a href="{{ route('settings.teams') }}"
       class="nav-link @if(request()->routeIs('settings.teams')) active @endif">
      Teams
    </a>
    <a href="{{ route('settings.notifications') }}"
       class="nav-link @if(request()->routeIs('settings.notifications')) active @endif">
      Notification Settings
    </a>
    <a href="{{ route('settings.members') }}"
       class="nav-link @if(request()->routeIs('settings.members')) active @endif">
      Members & Permissions
    </a>
    <a href="{{ route('settings.tax') }}"
       class="nav-link @if(request()->routeIs('settings.tax')) active @endif">
      Tax Information
    </a>
    <a href="{{ route('settings.connected') }}"
       class="nav-link @if(request()->routeIs('settings.connected')) active @endif">
      Connected Services
    </a>
    <a href="{{ route('settings.appeals') }}"
       class="nav-link @if(request()->routeIs('settings.appeals')) active @endif">
      Appeals Tracker
    </a>

   @auth
    @php
        $acc = Auth::user()->loadMissing('type');
        $typeId = $acc->type->account_type_id ?? null;

        // check active
        $activeSubmitted = request()->routeIs('settings.submitted_jobs');
    @endphp

    @if(in_array($typeId, [1, 2]))
        <a href="{{ route('settings.submitted_jobs') }}"
           class="nav-link {{ $activeSubmitted ? 'active fw-semibold' : '' }}">
           Công việc đã nộp
        </a>
    @endif
@endauth


  </nav>
</aside>



    {{-- Content phải --}}
    <main class="col-12 col-lg-9 col-xl-9">
      {{-- mỗi trang con yield phần này --}}
      @yield('settings_content')
    </main>

  </div>
</div>
<style>
  .sidebar-simple .nav-link {
  color: #333;
  padding: .75rem 1rem;
  border-left: 3px solid transparent; /* chừa chỗ line */
  transition: all 0.2s ease;
}

.sidebar-simple .nav-link:hover {
  background-color: #f8f9fa;
  color: #000;
}

.sidebar-simple .nav-link.active {
  font-weight: 600;
  border-left: 3px solid #000; /* line đen bên trái */
  background-color: #fff;      /* nền trắng giữ đơn giản */
  color: #000;
}
</style>
@endsection
