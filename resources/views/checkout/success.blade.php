<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Thanh toán thành công 🎉</title>

  {{-- CSS --}}
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <style>
    html, body {
      height: 100%;
      margin: 0;
      background: linear-gradient(180deg, #d2efe2 0%, #c8e4d7 50%, #b7d8c9 100%);
      font-family: "Segoe UI", system-ui, sans-serif;
    }

    .success-bg {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background:
        radial-gradient(1200px 600px at 10% -10%, #e7f9f1 0%, transparent 60%),
        radial-gradient(1200px 600px at 110% 110%, #e7f9f1 0%, transparent 60%);
    }

    .card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
      max-width: 720px;
      width: 100%;
    }

    .stripe-bar {
      height: 8px;
      background: repeating-linear-gradient(
        90deg,
        #22c55e 0 12px,
        #10b981 12px 24px,
        #34d399 24px 36px
      );
    }

    .progress {
      height: 8px;
      border-radius: 5px;
      overflow: hidden;
    }
  </style>
</head>
<body>

<div class="success-bg">
  <div class="card text-center">
    <div class="stripe-bar"></div>
    <div class="card-body p-4 p-md-5 position-relative">
      <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3"
           style="width:86px;height:86px;">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
      </div>

      <h2 class="mb-2 fw-bold text-success">Thanh toán thành công 🎉</h2>
      <p class="text-secondary mb-4">Cảm ơn bạn! Giao dịch đã được xử lý thành công.</p>

      <div class="mb-3">
        <span class="text-muted">Tự động quay về trang chủ sau</span>
        <span class="badge bg-success-subtle text-success border border-success ms-1"
              id="count-badge" style="font-size: .95rem;">5s</span>
      </div>

      <div class="progress mb-4">
        <div id="count-progress" class="progress-bar bg-success progress-bar-striped progress-bar-animated"
             role="progressbar" aria-label="Tiến trình đếm ngược" aria-valuemin="0" aria-valuemax="100"
             aria-valuenow="0" style="width: 0%"></div>
      </div>

      <div class="d-grid d-sm-inline-flex gap-2">
        <a href="{{ url('/') }}" id="home-btn" class="btn btn-success rounded-pill px-4">
          <i class="bi bi-house-door me-1"></i> Về trang chủ (5s)
        </a>
        <a href="javascript:void(0)" class="btn btn-outline-secondary rounded-pill px-4"
           onclick="if(history.length>1){history.back()}else{window.location.href='{{ url('/') }}'}">
          <i class="bi bi-arrow-left-circle me-1"></i> Quay lại
        </a>
      </div>

      <p class="small text-muted mt-3 mb-0">Nếu không tự chuyển, bạn có thể bấm “Về trang chủ”.</p>
    </div>
  </div>
</div>

<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
(function () {
  const total = 5;
  let remain = total;

  const badge = document.getElementById('count-badge');
  const btn   = document.getElementById('home-btn');
  const bar   = document.getElementById('count-progress');

  function updateUI() {
    badge.textContent = remain + 's';
    btn.innerHTML = '<i class="bi bi-house-door me-1"></i> Về trang chủ (' + remain + 's)';
    const percent = Math.min(100, Math.round(((total - remain) / total) * 100));
    bar.style.width = percent + '%';
    bar.setAttribute('aria-valuenow', percent);
  }

  updateUI();

  const tick = setInterval(function () {
    remain--;
    updateUI();
    if (remain <= 0) {
      clearInterval(tick);
      window.location.href = "{{ url('/') }}";
    }
  }, 1000);
})();
</script>
</body>
</html>
