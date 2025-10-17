<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng nhập · JobLink</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />

  <style>
    :root {
      --brand: #0d6efd;
      --glass-bg: rgba(255, 255, 255, .72);
      --glass-bd: rgba(13, 110, 253, .10);
      --card-bd: rgba(0, 0, 0, .06);
      --muted: #6b7280;
      --ring: rgba(13, 110, 253, .18);
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
      background:
        radial-gradient(1400px 800px at -10% -10%, #eaf2ff 0, #ffffff 55%),
        radial-gradient(1000px 700px at 110% 110%, #f3f7ff 0, #ffffff 55%),
        linear-gradient(180deg, #ffffff 0%, #f6f8ff 100%);
      color: #0f172a;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    .auth-wrap {
      padding-block: 56px;
    }

    /* Shell */
    .auth-shell {
      border-radius: 24px;
      background: var(--glass-bg);
      backdrop-filter: saturate(160%) blur(16px);
      border: 1px solid var(--glass-bd);
      box-shadow: 0 30px 80px rgba(13, 110, 253, .10), 0 10px 30px rgba(2, 6, 23, .06);
      overflow: hidden;
      transition: transform .25s ease, box-shadow .25s ease;
    }

    .auth-shell:hover {
      transform: translateY(-2px);
      box-shadow: 0 36px 90px rgba(13, 110, 253, .12), 0 12px 36px rgba(2, 6, 23, .08);
    }

    /* Left panel */
    .auth-left {
      background:
        radial-gradient(600px 400px at 20% 20%, rgba(13, 110, 253, .10), transparent 60%),
        radial-gradient(500px 300px at 80% 80%, rgba(99, 102, 241, .12), transparent 60%),
        conic-gradient(from 180deg at 50% 50%, #f5f7ff, #eef2ff, #f5f7ff);
      position: relative;
      isolation: isolate;
    }

    .auth-left::after {
      content: "";
      position: absolute;
      inset: 10% -20% -20% -10%;
      background: radial-gradient(400px 200px at 70% 20%, rgba(14, 165, 233, .15), transparent 60%);
      filter: blur(30px);
      z-index: -1;
    }

    .brand-badge {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      letter-spacing: .5px;
      box-shadow: 0 14px 28px rgba(79, 70, 229, .28);
    }

    /* Right panel */
    .form-title {
      letter-spacing: .2px;
    }

    .text-muted {
      color: var(--muted) !important;
    }

    .btn-google {
      background: #fff;
      border: 1px solid #e5e7eb;
      color: #111827;
    }

    .btn-google:hover {
      background: #f8fafc;
      border-color: #e5e7eb;
    }

    .btn-github {
      background: #0f172a;
      color: #fff;
      border: 1px solid #0f172a;
    }

    .btn-github:hover {
      background: #111827;
      border-color: #111827;
    }

    .btn-primary {
      box-shadow: 0 8px 18px rgba(13, 110, 253, .18);
    }

    .btn-primary:hover {
      box-shadow: 0 10px 22px rgba(13, 110, 253, .22);
    }

    .divider {
      position: relative;
      text-align: center;
      margin: 1.25rem 0 1.5rem;
    }

    .divider::before {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      top: 50%;
      height: 1px;
      background: #e5e7eb;
      transform: translateY(-50%);
    }

    .divider span {
      position: relative;
      padding: 0 .75rem;
      background: transparent;
      color: var(--muted);
      font-size: .9rem;
    }

    .form-control {
      border-radius: 12px;
      padding: .7rem .9rem;
      border: 1px solid #e5e7eb;
      transition: border-color .2s ease, box-shadow .2s ease;
    }

    .form-control:focus {
      border-color: var(--brand);
      box-shadow: 0 0 0 .2rem var(--ring);
    }

    .input-group-text {
      border-radius: 12px;
    }

    .password-toggle {
      cursor: pointer;
      user-select: none;
      color: #6b7280;
      transition: color .2s ease;
    }

    .password-toggle:hover {
      color: #374151;
    }

    .small-link {
      color: var(--brand);
      text-decoration: none;
    }

    .small-link:hover {
      text-decoration: underline;
    }

    .list-errors {
      border: 1px dashed rgba(220, 53, 69, .35);
      background: rgba(220, 53, 69, .06);
    }

    /* Footer */
    .page-foot {
      color: #94a3b8;
    }

    /* Mobile */
    @media (max-width: 991.98px) {
      .auth-left {
        display: none;
      }

      .auth-wrap {
        padding-block: 28px;
      }
    }

    /* Dark mode */
    @media (prefers-color-scheme: dark) {
      body {
        background: radial-gradient(1200px 700px at -10% -10%, #0b1220 0, #0b1220 40%), radial-gradient(1000px 700px at 110% 110%, #0b1528 0, #0b1220 40%), #0b1220;
        color: #e5e7eb;
      }

      .auth-shell {
        background: rgba(14, 23, 42, .66);
        border-color: rgba(255, 255, 255, .06);
      }

      .auth-left {
        background: radial-gradient(600px 400px at 20% 20%, rgba(59, 130, 246, .12), transparent 60%), radial-gradient(500px 300px at 80% 80%, rgba(168, 85, 247, .12), transparent 60%), #0b1220;
      }

      .btn-google {
        background: rgba(255, 255, 255, .95);
        color: #0f172a;
      }

      .btn-github {
        background: #111827;
        border-color: #111827;
      }

      .divider::before {
        background: rgba(255, 255, 255, .08);
      }

      .form-control {
        background: rgba(255, 255, 255, .08);
        color: #e5e7eb;
        border-color: rgba(255, 255, 255, .12);
      }

      .input-group-text {
        background: rgba(255, 255, 255, .08);
        color: #cbd5e1;
        border-color: rgba(255, 255, 255, .12);
      }

      .list-errors {
        background: rgba(220, 53, 69, .08);
        border-color: rgba(220, 53, 69, .32);
      }
    }
  </style>
</head>

<body>
  <div class="container auth-wrap">
    <div class="auth-shell row g-0 mx-auto" style="max-width: 1024px;">
      <!-- Left panel -->
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
          <p class="text-muted mb-4">Đăng nhập để tiếp tục ứng tuyển, đăng dự án và quản lý công việc dễ dàng.</p>

          <!-- Simple vector illustration -->
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

      <!-- Right panel (form) -->
      <div class="col-lg-6 bg-white">
        <div class="p-4 p-lg-5">
          <!-- Flash / errors -->
          @if (session('status'))
            <div class="alert alert-success small mb-3"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}
            </div>
          @endif
          @if ($errors->any())
            <div class="alert alert-danger small list-errors mb-3">
              <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="d-flex align-items-baseline justify-content-between mb-1">
            <h2 class="h5 fw-semibold form-title mb-0">Đăng nhập</h2>
           <a href="{{ route('password.request') }}" class="small-link small">Quên mật khẩu?</a>
          </div>
          <p class="text-muted small mb-4">Chọn phương thức đăng nhập của bạn</p>

          <!-- Social -->
          <div class="d-grid gap-2 mb-3">
            <a href="{{ route('google.redirect') }}"
              class="btn btn-google d-flex align-items-center justify-content-center gap-2 py-2">
              <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true" role="img">
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
              class="btn btn-github d-flex align-items-center justify-content-center gap-2 py-2">
              <i class="bi bi-github"></i>
              Đăng nhập bằng GitHub
            </a>
          </div>

          <div class="divider"><span>hoặc</span></div>

          <!-- Classic login -->
          <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input id="email" name="email" type="email" class="form-control" placeholder="you@example.com" required
                autofocus>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Mật khẩu</label>
              <div class="input-group">
                <input id="password" name="password" type="password" class="form-control" placeholder="••••••••"
                  required>
                <span class="input-group-text password-toggle" id="togglePwd" title="Hiện/ẩn mật khẩu" role="button"
                  aria-label="Hiện/ẩn mật khẩu">
                  <i class="bi bi-eye"></i>
                </span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
              </div>
              <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btnSubmit">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Đăng nhập</span>
              </button>
            </div>
          </form>

          <p class="text-center small text-muted mb-0">
            Chưa có tài khoản?
            <a href="{{ route('register.role.show') }}" class="small-link">Đăng ký ngay</a>
          </p>
        </div>
      </div>
    </div>

    <p class="text-center page-foot small mt-3">
      Bằng cách tiếp tục, bạn đồng ý với
      <a href="{{ route('legal.terms') }}" class="small-link">Điều khoản</a> &amp;
      <a href="{{ route('legal.privacy') }}" class="small-link">Chính sách</a> của JobLink.
    </p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      // Toggle show/hide password
      const inputPwd = document.getElementById('password');
      const toggle = document.getElementById('togglePwd');
      if (toggle && inputPwd) {
        toggle.addEventListener('click', function () {
          const show = inputPwd.type === 'password';
          inputPwd.type = show ? 'text' : 'password';
          this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
      }

      // Submit loading
      const form = document.querySelector('form[action="{{ route('login') }}"]');
      const btn = document.getElementById('btnSubmit');
      if (form && btn) {
        form.addEventListener('submit', function () {
          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý';
        });
      }

      // Nếu backend flash locked_until_ts -> khóa nút + đếm ngược
      @if (session('locked_until_ts'))
        const unlockAt = {{ session('locked_until_ts') }} * 1000;
        if (btn) {
          btn.disabled = true;
          const orig = 'Đăng nhập';
          const timer = setInterval(() => {
            const now = Date.now();
            let sec = Math.max(0, Math.floor((unlockAt - now) / 1000));
            const mm = String(Math.floor(sec / 60)).padStart(2, '0');
            const ss = String(sec % 60).padStart(2, '0');
            btn.innerHTML = `<i class="bi bi-lock-fill me-2"></i>Khoá tạm (${mm}:${ss})`;
            if (sec <= 0) {
              clearInterval(timer);
              btn.disabled = false;
              btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> <span>' + orig + '</span>';
            }
          }, 1000);
        }
      @endif
})();
  </script>

</body>

</html>