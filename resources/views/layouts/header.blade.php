{{-- resources/views/components/navbar.blade.php --}}
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<nav class="navbar navbar-expand-lg navbar-light fixed-top border-0">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="{{ url('/') }}">
            <span class="rounded-3 bg-primary-subtle text-primary px-2 py-1">JL</span> JobLink
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarJobLink"
            aria-controls="navbarJobLink" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarJobLink">
            {{-- Left --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/') ? 'active fw-semibold' : '' }}" href="{{ url('/') }}">
                        Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('jobs*') ? 'active fw-semibold' : '' }}"
                        href="{{ url('/jobs') }}">
                        Công việc
                    </a>
                </li>
                @auth
                    @php
                        $acc = Auth::user()->loadMissing('type');
                        $code = strtoupper($acc->type->code ?? '');
                        $isClient = $code === 'CLIENT' || $code === 'BUSS';
                        // những route được coi là "Đăng công việc"
                        $activePost = request()->routeIs(
                            'client.jobs.choose',
                            'client.jobs.wizard.*',
                            'client.jobs.create',
                            'client.jobs.ai_form'
                        );

                        // trang My Jobs
                        $activeMine = request()->routeIs('client.jobs.mine');
                      @endphp

                    @if($isClient)
                        <li class="nav-item">
                            <a class="nav-link {{ $activePost ? 'active fw-semibold' : '' }}"
                                href="{{ route('client.jobs.choose') }}">
                                Đăng công việc
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ $activeMine ? 'active fw-semibold' : '' }}"
                                href="{{ route('client.jobs.mine') }}">
                                Công việc của tôi
                            </a>
                        </li>
                    @endif
                @endauth

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('freelancers*') ? 'active fw-semibold' : '' }}"
                        href="{{ url('/freelancers') }}">
                        Freelancer
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('contact') ? 'active fw-semibold' : '' }}"
                        href="{{ url('/contact') }}">
                        Liên hệ
                    </a>
                </li>
            </ul>

            {{-- Right --}}
            <ul class="navbar-nav ms-auto align-items-lg-center">
                {{-- Nút Thông báo --}}
                <li class="nav-item me-lg-2 my-2 my-lg-0 position-relative">
                    <a class="nav-link" href="{{ url('/notifications') }}">
                        <i class="bi bi-bell fs-5"></i>
                        {{-- Badge số lượng thông báo --}}
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </a>
                </li>

                {{-- Nút Chat --}}
                <li class="nav-item me-lg-2 my-2 my-lg-0 position-relative">
                    <a class="nav-link" href="{{ url('/chats') }}">
                        <i class="bi bi-chat-dots fs-5"></i>
                        {{-- Badge số tin nhắn chưa đọc --}}
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            5
                        </span>
                    </a>
                </li>

                {{-- Ô tìm kiếm --}}
                <li class="nav-item me-lg-2 my-2 my-lg-0" style="min-width: 300px; position: relative;">
                    <form action="#" method="GET" class="d-flex" role="search" id="searchForm">
                        <div class="input-group w-100 rounded-pill overflow-hidden shadow-sm" style="height: 36px;">
                            <select id="searchCategory" class="form-select border-0 bg-light px-2" name="category"
                                style="max-width: 120px; font-size: 14px;">
                                <option value="job" {{ request('category') == 'job' ? 'selected' : '' }}>Công việc
                                </option>
                                <option value="account" {{ request('category') == 'account' ? 'selected' : '' }}>Tài khoản
                                </option>
                            </select>

                            <input id="searchInput" name="q" value="{{ request('q') }}"
                                class="form-control border-0 bg-light px-2" type="search"
                                placeholder="{{ request('category') == 'account' ? 'Nhập username...' : 'Nhập công việc...' }}"
                                aria-label="Search" style="font-size: 14px;" autocomplete="off">

                            <button class="btn btn-primary px-3" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>

                        <ul id="searchSuggestions" class="list-group position-absolute w-100 shadow-sm d-none"
                            style="top: 42px; z-index: 1000; max-height: 300px; overflow-y: auto;"></ul>
                    </form>
                </li>


                <script>
                    const category = document.getElementById('searchCategory');
                    const input = document.getElementById('searchInput');
                    const suggestions = document.getElementById('searchSuggestions');
                    const form = document.getElementById('searchForm');
                    let debounceTimer;
                    let cache = {};
                    const defaultAvatar = "/assets/img/defaultavatar.jpg";

                    window.addEventListener("load", () => {
                        let saved = localStorage.getItem("lastSearch");
                        if (saved) {
                            let { type, query, data } = JSON.parse(saved);
                            category.value = type;
                            input.value = query;

                        }
                    });

                    category.addEventListener('change', function () {
                        if (this.value === 'account') {
                            input.placeholder = "Vui lòng nhập username...";
                        } else {
                            input.placeholder = "Nhập công việc...";
                        }
                        suggestions.classList.add('d-none');
                    });

                    input.addEventListener('input', function () {
                        // Nếu input bị xóa rỗng (dùng cả nút x của trình duyệt)
                        if (this.value.trim().length === 0) {
                            localStorage.removeItem("lastSearch"); // xóa localStorage
                            suggestions.classList.add('d-none');   // ẩn gợi ý
                        } else {
                            // debounce fetch data như bình thường
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(() => {
                                fetchData(this.value.trim());
                            }, 300);
                        }
                    });


                    input.addEventListener('focus', () => {
                        if (input.value.length >= 2) {
                            let saved = localStorage.getItem("lastSearch");
                            if (saved) {
                                let { type, query, data } = JSON.parse(saved);
                                if (query === input.value && type === category.value) {
                                    renderSuggestions(data, type);
                                    return;
                                }
                            }
                            fetchData(input.value);
                        }
                    });

                    document.addEventListener('click', function (e) {
                        if (!input.contains(e.target) && !suggestions.contains(e.target)) {
                            suggestions.classList.add('d-none');
                        }
                    });

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        if (input.value.trim().length >= 2) {
                            fetchData(input.value.trim());
                        }
                    });

                    async function fetchData(query) {
                        if (query.length < 2) {
                            suggestions.classList.add('d-none');
                            return;
                        }

                        showMessage(`<div class="d-flex justify-content-center align-items-center">
                                        <div class="spinner-border spinner-border-sm me-2 text-primary"></div>
                                        <span>Đang tìm...</span>
                                    </div>`);

                        let type = category.value;
                        let cacheKey = type + "_" + query;

                        if (cache[cacheKey]) {
                            renderSuggestions(cache[cacheKey], type);
                            return;
                        }

                        try {
                            let response = await fetch(`/search?q=${encodeURIComponent(query)}&type=${type}`);
                            let data = await response.json();
                            cache[cacheKey] = data;

                            localStorage.setItem("lastSearch", JSON.stringify({
                                type: type,
                                query: query,
                                data: data
                            }));

                            renderSuggestions(data, type);
                        } catch (e) {
                            console.error("Lỗi fetch:", e);
                            showMessage("Có lỗi xảy ra, vui lòng thử lại");
                        }
                    }

                    function renderSuggestions(data, type) {
                        suggestions.innerHTML = "";
                        if (data.length > 0) {
                            data.forEach(item => {
                                let li = document.createElement('li');
                                li.className = "list-group-item list-group-item-action d-flex align-items-center";
                                li.style.cursor = "pointer";

                                if (type === 'account') {
                                    let avatar = document.createElement('img');
                                    avatar.src = item.avatar_url ? item.avatar_url : defaultAvatar;
                                    avatar.className = "rounded-circle me-2";
                                    avatar.style.width = "32px";
                                    avatar.style.height = "32px";
                                    avatar.style.objectFit = "cover";

                                    let text = document.createElement('span');
                                    text.textContent = `${item.username} (${item.fullname ?? ''})`;

                                    li.appendChild(avatar);
                                    li.appendChild(text);

                                    li.onclick = () => window.location.href = `/portfolios/${item.username}`;
                                } else {
                                    let icon = document.createElement('i');
                                    icon.className = "bi bi-briefcase-fill me-2 text-primary";

                                    let text = document.createElement('span');
                                    text.textContent = item.title;

                                    li.appendChild(icon);
                                    li.appendChild(text);

                                    li.onclick = () => window.location.href = `/jobs/${item.id}`;
                                }

                                suggestions.appendChild(li);
                            });
                            suggestions.classList.remove('d-none');
                        } else {
                            showMessage("Không tìm thấy kết quả nào");
                        }
                    }

                    function showMessage(msg) {
                        suggestions.innerHTML = `<li class="list-group-item text-muted text-center py-2">${msg}</li>`;
                        suggestions.classList.remove('d-none');
                    }
                </script>

                @auth
                    {{-- ... dropdown người dùng của bạn giữ nguyên ... --}}
                @else {{-- <— THÊM NHÁNH NÀY (hoặc dùng @guest ... @endguest) --}} <li class="nav-item"><a
                        class="nav-link" href="{{ route('login') }}">Đăng nhập</a></li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-lg-2" href="{{ route('register.role.show') }}">Đăng ký</a>
                        {{-- nếu dự án không có route('register') thì dùng url('/register') hoặc ẩn nút --}}
                    </li>
                @endauth
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @php
                                    // Lấy user + quan hệ type
                                    $acc = Auth::user()->loadMissing('type');   // NEW
                                    $name = $acc->profile?->fullname ?: $acc->name ?: 'Tài khoản';
                                    $username = $acc->profile?->username;
                                    $handle = $username ? '@' . $username : null;
                                    $initials = collect(explode(' ', $name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                                    $avatar = $acc->avatar_url;
                                    $src = $avatar ? (preg_match('/^https?:\/\//', $avatar) ? $avatar : asset($avatar)) : null;
                                @endphp

                                @if($src)
                                    <img src="{{ $src }}" referrerpolicy="no-referrer" class="rounded-circle"
                                        style="width:28px;height:28px;object-fit:cover;">
                                @else
                                    <span class="avatar-circle">{{ $initials }}</span>
                                @endif

                                <span class="d-none d-lg-flex flex-column lh-sm text-start">
                                    <span class="fw-semibold">{{ $name }}</span>
                                    @if($handle)
                                        <span class="text-muted small">{{ $handle }}</span>
                                    @endif
                                </span>

                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow-sm p-2" style="min-width:230px;"
                                aria-labelledby="userDropdown">

                                {{-- HIỂN THỊ LOẠI TÀI KHOẢN (từ account_types) --}}
                                @php
                                    $typeName = $acc->type->name ?? 'Guest';
                                    $typeCode = $acc->type->code ?? 'GUEST';
                                @endphp
                                <li class="px-3 py-2 text-muted small d-flex align-items-center gap-2"> {{-- NEW --}}
                                    <i class="bi bi-patch-check"></i>
                                    Loại tài khoản:
                                    <span class="badge bg-light text-dark">{{ $typeName }}</span>
                                </li>
                                @if ($typeCode === 'F_BASIC' || $typeCode === 'CLIENT')
                                    <div class="border rounded-3 p-3 mb-3"
                                        style="border:1px solid #f8e8a0; background:linear-gradient(90deg, #fdfdfd, #fffef9);">
                                        <a href="{{ route('settings.upgrade') }}"
                                            class="d-flex justify-content-between align-items-center text-decoration-none">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 fw-semibold text-dark">
                                                    <i class="bi bi-gem text-warning"></i>
                                                    Nâng cấp gói Plus
                                                </div>
                                                <small class="text-muted">
                                                    Mở khóa thêm nhiều tính năng & ưu đãi. Không yêu cầu trả trước.
                                                </small>
                                            </div>
                                            <i class="bi bi-arrow-right fw-bold text-dark"></i>
                                        </a>
                                    </div>
                                @endif

                                <li>
                                    <hr class="dropdown-divider my-2">
                                </li> {{-- NEW --}}

                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                        href="{{ route('settings.myinfo') }}">
                                        <i class="bi bi-person-circle"></i> Cài đặt tài khoản
                                    </a>
                                </li>
                                @if ($typeCode === 'ADMIN')
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                            href="{{ route('admin.accounts.index') }}">
                                            <i class="bi bi-speedometer2"></i> Bảng điều khiển
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                            <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth

            </ul>
        </div>
    </div>
</nav>
{{-- Chừa khoảng trống dưới navbar fixed-top để nội dung không bị che --}}
<style>
    .avatar-circle {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #0d6efd;
        color: #fff;
        font-weight: 700;
        font-size: .85rem;
    }

    :root {
        --jl-nav-height: 64px;
    }

    body {
        padding-top: var(--jl-nav-height);
    }

    /* Nền mờ + viền nhẹ cho navbar */
    .navbar.fixed-top {
        backdrop-filter: saturate(180%) blur(12px);
        -webkit-backdrop-filter: saturate(180%) blur(12px);
        background: rgba(255, 255, 255, 0.78) !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    /* Link đẹp hơn khi hover/active */
    .navbar .nav-link {
        transition: color .15s ease, background-color .15s ease;
        border-radius: .5rem;
        padding: .5rem .75rem;
    }

    .navbar .nav-link:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }

    .navbar .nav-link.active {
        color: #0d6efd !important;
        background-color: rgba(13, 110, 253, .08);
    }

    /* Badge thương hiệu “JL” */
    .navbar-brand .rounded-3 {
        font-weight: 700;
    }

    /* Avatar tròn từ initials */
    .avatar-circle {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #0d6efd;
        color: #fff;
        font-weight: 700;
        font-size: .85rem;
    }

    /* Điều chỉnh chiều cao trên mobile (navbar cao hơn) */
    @media (max-width: 991.98px) {
        :root {
            --jl-nav-height: 70px;
        }
    }
</style>