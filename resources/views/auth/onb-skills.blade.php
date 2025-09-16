<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Choose your skills • JobLink</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{min-height:100vh;background:#fff}
    .chips{user-select:none}
    .chip{
      border:1px solid #e5e7eb;border-radius:9999px;display:inline-flex;
      align-items:center;margin:.25rem;padding:.1rem;
    }
    .chip input{display:none}
    .chip > span{padding:.45rem .85rem;border-radius:9999px;color:#111827}
    .chip input:checked + span{
      background:#111827;color:#fff;border-color:#111827;
    }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="text-center mb-4">
        <h1 class="fw-semibold">Set up your profile</h1>
        <p class="text-muted">Pick a few skills and write a short intro</p>
      </div>

      <form method="POST" action="{{ route('onb.skills.store') }}" class="card shadow-sm p-4" autocomplete="off">
        @csrf

        <div class="mb-3">
          <div class="mb-2 fw-semibold">Skills</div>

          <div class="chips">
            @foreach ($skills as $s)
              @php $checked = in_array($s->id, $selected ?? []); @endphp
              <label class="chip">
                <input type="checkbox" name="skills[]" value="{{ $s->id }}" {{ $checked ? 'checked' : '' }}>
                <span>{{ $s->name }}</span>
              </label>
            @endforeach
          </div>

          <div class="form-text">Bạn có thể chọn nhiều kỹ năng.</div>
          @error('skills')   <div class="text-danger small">{{ $message }}</div> @enderror
          @error('skills.*') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Short description</label>
          <textarea class="form-control" name="description" rows="5"
            placeholder="Ví dụ: Tôi là lập trình viên Laravel 3 năm kinh nghiệm...">{{ old('description', $profile->description ?? '') }}</textarea>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-dark btn-lg">Finish</button>
        </div>

        @if ($errors->any())
          <div class="alert alert-danger mt-3 small">
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          </div>
        @endif
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
