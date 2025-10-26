<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Thanh toán thất bại ❌</title>

  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <style>
    html, body { height: 100%; margin: 0; font-family: "Segoe UI", system-ui, sans-serif; }
    /* Nền đỏ nhạt phủ full màn */
    .fail-bg{
      min-height: 100vh; display:flex; align-items:center; justify-content:center;
      background:
        radial-gradient(1200px 600px at 10% -10%, #ffe8ea 0%, transparent 60%),
        radial-gradient(1200px 600px at 110% 110%, #ffe8ea 0%, transparent 60%),
        linear-gradient(180deg,#ffe2e5 0%,#ffd8db 50%,#ffcfd3 100%);
    }
    .card{ border:none; border-radius:20px; box-shadow:0 6px 20px rgba(0,0,0,.08); max-width:720px; width:100%; }
    .stripe-bar{ height:8px; background:repeating-linear-gradient(90deg,#ef4444 0 12px,#dc2626 12px 24px,#f87171 24px 36px); }
    .progress{ height:8px; border-radius:5px; overflow:hidden; }
  </style>
</head>
<body>

<div class="fail-bg">
  <div class="card text-center">
    <div class="stripe-bar"></div>
    <div class="card-body p-4 p-md-5">
      <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 mb-3"
           style="width:86px;height:86px;">
        <i class="bi bi-x-circle-fill text-danger" style="font-size:3rem;"></i>
      </div>

      <h2 class="mb-2 fw-bold text-danger">Thanh toán thất bại ❌</h2>
      <p class="text-secondary mb-4">Giao dịch đã bị hủy hoặc có lỗi xảy ra.</p>

      <div class="mb-3">
        <span class="text-muted">Tự động quay về trang chủ sau</span>
        <span class="badge bg-danger-subtle text-danger border border-danger ms-1"
              id="count-badge" style="font-size:.95rem;">5s</span>
      </div>

      <div class="progress mb-4">
        <div id="count-progress" class="progress-bar bg-danger progress-bar-striped progress-bar-animated"
             role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%"></div>
      </div>

      <div class="d-grid d-sm-inline-flex gap-2">
        <a href="{{ url('/') }}" id="home-btn" class="btn btn-danger rounded-pill px-4">
          <i class="bi bi-house-door me-1"></i> Về trang chủ (5s)
        </a>
        <a href="javascript:void(0)" class="btn btn-outline-secondary rounded-pill px-4"
           onclick="if(history.length>1){history.back()}else{window.location.href='{{ url('/') }}'}">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Thử lại
        </a>
      </div>

      <p class="small text-muted mt-3 mb-0">Nếu không tự chuyển, bạn có thể bấm “Về trang chủ”.</p>
    </div>
  </div>
</div>

<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
(function () {
  const total = 5; let remain = total;
  const badge = document.getElementById('count-badge');
  const btn   = document.getElementById('home-btn');
  const bar   = document.getElementById('count-progress');

  function updateUI(){
    badge.textContent = remain + 's';
    btn.innerHTML = '<i class="bi bi-house-door me-1"></i> Về trang chủ (' + remain + 's)';
    const percent = Math.min(100, Math.round(((total - remain)/total)*100));
    bar.style.width = percent + '%'; bar.setAttribute('aria-valuenow', percent);
  }
  updateUI();

  const tick = setInterval(function(){
    remain--; updateUI();
    if(remain<=0){ clearInterval(tick); window.location.href = "{{ url('/') }}"; }
  },1000);
})();
</script>
</body>
</html>
