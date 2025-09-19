<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng ký</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
      color: #6b7280;
      font-size: .9rem;
      background: transparent;
    }

    .password-toggle {
      cursor: pointer;
      user-select: none;
      color: #6b7280;
    }

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
      {{-- Left --}}
      <div class="auth-left col-lg-6 d-flex align-items-center p-4 p-lg-5">
        <div class="w-100">
          <div class="d-flex align-items-center gap-3 mb-4">
            <span class="brand-badge">JL</span>
            <div>
              <div class="h4 m-0 fw-bold">JobLink</div>
              <div class="text-muted small">Kết nối Freelancer &amp; Nhà tuyển dụng</div>
            </div>
          </div>
          <h1 class="h3 fw-semibold mb-3">Tạo tài khoản mới 🚀</h1>
          <p class="text-muted mb-4">Đăng ký để ứng tuyển, đăng dự án và quản lý công việc thuận tiện hơn.</p>
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
          </svg>
        </div>
      </div>

      {{-- Right --}}
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

          <h2 class="h5 fw-semibold mb-1">Đăng ký</h2>
          <p class="text-muted small mb-4">Chọn phương thức đăng ký của bạn</p>

          <div class="d-grid gap-2 mb-3">
            <a href="{{ route('google.redirect', ['role' => $role]) }}" class="btn btn-google ...">
              <i class="bi bi-google"></i> Đăng ký bằng Google
            </a>
            <a href="{{ route('github.redirect', ['role' => $role]) }}" class="btn btn-dark ...">
              <i class="bi bi-github"></i> Đăng ký bằng GitHub
            </a>

          </div>

          <div class="divider"><span>hoặc</span></div>
          @if(!empty($role))
            <div class="alert alert-info py-2 small mb-3">
              Vai trò đã chọn: <strong>{{ $role }}</strong>
              <a href="{{ route('register.role.show') }}" class="ms-2">Đổi</a>
            </div>
          @endif

          <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf
            <div class="mb-3">
              <label for="name" class="form-label">Họ và tên</label>
              <input id="name" name="name" type="text" class="form-control" placeholder="Nguyễn Văn A"
                value="{{ old('name') }}" required autofocus>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input id="email" name="email" type="email" class="form-control" placeholder="you@example.com"
                value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Mật khẩu</label>
              <div class="input-group">
                <input id="password" name="password" type="password" class="form-control" placeholder="••••••••"
                  required>
                <span class="input-group-text password-toggle" id="togglePwd" title="Hiện/ẩn mật khẩu">
                  <i class="bi bi-eye"></i>
                </span>
              </div>
            </div>
            <div class="mb-3">
              <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
              <input id="password_confirmation" name="password_confirmation" type="password" class="form-control"
                placeholder="••••••••" required>
            </div>
            <div class="d-grid mb-3">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i> Đăng ký
              </button>
            </div>
            <input type="hidden" name="role" value="{{ $role ?? old('role') }}">
          </form>

          <p class="text-center small text-muted mb-0">
            Đã có tài khoản?
            <a href="{{ route('login') }}" class="text-decoration-none">Đăng nhập</a>
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