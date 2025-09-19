<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chọn vai trò</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body{min-height:100vh;background:
      radial-gradient(1100px 600px at -10% 0%, #eaf2ff 0, #fff 55%),
      radial-gradient(900px 500px at 110% 100%, #f3f7ff 0, #fff 55%);}
    .card-role{transition:.2s; cursor:pointer;}
    .card-role:hover{transform:translateY(-3px); box-shadow:0 14px 34px rgba(13,110,253,.12)}
  </style>
</head>
<body>
<div class="container py-5">
  <div class="mx-auto" style="max-width: 820px;">
    <div class="text-center mb-4">
      <span class="badge text-bg-primary rounded-pill px-3 py-2">Bước 1/2</span>
      <h1 class="h3 fw-semibold mt-3">Chọn vai trò của bạn</h1>
      <p class="text-muted">Bạn có thể thay đổi sau trong cài đặt tài khoản.</p>
      @if ($errors->any())
        <div class="alert alert-danger small text-start d-inline-block mt-2">
          <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif
    </div>

    <form method="POST" action="{{ route('register.role.store') }}" id="roleForm">
      @csrf
      <input type="hidden" name="role" id="roleInput">
      <div class="row g-4">
        <div class="col-md-6">
          <div class="card card-role h-100" onclick="selectRole('CLIENT')">
            <div class="card-body p-4">
              <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-briefcase-fill fs-2 text-primary"></i>
                <div>
                  <div class="h5 mb-0">Client</div>
                  <small class="text-muted">Đăng dự án, thuê freelancer</small>
                </div>
              </div>
              <ul class="text-muted small mb-0 ps-3">
                <li>Đăng việc, quản lý ứng tuyển</li>
                <li>Quản lý hợp đồng & thanh toán</li>
              </ul>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-4 px-4">
              <button type="button" class="btn btn-outline-primary w-100" onclick="selectRole('CLIENT')">
                Chọn Client
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card card-role h-100" onclick="selectRole('F_BASIC')">
            <div class="card-body p-4">
              <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-person-workspace fs-2 text-success"></i>
                <div>
                  <div class="h5 mb-0">Freelancer</div>
                  <small class="text-muted">Tìm việc, gửi hồ sơ, làm dự án</small>
                </div>
              </div>
              <ul class="text-muted small mb-0 ps-3">
                <li>Tạo hồ sơ năng lực</li>
                <li>Ứng tuyển & theo dõi công việc</li>
              </ul>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-4 px-4">
              <button type="button" class="btn btn-outline-success w-100" onclick="selectRole('F_BASIC')">
                Chọn Freelancer
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

    <div class="text-center mt-4">
      <a href="{{ route('register.show') }}" class="small text-decoration-none">Bỏ qua (chọn sau)</a>
    </div>
  </div>
</div>

<script>
  function selectRole(role) {
    document.getElementById('roleInput').value = role;
    document.getElementById('roleForm').submit();
  }
</script>
</body>
</html>
