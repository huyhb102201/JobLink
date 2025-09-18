{{-- resources/views/settings/layout.blade.php (hoặc view settings) --}}
@extends('layouts.app')
@section('title', 'Cài đặt tài khoản')

@section('content')
<div class="container settings" style="margin-top: 50px;">
  <div class="row g-4 align-items-start">

    {{-- Sidebar trái --}}
    <aside class="col-12 col-lg-3 col-xl-3">
      <div class="list-group settings-sidebar">
        <a href="{{ route('settings.myinfo') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.myinfo')) active @endif">
          My info
        </a>
        <a href="{{ route('settings.billing') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.billing')) active @endif">
          Billing & Payments
        </a>
        <a href="{{ route('settings.security') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.security')) active @endif">
          Password & Security
        </a>
        <a href="{{ route('settings.membership') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.membership')) active @endif">
          Membership Settings
        </a>
        <a href="{{ route('settings.teams') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.teams')) active @endif">
          Teams
        </a>
        <a href="{{ route('settings.notifications') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.notifications')) active @endif">
          Notification Settings
        </a>
        <a href="{{ route('settings.members') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.members')) active @endif">
          Members & Permissions
        </a>
        <a href="{{ route('settings.tax') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.tax')) active @endif">
          Tax Information
        </a>
        <a href="{{ route('settings.connected') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.connected')) active @endif">
          Connected Services
        </a>
        <a href="{{ route('settings.appeals') }}"
           class="list-group-item list-group-item-action @if(request()->routeIs('settings.appeals')) active @endif">
          Appeals Tracker
        </a>
      </div>
    </aside>

    {{-- Content phải --}}
    <main class="col-12 col-lg-9 col-xl-9">
      {{-- mỗi trang con yield phần này --}}
      @yield('settings_content')
    </main>

  </div>
</div>
@endsection
