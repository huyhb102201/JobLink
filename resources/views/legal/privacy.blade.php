<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Chính sách quyền riêng tư · JobLink</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{background:#f9fafb;color:#212529;font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial}
    .legal-hero{background:linear-gradient(135deg,#f0f7ff,#ffffff)}
    .card{border-radius:16px;border:1px solid rgba(0,0,0,.08)}
    .toc a{color:#0d6efd;text-decoration:none} .toc a:hover{text-decoration:underline}
    .lead{color:#495057}
    .pill{border:1px solid rgba(0,0,0,.15);border-radius:999px;padding:.35rem .7rem;background:#fff}
  </style>
</head>
<body>
<header class="legal-hero py-5 border-bottom">
  <div class="container">
    <div class="d-flex align-items-center gap-3 mb-3">
      <span class="pill">JobLink</span>
      <span class="text-muted">Chính sách quyền riêng tư</span>
    </div>
    <h1 class="display-6 fw-semibold mb-2 text-primary">Chính sách quyền riêng tư</h1>
    <p class="lead">Chúng tôi tôn trọng quyền riêng tư của bạn và bảo vệ dữ liệu cá nhân theo các nguyên tắc dưới đây.</p>
  </div>
</header>

<main class="py-4">
  <div class="container">
    <div class="row g-4">
      <aside class="col-lg-4">
        <div class="card sticky-top" style="top:24px">
          <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-list-ul me-1"></i>Mục lục</h5>
            <ol class="toc ps-3 m-0 d-grid gap-2">
              <li><a href="#1">1. Phạm vi</a></li>
              <li><a href="#2">2. Dữ liệu thu thập</a></li>
              <li><a href="#3">3. Mục đích sử dụng</a></li>
              <li><a href="#4">4. Chia sẻ dữ liệu</a></li>
              <li><a href="#5">5. Bảo mật</a></li>
              <li><a href="#6">6. Quyền của người dùng</a></li>
              <li><a href="#7">7. Cập nhật</a></li>
              <li><a href="#8">8. Liên hệ</a></li>
            </ol>
            <hr>
            <a class="btn btn-primary w-100 mb-2" href="{{ route('legal.privacy') }}"><i class="bi bi-shield-check me-1"></i>Chính sách</a>
            <a class="btn btn-outline-primary w-100" href="{{ route('legal.terms') }}"><i class="bi bi-file-text me-1"></i>Điều khoản</a>
          </div>
        </div>
      </aside>

      <section class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <h2 id="1" class="h4 text-primary fw-semibold">1. Phạm vi</h2>
            <p>Chính sách này áp dụng cho mọi người dùng JobLink, bao gồm Freelancer, Client và Admin.</p>

            <h2 id="2" class="h4 text-primary fw-semibold mt-4">2. Dữ liệu thu thập</h2>
            <ul>
              <li>Thông tin tài khoản: tên, email, ảnh đại diện, loại gói (Basic/Plus/Business).</li>
              <li>Dữ liệu giao dịch: job, tin nhắn, thanh toán, lịch sử hoạt động.</li>
              <li>Dữ liệu kỹ thuật: địa chỉ IP, thiết bị, trình duyệt, cookie.</li>
            </ul>

            <h2 id="3" class="h4 text-primary fw-semibold mt-4">3. Mục đích sử dụng</h2>
            <p>Cung cấp dịch vụ, phòng chống gian lận, hỗ trợ người dùng và cải thiện trải nghiệm nền tảng.</p>

            <h2 id="4" class="h4 text-primary fw-semibold mt-4">4. Chia sẻ dữ liệu</h2>
            <p>Chúng tôi chỉ chia sẻ với đối tác thanh toán, lưu trữ hoặc cơ quan có thẩm quyền khi được yêu cầu hợp pháp.</p>

            <h2 id="5" class="h4 text-primary fw-semibold mt-4">5. Bảo mật</h2>
            <p>JobLink sử dụng mã hóa HTTPS, kiểm soát truy cập và sao lưu dữ liệu định kỳ để đảm bảo an toàn thông tin.</p>

            <h2 id="6" class="h4 text-primary fw-semibold mt-4">6. Quyền của người dùng</h2>
            <p>Bạn có quyền truy cập, chỉnh sửa, xoá dữ liệu hoặc yêu cầu cung cấp bản sao dữ liệu cá nhân.</p>

            <h2 id="7" class="h4 text-primary fw-semibold mt-4">7. Cập nhật</h2>
            <p>Chính sách có thể được cập nhật định kỳ. Phiên bản mới nhất luôn được đăng tại trang này.</p>

            <h2 id="8" class="h4 text-primary fw-semibold mt-4">8. Liên hệ</h2>
            <p>Email: <a href="mailto:privacy@joblink.example" class="link-primary">privacy@joblink.example</a></p>

            <hr>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">Phiên bản 1.0 · Cập nhật: 17/10/2025</small>
              <a href="{{ url('/') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-house me-1"></i>Trang chủ</a>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</main>
</body>
</html>
