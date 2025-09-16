<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tell us your name • JobLink</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{min-height:100vh;display:flex;align-items:center;background:#fff}
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
      <div class="text-center mb-4">
        <h1 class="fw-semibold">Set up your profile</h1>
        <p class="text-muted mb-0">First, what should we call you?</p>
      </div>

      @if ($errors->any())
        <div class="alert alert-danger small">
          <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif

      <form method="POST" action="{{ route('onb.name.store') }}" class="card shadow-sm p-4">
        @csrf
        <div class="mb-3">
          <label class="form-label">Full name</label>
          <input type="text" name="fullname" class="form-control form-control-lg"
                 value="{{ old('fullname', $profile->fullname ?? $user->name) }}" required autofocus>
        </div>
        <div class="d-grid">
          <button class="btn btn-dark btn-lg">Continue</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
