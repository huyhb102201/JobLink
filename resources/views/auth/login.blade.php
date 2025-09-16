<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập • JobLink</title>

  {{-- Bootstrap 5 CSS (CDN) --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      min-height: 100vh;
      background: radial-gradient(1200px 600px at 10% 10%, #eef2ff 0, #fff 60%),
                  radial-gradient(1000px 500px at 90% 90%, #e6f0ff 0, #fff 60%);
    }
    .card-soft {
      backdrop-filter: saturate(1.2) blur(6px);
      border: 1px solid rgba(0,0,0,.05);
    }
    .btn-google {
      background: #fff; border:1px solid #e5e7eb; color:#111827;
    }
    .btn-google:hover { background:#f9fafb; }
    .btn-fb { background:#1877F2; color:#fff; }
    .brand-badge {
      width:44px;height:44px;border-radius:14px;background:#4f46e5;color:#fff;
      display:inline-flex;align-items:center;justify-content:center;font-weight:700;
      box-shadow:0 10px 20px rgba(79,70,229,.25);
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-5">
        <div class="text-center mb-4">
          <span class="brand-badge me-2">J</span>
          <span class="fs-3 fw-semibold align-middle">JobLink</span>
          <div class="text-muted mt-1">Kết nối Freelancer &amp; Nhà tuyển dụng</div>
        </div>

        <div class="card card-soft shadow-sm">
          <div class="card-body p-4 p-sm-5">

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

            <h1 class="h5 fw-semibold mb-2">Chào mừng trở lại</h1>
            <p class="text-muted small mb-4">Chọn phương thức đăng nhập của bạn</p>

            {{-- Social buttons --}}
            <div class="d-grid gap-2 mb-3">
              <a href="{{ route('google.redirect') }}" class="btn btn-google d-flex align-items-center justify-content-center gap-2 py-2">
                {{-- Google icon (SVG) --}}
                <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                  <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3A12 12 0 1112 24a12 12 0 0118.5-9.7l5.7-5.7A20 20 0 1044 24c0-1.2-.1-2.3-.4-3.5z"/>
                  <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8A12 12 0 0124 12c3 0 5.7 1.1 7.8 2.9l5.8-5.8A20 20 0 006.3 14.7z"/>
                  <path fill="#4CAF50" d="M24 44c5.1 0 9.7-1.9 13.2-5l-6.1-5a12 12 0 01-18.9-4.2l-6.6 5A20 20 0 0024 44z"/>
                  <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3a12 12 0 01-4.2 5.7l6.1 5C40.4 36.1 44 30.5 44 24c0-1.2-.1-2.3-.4-3.5z"/>
                </svg>
                <span class="fw-medium">Đăng nhập bằng Google</span>
              </a>

              <a href="{{ route('facebook.redirect') }}" class="btn btn-fb d-flex align-items-center justify-content-center gap-2 py-2">
                {{-- Facebook icon --}}
                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                  <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06C2 17.06 5.66 21.2 10.44 22v-7.03H7.9v-2.9h2.54V9.41c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.86h2.78l-.44 2.9h-2.34V22C18.34 21.2 22 17.06 22 12.06z"/>
                </svg>
                <span class="fw-medium">Đăng nhập bằng Facebook</span>
              </a>
            </div>

            {{-- Divider --}}
            <div class="position-relative my-3">
              <hr class="text-secondary">
              <div class="position-absolute top-50 start-50 translate-middle px-2 bg-white text-muted small">
                hoặc
              </div>
            </div>

            {{-- (Tuỳ chọn) Login truyền thống: dùng khi có route('login') --}}
            <form method="POST" action="{{ route('login') }}">
              @csrf
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" name="email" type="email" class="form-control" placeholder="you@example.com" required>
              </div>
              <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center">
                  <label for="password" class="form-label mb-0">Mật khẩu</label>
                  <a class="small text-decoration-none" href="">Quên mật khẩu?</a>
                </div>
                <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remember" id="remember">
                  <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>
                <button type="submit" class="btn btn-dark">Đăng nhập</button>
              </div>
            </form>

            <p class="text-center small text-muted mb-0">
              Chưa có tài khoản?
              <a href="" class="text-decoration-none">Đăng ký ngay</a>
            </p>
          </div>
        </div>

        <p class="text-center text-muted small mt-3">
          Bằng cách tiếp tục, bạn đồng ý với <a href="#" class="text-decoration-none">Điều khoản</a> &amp; <a href="#" class="text-decoration-none">Chính sách</a> của JobLink.
        </p>
      </div>
    </div>
  </div>

  {{-- Bootstrap 5 JS (CDN) --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
