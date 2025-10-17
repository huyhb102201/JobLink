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

    .progress {
      background-color: #f1f3f5;
      border-radius: 50px;
      overflow: hidden;
    }

    .toast-container {
      z-index: 9999;
    }
  </style>
</head>

<body>
  <div class="container auth-wrap">
    <div class="auth-shell row g-0 mx-auto" style="max-width: 1000px;">
      {{-- LEFT --}}
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

      {{-- RIGHT --}}
      <div class="col-lg-6 bg-white">
        <div class="p-4 p-lg-5">
          <h2 class="h5 fw-semibold mb-1">Đăng ký</h2>
          <p class="text-muted small mb-4">Chọn phương thức đăng ký của bạn</p>

          <div class="d-grid gap-2 mb-3">
            <a href="{{ route('google.redirect', ['role' => $role]) }}" class="btn btn-google">
              <i class="bi bi-google"></i> Đăng ký bằng Google
            </a>
            <a href="{{ route('github.redirect', ['role' => $role]) }}" class="btn btn-dark">
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
              <label for="username" class="form-label">Username</label>
              <div class="input-group">
                <span class="input-group-text">@</span>
                <input id="username" name="username" type="text" class="form-control" placeholder="hogiahuy"
                  value="{{ old('username') }}" required>
                <button type="button" class="btn btn-outline-secondary" id="autoUsername" title="Tạo tự động" disabled>
                  <i class="bi bi-stars"></i>
                </button>
              </div>
              <div class="form-text">
                Tự động sinh từ email + ký tự ngẫu nhiên (chữ và số).
              </div>
            </div>


            {{-- PASSWORD FIELD --}}
            <div class="mb-3">
              <label for="password" class="form-label">Mật khẩu</label>
              <div class="input-group">
                <input id="password" name="password" type="password" class="form-control" placeholder="••••••••"
                  required>
                <span class="input-group-text password-toggle" id="togglePwd" title="Hiện/ẩn mật khẩu">
                  <i class="bi bi-eye"></i>
                </span>
              </div>
              <div class="progress mt-2" style="height:6px;">
                <div id="pwdMeterBar" class="progress-bar" role="progressbar"></div>
              </div>
              <small id="pwdStrengthText" class="text-muted small"></small>
            </div>

            {{-- CONFIRM PASSWORD FIELD --}}
            <div class="mb-3">
              <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
              <div class="input-group">
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control"
                  placeholder="••••••••" required>
                <span class="input-group-text password-toggle" id="toggleConfirmPwd" title="Hiện/ẩn mật khẩu">
                  <i class="bi bi-eye"></i>
                </span>
              </div>
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
      <a href="{{ route('legal.terms') }}" class="text-decoration-none">Điều khoản</a> &
      <a href="{{ route('legal.privacy') }}" class="text-decoration-none">Chính sách</a> của JobLink.
    </p>
  </div>

  {{-- TOAST CONTAINER --}}
  <div class="toast-container position-fixed top-0 end-0 p-3">
    @if (session('status'))
      <div class="toast align-items-center text-bg-success border-0 show mb-2" role="alert">
        <div class="d-flex">
          <div class="toast-body">✅ {{ session('status') }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    @endif

    @if ($errors->any())
      @foreach ($errors->all() as $error)
        <div class="toast align-items-center text-bg-warning border-0 show mb-2" role="alert">
          <div class="d-flex">
            <div class="toast-body">⚠️ {{ $error }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
          </div>
        </div>
      @endforeach
    @endif
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      // === TOGGLE PASSWORD ===
      const toggle = (inputId, toggleId) => {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);
        if (input && toggle) {
          toggle.addEventListener("click", () => {
            const show = input.type === "password";
            input.type = show ? "text" : "password";
            toggle.querySelector("i").className = show ? "bi bi-eye-slash" : "bi bi-eye";
          });
        }
      };
      toggle("password", "togglePwd");
      toggle("password_confirmation", "toggleConfirmPwd");

      // === PASSWORD STRENGTH ===
      const pwd = document.getElementById("password");
      const bar = document.getElementById("pwdMeterBar");
      const text = document.getElementById("pwdStrengthText");
      pwd.addEventListener("input", () => {
        const val = pwd.value.trim();
        if (!val) {
          bar.style.width = "0%";
          bar.style.backgroundColor = "#f1f3f5";
          text.textContent = "";
          return;
        }

        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const states = [
          { label: "Yếu", color: "#dc3545", width: "25%" },
          { label: "Trung bình", color: "#ffc107", width: "50%" },
          { label: "Mạnh", color: "#0d6efd", width: "75%" },
          { label: "Rất mạnh", color: "#198754", width: "100%" }
        ];

        const s = states[Math.max(0, score - 1)];
        bar.style.width = s.width;
        bar.style.backgroundColor = s.color;
      });

      // === TOAST AUTO ===
      document.querySelectorAll('.toast').forEach(el => new bootstrap.Toast(el, { delay: 4000 }).show());

      // === AUTO GENERATE USERNAME ===
      const emailInput = document.getElementById("email");
      const usernameInput = document.getElementById("username");
      const autoBtn = document.getElementById("autoUsername");

      // Hàm kiểm tra email hợp lệ
      function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      }

      // Cập nhật trạng thái nút tự động
      function updateAutoBtnState() {
        const emailVal = emailInput.value.trim();
        autoBtn.disabled = !isValidEmail(emailVal);
      }

      // Theo dõi khi người dùng nhập email
      if (emailInput && autoBtn) {
        emailInput.addEventListener("input", updateAutoBtnState);
        updateAutoBtnState();
      }

      if (autoBtn && emailInput && usernameInput) {
        autoBtn.addEventListener("click", () => {
          const emailVal = emailInput.value.trim();
          if (!isValidEmail(emailVal)) return;

          // Lấy phần trước @ và lọc ký tự hợp lệ
          const base = emailVal.split("@")[0].replace(/[^a-zA-Z0-9._]/g, "").toLowerCase();

          // Tạo chuỗi ngẫu nhiên gồm chữ + số (3–4 ký tự)
          const chars = "abcdefghijklmnopqrstuvwxyz0123456789";
          let randomStr = "";
          for (let i = 0; i < 4; i++) {
            randomStr += chars.charAt(Math.floor(Math.random() * chars.length));
          }

          const generated = `${base}_${randomStr}`;
          usernameInput.value = generated;
          usernameInput.classList.add("is-valid");

          // Hiệu ứng highlight mượt
          usernameInput.animate(
            [{ backgroundColor: "#e8f4ff" }, { backgroundColor: "white" }],
            { duration: 600, fill: "forwards" }
          );
        });
      }

    });
  </script>
</body>

</html>