<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'JobLink' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="{{ url('/') }}">JobLink</a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarJobLink" aria-controls="navbarJobLink" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarJobLink">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="#">Trang chủ</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Công việc</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Freelancer</a></li>
        </ul>

        <!-- Auth Buttons -->
        <ul class="navbar-nav ms-auto">
          @auth
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
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

  <main class="py-5">
    @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
