{{-- resources/views/settings/layout.blade.php (hoặc view settings) --}}
@extends('layouts.app')
@section('title', 'Cài đặt tài khoản')

@section('content')
  <div class="container settings" style="margin-top: 50px;">
    <div class="row g-4 align-items-start">
      {{-- Sidebar trái --}}
      <aside class="col-12 col-lg-3 col-xl-3">
        @php
          use Illuminate\Support\Facades\DB;

          $acct = ($account ?? auth()->user()?->loadMissing('type'));
          $isClient = (($acct?->type?->code) === 'CLIENT');
          // BUSS hoặc là thành viên tổ chức (đang ACTIVE)
          $isBusiness = (($acct?->type?->code) === 'BUSS')
            || ($acct?->account_id
              && DB::table('org_members')
                ->where('account_id', $acct->account_id)
                ->where('status', 'ACTIVE')    // nếu muốn tính cả PENDING thì dùng whereIn(['ACTIVE','PENDING'])
                ->exists());
        @endphp

        <nav class="nav flex-column fs-5 sidebar-simple">
          <a href="{{ route('settings.myinfo') }}"
            class="nav-link @if(request()->routeIs('settings.myinfo')) active @endif">
            Thông tin của tôi
          </a>
          @if($isBusiness)
            <a href="{{ route('settings.company') }}"
              class="nav-link @if(request()->routeIs('settings.company')) active @endif">
              Doanh nghiệp của tôi
            </a>
          @elseif ($isClient)
            {{-- ⚠️ Gói Client: hiển thị mờ + nút nâng cấp --}}
            <a href="{{ url('/settings/upgrade') }}"
              class="nav-link disabled-link d-flex align-items-center justify-content-between"
              title="Nâng cấp gói Business để sử dụng mục này">
              <span> Doanh nghiệp của tôi</span>
              <span class="badge upgrade-badge ms-2">Nâng cấp</span>
            </a>

          @endif
          <a href="{{ route('settings.billing') }}"
            class="nav-link @if(request()->routeIs('settings.billing')) active @endif">
            Thanh toán & Giao dịch
          </a>
          <a href="{{ route('settings.security') }}"
            class="nav-link @if(request()->routeIs('settings.security')) active @endif">
            Mật khẩu & Bảo mật
          </a>
          <a href="{{ route('settings.membership') }}"
            class="nav-link @if(request()->routeIs('settings.membership')) active @endif">
            Cài đặt thành viên
          </a>
          <a href="{{ route('settings.connected') }}"
            class="nav-link @if(request()->routeIs('settings.connected')) active @endif">
            Dịch vụ đã liên kết
          </a>
          @auth
            @php
              $acc = Auth::user()->loadMissing('type');
              $typeId = $acc->type->account_type_id ?? null;

              // check active
              $activeSubmitted = request()->routeIs('settings.reported_jobs');
            @endphp

            @if(in_array($typeId, [1, 2]))
              <a href="{{ route('settings.reported_jobs') }}"
                class="nav-link {{ $activeSubmitted ? 'active fw-semibold' : '' }}">
                Báo cáo công việc
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
      border-left: 3px solid transparent;
      /* chừa chỗ line */
      transition: all 0.2s ease;
    }

    .sidebar-simple .nav-link:hover {
      background-color: #f8f9fa;
      color: #000;
    }

    .sidebar-simple .nav-link.active {
      font-weight: 600;
      border-left: 3px solid #000;
      /* line đen bên trái */
      background-color: #fff;
      /* nền trắng giữ đơn giản */
      color: #000;
    }

    .disabled-link {
      opacity: 0.65;
      pointer-events: auto;
      transition: 0.2s;
      color: #6c757d !important;
    }

    .disabled-link:hover {
      opacity: 0.9;
      background: #fff8e1;
      color: #000 !important;
    }

    .upgrade-badge {
      background: linear-gradient(135deg, #facc15 0%, #fbbf24 45%, #f59e0b 100%);
      color: #000;
      font-weight: 600;
      border-radius: 6px;
      font-size: 0.75rem;
      padding: 3px 6px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .15);
    }
  </style>

@endsection