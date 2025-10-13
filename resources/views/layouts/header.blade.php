<header id="header" class="header fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">
    <!-- Logo LEFT -->
    <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2 me-auto" href="{{ url('/') }}">
      <span class="rounded-3 bg-primary-subtle text-primary px-3 py-2.5 fs-5">JL</span>
      <span class="fs-4">JobLink</span>
    </a>

    <!-- Toggle mobile -->
    <i class="mobile-nav-toggle d-xl-none bi bi-list fs-4" role="button" aria-label="Open menu"></i>

    <!-- NAV -->
    <nav id="navmenu" class="navmenu d-flex align-items-center">
      <!-- Close mobile -->
      <button class="btn-close-nav d-xl-none" type="button" aria-label="Close">
        <i class="bi bi-x fs-3"></i>
      </button>

      <!-- RIGHT cluster -->
      <ul class="d-flex align-items-center ms-xl-auto">
        <li class="nav-item">
          <a class="nav-link {{ request()->is('/') ? 'active fw-semibold' : '' }}" href="{{ url('/') }}">Trang chủ</a>
        </li>

        @auth
          @php
            $acc = Auth::user()->loadMissing('type');
            $code = strtoupper($acc->type->code ?? '');
            $isClient = $code === 'CLIENT' || $code === 'BUSS';

            // active khi user đang ở route liên quan tới client jobs
            $activePost = request()->routeIs('client.jobs.choose', 'client.jobs.wizard.*', 'client.jobs.create', 'client.jobs.ai_form');
            $activeMine = request()->routeIs('client.jobs.mine');
          @endphp

          @if($isClient)
            <li class="nav-item dropdown dropdown-hover">
              <a class="nav-link {{ request()->is('client/jobs*') ? 'active fw-semibold' : '' }}" href="#"
                data-bs-toggle="dropdown" aria-expanded="false">
                <span>Công việc</span>
                <i class="bi bi-chevron-down toggle-dropdown"></i>
              </a>
              <ul class="dropdown-menu shadow-sm" style="min-width:240px">
                <li>
                  <a class="dropdown-item {{ request()->is('jobs') || request()->is('jobs/*') ? 'active fw-semibold' : '' }}"
                    href="{{ url('/jobs') }}">
                    Danh sách công việc
                  </a>
                </li>
                <li>
                  <a class="dropdown-item {{ $activePost ? 'active fw-semibold' : '' }}"
                    href="{{ route('client.jobs.choose') }}">
                    Đăng công việc
                  </a>
                </li>
                <li>
                  <a class="dropdown-item {{ $activeMine ? 'active fw-semibold' : '' }}"
                    href="{{ route('client.jobs.mine') }}">
                    Công việc của tôi
                  </a>
                </li>
              </ul>
            </li>
          @else
            <li class="nav-item">
              <a class="nav-link {{ request()->is('jobs') || request()->is('jobs/*') ? 'active fw-semibold' : '' }}"
                href="{{ url('/jobs') }}">
                Công việc
              </a>
            </li>
          @endif
        @endauth

        <li class="nav-item">
          <a class="nav-link {{ request()->is('orgs*') ? 'active fw-semibold' : '' }}" href="{{ url('/orgs') }}">
            Doanh nghiệp
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->is('contact') ? 'active fw-semibold' : '' }}"
            href="{{ url('/contact') }}">Liên hệ</a>
        </li>

        <!-- Notifications -->
        <li class="dropdown dropdown-hover extended-dropdown-2">
          <a class="nav-link position-relative" href="{{ url('/notifications') }}">
            <i class="bi bi-bell fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
          </a>
          <ul class="dropdown-menu shadow-sm" style="min-width:220px;font-size:.9rem">
            <li>
              <a class="dropdown-item py-2" href="#">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-speedometer2 fs-5"></i>
                  <div>
                    <div class="fw-semibold">Analytics Dashboard</div>
                    <small class="text-muted">Track your performance metrics</small>
                  </div>
                  <span class="badge bg-primary ms-auto">New</span>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2" href="#">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-people fs-5"></i>
                  <div>
                    <div class="fw-semibold">Team Management</div>
                    <small class="text-muted">Manage your team members</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2" href="#">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-graph-up fs-5"></i>
                  <div>
                    <div class="fw-semibold">Sales Reports</div>
                    <small class="text-muted">Review financial statistics</small>
                  </div>
                  <span class="badge bg-danger ms-auto">Hot</span>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2" href="#">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-shield-lock fs-5"></i>
                  <div>
                    <div class="fw-semibold">Security Center</div>
                    <small class="text-muted">Manage privacy settings</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2" href="#">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-chat-dots fs-5"></i>
                  <div>
                    <div class="fw-semibold">Message Center</div>
                    <small class="text-muted">Check your notifications</small>
                  </div>
                  <span class="badge bg-info ms-auto">5</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- Chat -->
        <li class="nav-item position-relative">
          <a class="nav-link" href="{{ url('/chat') }}">
            <i class="bi bi-chat-dots fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">5</span>
          </a>
        </li>

        <!-- Search -->
        <li class="nav-item position-relative">
          <form action="#" method="GET" class="d-flex" role="search" id="searchForm">
            <div class="input-group rounded-pill overflow-hidden shadow-sm jl-search-h36">
              <select id="searchCategory" class="form-select border-0 bg-light px-2" name="category">
                <option value="job" {{ request('category') == 'job' ? 'selected' : '' }}>Công việc</option>
                <option value="account" {{ request('category') == 'account' ? 'selected' : '' }}>Tài khoản</option>
              </select>
              <input id="searchInput" name="q" value="{{ request('q') }}" class="form-control border-0 bg-light px-2"
                type="search"
                placeholder="{{ request('category') == 'account' ? 'Nhập username...' : 'Nhập công việc...' }}"
                aria-label="Search" autocomplete="off">
              <button class="btn btn-primary px-3" type="submit"><i class="bi bi-search"></i></button>
            </div>
            <div id="searchSuggestions" class="search-suggestions shadow-sm d-none">
              <ul class="list-group w-100" style="max-height:300px;overflow-y:auto"></ul>
            </div>
          </form>
        </li>

        <!-- User -->
        @auth
          <li class="nav-item dropdown dropdown-hover me-3">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown"
              data-bs-toggle="dropdown" aria-expanded="false">
              @php
                $acc = Auth::user()->loadMissing('type');
                $name = $acc->profile?->fullname ?: $acc->name ?: 'Tài khoản';
                $username = $acc->profile?->username;
                $handle = $username ? '@' . $username : null;
                $initials = collect(explode(' ', $name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                $avatar = $acc->avatar_url;
                $src = $avatar ? (preg_match('/^https?:\/\//', $avatar) ? $avatar : asset($avatar)) : null;
              @endphp
              @if($src)
                <img src="{{ $src }}" referrerpolicy="no-referrer" class="rounded-circle"
                  style="width:32px;height:32px;object-fit:cover;">
              @else
                <span class="avatar-circle">{{ $initials }}</span>
              @endif
              <span class="d-none d-lg-flex flex-column lh-sm text-start">
                <span class="fw-semibold">{{ Str::limit($name, 12) }}</span>
                @if($handle)
                  <span class="text-muted small">{{ Str::limit($handle, 12) }}</span>
                @endif
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm p-2" style="min-width: 240px; font-size: 0.9rem;"
              aria-labelledby="userDropdown">
              @php
                $typeName = $acc->type->name ?? 'Guest';
                $typeCode = $acc->type->code ?? 'GUEST';
              @endphp
              <li class="px-2 py-1 text-muted small d-flex align-items-center gap-2">
                <i class="bi bi-patch-check fs-5"></i>
                Loại tài khoản:
                <span class="badge bg-light text-dark">{{ $typeName }}</span>
              </li>
              @if ($typeCode === 'F_BASIC' || $typeCode === 'CLIENT')
                <li class="px-2 py-1">
                  <a href="{{ route('settings.upgrade') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                    <div>
                      <div class="d-flex align-items-center gap-1 fw-semibold text-dark">
                        <i class="bi bi-gem text-warning fs-5"></i>
                        Nâng cấp gói Plus
                      </div>
                      <small class="text-muted">Mở khóa tính năng & ưu đãi</small>
                    </div>
                  </a>
                </li>
              @endif
              <li>
                <hr class="dropdown-divider my-1">
              </li>
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center gap-1 py-1"
                  onclick="window.location='{{ route('settings.myinfo') }}'">
                  <i class="bi bi-person-circle fs-5 me-1"></i>Cài đặt tài khoản
                </button>

              </li>
              @if ($typeCode === 'ADMIN')
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center gap-1 py-1"
                    onclick="window.location='{{ route('admin.accounts.index') }}'">
                    <i class="bi bi-speedometer2 fs-5 me-1"></i>Bảng điều khiển
                  </button>
                </li>
              @endif
              <li>
                <hr class="dropdown-divider my-1">
              </li>
              <li>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                  @csrf
                  <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-1">
                    <i class="bi bi-box-arrow-right fs-5"></i> Đăng xuất
                  </button>
                </form>
              </li>
            </ul>
          </li>
        @else
        <li class="nav-item me-3">
          <a class="nav-link" href="{{ route('login') }}">Đăng nhập</a>
        </li>
        @endif
      </ul>
    </nav>
  </div>

  <script>
    // ====== Search giữ logic cũ ======
    const category = document.getElementById('searchCategory');
    const input = document.getElementById('searchInput');
    const suggestions = document.getElementById('searchSuggestions')?.querySelector('ul');
    const form = document.getElementById('searchForm');
    let debounceTimer; let cache = {}; const defaultAvatar = "/assets/img/defaultavatar.jpg";

    // Hover dropdown chỉ desktop
    document.querySelectorAll('.dropdown-hover').forEach(dropdown => {
      const link = dropdown.querySelector('.nav-link');
      const menu = dropdown.querySelector('.dropdown-menu');
      const isMobile = window.matchMedia("(max-width: 1199px)").matches;
      if (!isMobile) {
        link.addEventListener('mouseenter', () => { menu.classList.add('show'); menu.setAttribute('data-bs-popper', 'static'); });
        dropdown.addEventListener('mouseleave', () => { menu.classList.remove('show'); menu.removeAttribute('data-bs-popper'); });
      }
    });

    // Mobile: click để toggle submenu
    function wireMobileDropdowns() {
      const isMobile = window.matchMedia("(max-width: 1199px)").matches;
      document.querySelectorAll('.dropdown-hover > a.nav-link').forEach(a => {
        a.onclick = (e) => {
          if (isMobile) {
            e.preventDefault();
            const menu = a.parentElement.querySelector('.dropdown-menu');
            menu?.classList.toggle('show');
          }
        }
      });
    }
    wireMobileDropdowns();
    window.addEventListener('resize', wireMobileDropdowns);

    // Toggle offcanvas nav
    document.querySelector('.mobile-nav-toggle')?.addEventListener('click', () => {
      document.getElementById('navmenu').classList.add('navmenu-active');
      document.body.classList.add('overflow-hidden');
    });
    document.querySelector('.btn-close-nav')?.addEventListener('click', () => {
      document.getElementById('navmenu').classList.remove('navmenu-active');
      document.body.classList.remove('overflow-hidden');
    });

    window.addEventListener("load", () => {
      let saved = localStorage.getItem("lastSearch");
      if (saved) {
        let { type, query } = JSON.parse(saved);
        if (category) category.value = type;
        if (input) input.value = query;
      }
    });

    category?.addEventListener('change', function () {
      if (input) {
        input.placeholder = this.value === 'account' ? "Nhập username..." : "Nhập công việc...";
      }
      suggestions?.parentElement.classList.add('d-none');
      if (suggestions) suggestions.innerHTML = '';
    });

    input?.addEventListener('input', function () {
      if (this.value.trim().length === 0) {
        localStorage.removeItem("lastSearch");
        suggestions?.parentElement.classList.add('d-none');
        if (suggestions) suggestions.innerHTML = '';
      } else {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchData(this.value.trim()), 300);
      }
    });

    input?.addEventListener('focus', () => {
      if (!input || !suggestions) return;
      if (input.value.length >= 2) {
        let saved = localStorage.getItem("lastSearch");
        if (saved) {
          let { type, query, data } = JSON.parse(saved);
          if (query === input.value && type === category.value) { renderSuggestions(data, type); return; }
        }
        fetchData(input.value);
      }
    });

    document.addEventListener('click', function (e) {
      if (!input || !suggestions) return;
      const wrap = document.getElementById('searchSuggestions');
      if (wrap && !wrap.contains(e.target) && !input.contains(e.target)) {
        wrap.classList.add('d-none');
        suggestions.innerHTML = '';
      }
    });

    form?.addEventListener('submit', function (e) {
      e.preventDefault();
      if (input && input.value.trim().length >= 2) fetchData(input.value.trim());
    });

    async function fetchData(query) {
      if (!suggestions) return;
      if (query.length < 2) {
        suggestions.parentElement.classList.add('d-none'); suggestions.innerHTML = ''; return;
      }
      showMessage(`<div class="d-flex justify-content-center align-items-center">
        <div class="spinner-border spinner-border-sm me-2 text-primary"></div><span>Đang tìm...</span></div>`);
      let type = category.value; let cacheKey = type + "_" + query;
      if (cache[cacheKey]) { renderSuggestions(cache[cacheKey], type); return; }
      try {
        let response = await fetch(`/search?q=${encodeURIComponent(query)}&type=${type}`);
        let data = await response.json();
        cache[cacheKey] = data;
        localStorage.setItem("lastSearch", JSON.stringify({ type, query, data }));
        renderSuggestions(data, type);
      } catch (e) { console.error("Lỗi fetch:", e); showMessage("Có lỗi xảy ra, vui lòng thử lại"); }
    }

    function renderSuggestions(data, type) {
      if (!suggestions) return;
      suggestions.innerHTML = '';
      if (data.length > 0) {
        data.forEach(item => {
          let li = document.createElement('li');
          li.className = "list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3";
          li.style.cursor = "pointer"; li.style.fontSize = "0.9rem";
          if (type === 'account') {
            let avatar = document.createElement('img');
            avatar.src = item.avatar_url ? item.avatar_url : defaultAvatar;
            avatar.className = "rounded-circle me-2"; avatar.style.width = "32px"; avatar.style.height = "32px"; avatar.style.objectFit = "cover";
            let textDiv = document.createElement('div'); textDiv.className = "d-flex flex-column";
            let username = document.createElement('span'); username.textContent = (item.username || '').slice(0, 20) + ((item.username || '').length > 20 ? '...' : ''); username.className = "fw-semibold";
            let fullname = document.createElement('small'); fullname.textContent = item.fullname ? item.fullname.slice(0, 20) + (item.fullname.length > 20 ? '...' : '') : ''; fullname.className = "text-muted";
            textDiv.appendChild(username); textDiv.appendChild(fullname);
            li.appendChild(avatar); li.appendChild(textDiv);
            li.onclick = () => window.location.href = `/portfolios/${item.username}`;
          } else {
            let icon = document.createElement('i'); icon.className = "bi bi-briefcase-fill me-2 text-primary"; icon.style.fontSize = "1rem";
            let textDiv = document.createElement('div'); textDiv.className = "d-flex flex-column";
            let title = document.createElement('span'); title.textContent = (item.title || '').slice(0, 25) + ((item.title || '').length > 25 ? '...' : ''); title.className = "fw-semibold";
            let description = document.createElement('small'); description.textContent = item.description ? item.description.slice(0, 30) + (item.description.length > 30 ? '...' : '') : ''; description.className = "text-muted";
            textDiv.appendChild(title); textDiv.appendChild(description);
            li.appendChild(icon); li.appendChild(textDiv);
            li.onclick = () => window.location.href = `/jobs/${item.id}`;
          }
          suggestions.appendChild(li);
        });
        suggestions.parentElement.classList.remove('d-none');
      } else {
        showMessage("Không tìm thấy kết quả nào");
      }
    }

    function showMessage(msg) {
      if (!suggestions) return;
      suggestions.innerHTML = `<li class="list-group-item text-muted text-center py-2" style="font-size:.9rem;">${msg}</li>`;
      suggestions.parentElement.classList.remove('d-none');
    }
  </script>

  <style>
    :root {
      --jl-nav-height: 64px;
    }

    body {
      padding-top: var(--jl-nav-height);
    }

    /* Tight desktop spacing */
    @media (min-width:1200px) {
      .jl-search-h36 {
        height: 34px;
      }

      .jl-search-h36 .form-select {
        max-width: 110px;
        font-size: 13px;
      }

      .jl-search-h36 .form-control {
        font-size: 13px;
      }

      .jl-search-h36 .btn {
        padding-inline: .65rem;
      }
    }

    /* Search chung */
    .input-group {
      border-radius: 50rem !important;
      transition: box-shadow .2s ease;
    }

    .input-group:focus-within {
      box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .25);
    }

    .form-select,
    .form-control {
      background-color: #f8f9fa !important;
      border: none !important;
    }

    .form-select:focus,
    .form-control:focus {
      box-shadow: none !important;
      background-color: #fff !important;
    }

    .btn-primary {
      border-radius: 0 50rem 50rem 0 !important;
    }

    .search-suggestions {
      position: absolute;
      top: 40px;
      left: 0;
      right: 0;
      z-index: 1000;
      background: #fff;
      border-radius: .5rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
      padding: .5rem 0;
    }

    .search-suggestions .list-group-item {
      border: none;
      padding: .5rem 1rem;
      transition: background-color .2s ease;
    }

    .search-suggestions .list-group-item:hover {
      background: #f1f3f5;
    }

    .avatar-circle {
      width: 32px;
      height: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: #e9ecef;
      font-weight: 700;
    }

    /* ===== Mobile (<1200px): Off-canvas giữ nguyên ===== */
    @media (max-width:1199.98px) {
      .mobile-nav-toggle {
        margin-left: .5rem;
      }

      #navmenu {
        position: fixed;
        inset: 0 0 0 auto;
        width: 86vw;
        max-width: 380px;
        height: 100vh;
        background: #fff;
        transform: translateX(100%);
        transition: transform .28s ease;
        box-shadow: -16px 0 24px -16px rgba(0, 0, 0, .15);
        z-index: 2000;
        padding: .75rem 1rem;
        display: block !important;
      }

      #navmenu.navmenu-active {
        transform: translateX(0);
      }

      #navmenu .btn-close-nav {
        display: block;
        margin-left: auto;
        margin-bottom: .5rem;
      }

      #navmenu>ul {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: .25rem;
      }

      #navmenu .nav-item {
        width: 100%;
      }

      #navmenu .nav-link {
        width: 100%;
        padding: .675rem .5rem;
        border-radius: .5rem;
      }

      #navmenu .nav-link.active {
        background: #f1f3f5;
      }

      #navmenu .dropdown-menu {
        position: static !important;
        transform: none !important;
        display: none;
        float: none;
        box-shadow: none !important;
        border: 1px solid #e9ecef;
        border-radius: .5rem;
        margin: .25rem 0 .5rem 0;
        padding: .25rem !important;
        width: 100% !important;
        min-width: unset !important;
      }

      #navmenu .dropdown-menu.show {
        display: block;
      }

      #navmenu .dropdown-item {
        padding: .5rem .625rem !important;
        border-radius: .375rem;
      }

      #navmenu .dropdown-item+.dropdown-item {
        margin-top: .125rem;
      }

      .user-dd {
        padding: .25rem !important;
      }

      .user-dd .dropdown-item {
        padding: .5rem .6rem !important;
        gap: .5rem !important;
      }

      .user-dd .px-2.py-1 {
        padding: .4rem .5rem !important;
      }

      .user-dd .dropdown-divider {
        margin: .25rem 0 !important;
      }

      .nav-item .badge {
        transform: translate(-30%, -30%) !important;
      }

      #searchForm {
        position: relative;
      }

      .search-suggestions {
        top: 44px;
      }
    }
  </style>
</header>
