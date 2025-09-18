<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --glass-bg: rgba(255, 255, 255, .72);
      --glass-bd: rgba(0, 0, 0, .06);
      --brand: #0d6efd;
    }

    body {
      min-height: 100vh;
      background:
        radial-gradient(1100px 600px at -10% 0%, #eaf2ff 0, #fff 55%),
        radial-gradient(900px 500px at 110% 100%, #f3f7ff 0, #fff 55%);
    }

    .auth-wrap {
      padding-block: 48px;
    }

    .auth-shell {
      border-radius: 22px;
      background: var(--glass-bg);
      border: 1px solid var(--glass-bd);
      box-shadow: 0 20px 60px rgba(13, 110, 253, .08);
      overflow: hidden;
    }

    .auth-left {
      background:
        radial-gradient(600px 400px at 20% 20%, rgba(13, 110, 253, .10), transparent 60%),
        radial-gradient(500px 300px at 80% 80%, rgba(99, 102, 241, .10), transparent 60%),
        #f8fafc;
    }

    .brand-badge {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      letter-spacing: .5px;
      box-shadow: 0 10px 22px rgba(79, 70, 229, .25);
    }

    .btn-google {
      background: #fff;
      border: 1px solid #e5e7eb;
      color: #111827;
    }

    .btn-google:hover {
      background: #f8fafc;
    }

    .btn-fb {
      background: #1877F2;
      color: #fff;
    }

    .divider {
      position: relative;
      text-align: center;
      margin: 1rem 0 1.25rem;
    }

    .divider::before {
      content: "";
      position: absolute;
      inset: auto 0 50% 0;
      height: 1px;
      background: #e5e7eb;
      transform: translateY(-50%);
    }

    .divider span {
      position: relative;
      padding: 0 .6rem;
      background: transparent;
      color: #6b7280;
      font-size: .9rem;
    }

    .form-control:focus {
      box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .12);
    }

    .is-invalid .form-control {
      border-color: #dc3545;
    }

    .password-toggle {
      cursor: pointer;
      user-select: none;
      color: #6b7280;
    }

    /* mobile */
    @media (max-width: 991.98px) {
      .auth-left {
        display: none;
      }

      .auth-wrap {
        padding-block: 32px;
      }
    }
  </style>
</head>

<body>
  <div class="container auth-wrap">
    <div class="auth-shell row g-0 mx-auto" style="max-width: 1000px;">
      {{-- Left panel --}}
      <div class="auth-left col-lg-6 d-flex align-items-center p-4 p-lg-5">
        <div class="w-100">
          <div class="d-flex align-items-center gap-3 mb-4">
            <span class="brand-badge">JL</span>
            <div>
              <div class="h4 m-0 fw-bold">JobLink</div>
              <div class="text-muted small">Kết nối Freelancer & Nhà tuyển dụng</div>
            </div>
          </div>

          <h1 class="h3 fw-semibold mb-3">Chào mừng bạn quay lại 👋</h1>
          <p class="text-muted mb-4">
            Đăng nhập để tiếp tục ứng tuyển, đăng dự án và quản lý công việc dễ dàng.
          </p>

          {{-- Simple vector illustration --}}
          <svg viewBox="0 0 600 260" width="100%" class="opacity-75">
            <defs>
              <linearGradient id="g1" x1="0" x2="1">
                <stop offset="0%" stop-color="#0ea5e9" />
                <stop offset="100%" stop-color="#4f46e5" />
              </linearGradient>
            </defs>
            <circle cx="100" cy="140" r="70" fill="url(#g1)" opacity=".15" />
            <rect x="180" y="70" rx="12" width="120" height="120" fill="url(#g1)" opacity=".15" />
            <rect x="330" y="90" rx="12" width="180" height="100" fill="#0d6efd" opacity=".08" />
            <g fill="#0d6efd" opacity=".18">
              <circle cx="370" cy="180" r="6" />
              <circle cx="420" cy="120" r="6" />
              <circle cx="480" cy="160" r="6" />
            </g>
          </svg>
        </div>
      </div>

      {{-- Right panel (form) --}}
      <div class="col-lg-6 bg-white">
        <div class="p-4 p-lg-5">

          {{-- Flash / errors --}}
          @if (session('status'))
            <div class="alert alert-success small">{{ session('status') }}</div>
          @endif
          @if ($errors->any())
            <div class="alert alert-danger small">
              <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <h2 class="h5 fw-semibold mb-1">Đăng nhập</h2>
          <p class="text-muted small mb-4">Chọn phương thức đăng nhập của bạn</p>

          {{-- Social --}}
          {{-- Social --}}
          <div class="d-grid gap-2 mb-3">
            <a href="{{ route('google.redirect') }}"
              class="btn btn-google d-flex align-items-center justify-content-center gap-2 py-2">
              <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107"
                  d="M43.6 20.5H42V20H24v8h11.3A12 12 0 1112 24a12 12 0 0118.5-9.7l5.7-5.7A20 20 0 1044 24c0-1.2-.1-2.3-.4-3.5z" />
                <path fill="#FF3D00"
                  d="M6.3 14.7l6.6 4.8A12 12 0 0124 12c3 0 5.7 1.1 7.8 2.9l5.8-5.8A20 20 0 006.3 14.7z" />
                <path fill="#4CAF50"
                  d="M24 44c5.1 0 9.7-1.9 13.2-5l-6.1-5a12 12 0 01-18.9-4.2l-6.6 5A20 20 0 0024 44z" />
                <path fill="#1976D2"
                  d="M43.6 20.5H42V20H24v8h11.3a12 12 0 01-4.2 5.7l6.1 5C40.4 36.1 44 30.5 44 24c0-1.2-.1-2.3-.4-3.5z" />
              </svg>
              Đăng nhập bằng Google
            </a>

            <a href="{{ route('github.redirect') }}"
              class="btn btn-dark d-flex align-items-center justify-content-center gap-2 py-2">
              {{-- GitHub icon --}}
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 .5C5.65.5.5 5.65.5 12a11.5 11.5 0 0 0 7.86 10.93c.58.1.79-.25.79-.56v-2.1c-3.2.7-3.88-1.54-3.88-1.54-.53-1.36-1.3-1.72-1.3-1.72-1.06-.73.08-.72.08-.72 1.18.08 1.8 1.21 1.8 1.21 1.04 1.8 2.74 1.28 3.41.98.1-.75.41-1.28.74-1.58-2.55-.29-5.23-1.28-5.23-5.69 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.47.11-3.06 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.8 0c2.21-1.49 3.18-1.18 3.18-1.18.63 1.59.23 2.77.11 3.06.74.81 1.19 1.84 1.19 3.1 0 4.42-2.69 5.39-5.25 5.67.42.36.8 1.07.8 2.16v3.19c0 .31.21.66.8.55A11.5 11.5 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5Z" />
              </svg>
              Đăng nhập bằng GitHub
            </a>
          </div>


          <div class="divider"><span>hoặc</span></div>

          {{-- Classic login --}}
          <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input id="email" name="email" type="email" class="form-control" placeholder="you@example.com" required
                autofocus>
            </div>

            <div class="mb-2">
              <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label mb-0">Mật khẩu</label>
                <a class="small text-decoration-none" href="">Quên mật khẩu?</a>
              </div>
              <div class="input-group">
                <input id="password" name="password" type="password" class="form-control" placeholder="••••••••"
                  required>
                <span class="input-group-text password-toggle" id="togglePwd" title="Hiện/ẩn mật khẩu">
                  <i class="bi bi-eye"></i>
                </span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
              </button>
            </div>
          </form>

          <p class="text-center small text-muted mb-0">
            Chưa có tài khoản?
            <a href="" class="text-decoration-none">Đăng ký ngay</a>
          </p>

        </div>
      </div>
    </div>

    <p class="text-center text-muted small mt-3">
      Bằng cách tiếp tục, bạn đồng ý với
      <a href="#" class="text-decoration-none">Điều khoản</a> &amp;
      <a href="#" class="text-decoration-none">Chính sách</a> của JobLink.
    </p>
  </div>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle show/hide password
    (function () {
      const input = document.getElementById('password');
      const btn = document.getElementById('togglePwd');
      if (btn && input) {
        btn.addEventListener('click', function () {
          const show = input.getAttribute('type') === 'password';
          input.setAttribute('type', show ? 'text' : 'password');
          this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
      }
    })();
  </script>
</body>

</html>