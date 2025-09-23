<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Set up your profile • JobLink</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{min-height:100vh;background:#fff}
    .hero{min-height:calc(100vh - 80px)}
    .role-card{
      border:1px solid #e5e7eb; border-radius:16px; transition:.2s;
      box-shadow:0 1px 2px rgba(0,0,0,.04);
    }
    .role-card:hover{
      transform:translateY(-2px);
      box-shadow:0 12px 28px rgba(0,0,0,.08);
      border-color:#d1d5db;
    }
    .role-img{
      border-top-left-radius:14px; border-top-right-radius:14px;
      aspect-ratio: 4 / 3; object-fit: cover; width:100%;
    }
    .role-title{font-size:1.25rem;font-weight:600;color:#111827}
    .role-desc{color:#6b7280}
    .btn-ghost{border:1px solid #e5e7eb;background:#fff}
    .btn-ghost:hover{background:#f9fafb}
  </style>
</head>
<body>
<div class="container hero d-flex flex-column justify-content-center align-items-center py-5">

  <h1 class="display-5 fw-semibold text-center mb-4">Set up your profile</h1>

  {{-- cảnh báo nếu thiếu loại tài khoản trong DB --}}
  @if (is_null($clientTypeId) || is_null($freelancerTypeId))
    <div class="alert alert-warning small">
      Thiếu cấu hình trong <code>account_types</code>. Hãy đảm bảo có bản ghi
      với <code>code='CLIENT'</code> và <code>code='F_BASIC'</code>.
    </div>
  @endif

  <div class="row g-4 mt-2" style="max-width:900px;">
    {{-- Client --}}
    <div class="col-12 col-md-6">
      <form method="POST" action="{{ route('role.store') }}">
        @csrf
        <input type="hidden" name="account_type_id" value="{{ $clientTypeId }}">
        <button type="submit" class="w-100 text-start role-card p-0 bg-white">
          <img class="role-img"
               src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=1200&auto=format&fit=crop"
               alt="Client">
          <div class="p-3 p-md-4">
            <div class="role-title">Client</div>
            <div class="role-desc">Get help with a project</div>
          </div>
        </button>
      </form>
    </div>

    {{-- Freelancer --}}
    <div class="col-12 col-md-6">
      <form method="POST" action="{{ route('role.store') }}">
        @csrf
        <input type="hidden" name="account_type_id" value="{{ $freelancerTypeId }}">
        <button type="submit" class="w-100 text-start role-card p-0 bg-white">
          <img class="role-img"
               src="https://images.unsplash.com/photo-1527980965255-d3b416303d12?q=80&w=1200&auto=format&fit=crop"
               alt="Freelancer">
          <div class="p-3 p-md-4">
            <div class="role-title">Freelancer</div>
            <div class="role-desc">Work and get paid</div>
          </div>
        </button>
      </form>
    </div>
  </div>


  @if ($errors->any())
    <div class="alert alert-danger mt-3 small">
      <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif
  @if (session('status'))
    <div class="alert alert-success mt-3 small">{{ session('status') }}</div>
  @endif

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
