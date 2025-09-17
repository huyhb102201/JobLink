<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="{{ url('/') }}">JobLink</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarJobLink"
            aria-controls="navbarJobLink" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarJobLink">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/jobs') }}">Công việc</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/freelancers') }}">Freelancer</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/contact') }}">Liên hệ</a></li>
            </ul>

            <!-- Auth Buttons -->
            <ul class="navbar-nav ms-auto">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown">
                            {{ Auth::user()->name ?? 'Tài khoản' }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">Hồ sơ</a></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Đăng nhập</a></li>
                    <li class="nav-item"><a class="btn btn-outline-primary ms-2" href="">Đăng ký</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>