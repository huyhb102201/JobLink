@extends('layouts.app')
@section('title', 'JobLink - Yêu thích')

@push('styles')
<style>
  .job-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,.06); }
  .job-meta .badge { font-weight: 500; }
  .skeleton {
    background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 37%, #e9ecef 63%);
    background-size: 400% 100%;
    animation: shimmer 1.2s infinite;
    border-radius: .5rem;
  }
  @keyframes shimmer { 0%{background-position:100% 0} 100%{background-position:-100% 0} }
  .btn-heart { border-radius: 999px; }
</style>
@endpush

@section('content')
<main class="main">
  <!-- Page Title -->
  <div class="page-title">
    <div class="container d-lg-flex justify-content-between align-items-center">
      <h1 class="mb-2 mb-lg-0">Công việc đã yêu thích</h1>
      <nav class="breadcrumbs">
        <ol>
          <li><a href="{{ route('home') }}">Trang chủ</a></li>
          <li class="current">Yêu thích</li>
        </ol>
      </nav>
    </div>
  </div>

  <!-- Filters -->
  <section class="section py-4">
    <div class="container">
      <form class="row g-2 align-items-center" method="get">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" name="q" class="form-control" placeholder="Tìm tiêu đề, mô tả…"
                   value="{{ $q ?? '' }}">
          </div>
        </div>
        <div class="col-md-3">
          <select class="form-select" name="payment">
            <option value="">Tất cả hình thức trả</option>
            <option value="fixed"  @selected(($payment ?? '')==='fixed')>Trọn gói (Fixed)</option>
            <option value="hourly" @selected(($payment ?? '')==='hourly')>Theo giờ (Hourly)</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Lọc</button>
          <a href="{{ route('favorites.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
      </form>
    </div>
  </section>

  <!-- List -->
  <section class="section pt-0">
    <div class="container">
      @if($favorites->count() === 0)
        <div class="text-center py-5">
          <div class="mb-3">
            <i class="bi bi-heart fs-1 text-secondary"></i>
          </div>
          <h5 class="mb-2">Chưa có công việc nào trong danh sách yêu thích</h5>
          <p class="text-muted mb-4">Hãy duyệt danh sách việc và nhấn <i class="bi bi-heart"></i> để lưu lại.</p>
          <a href="{{ route('home') }}" class="btn btn-primary"><i class="bi bi-briefcase me-1"></i>Khám phá công việc</a>
        </div>
      @else
        <div class="row g-4" id="favGrid">
          @foreach($favorites as $job)
          <div class="col-xl-4 col-lg-6 col-md-6 fav-item" id="fav-{{ $job->job_id }}">
            <div class="card job-card h-100 border-0 shadow-sm">
              <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                  <h5 class="card-title mb-2 flex-grow-1 pe-3">
                    <a href="{{ route('jobs.show', $job->job_id) }}" class="text-decoration-none">
                      {{ $job->title }}
                    </a>
                  </h5>
                  <button class="btn btn-light border btn-sm btn-heart favorite-btn"
                          data-job-id="{{ $job->job_id }}"
                          title="Bỏ yêu thích">
                    <i class="bi bi-heart-fill text-danger"></i>
                  </button>
                </div>

                <p class="text-muted small mb-3 line-clamp-3">{{ \Illuminate\Support\Str::limit(strip_tags($job->description ?? ''), 140) }}</p>

                <div class="job-meta d-flex flex-wrap gap-2 mb-3">
                  @if($job->payment_type === 'hourly')
                    <span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>Hourly</span>
                  @else
                    <span class="badge bg-light text-dark border"><i class="bi bi-cash-coin me-1"></i>Fixed</span>
                  @endif

                  @if(!is_null($job->budget_min) || !is_null($job->budget_max))
                    <span class="badge bg-primary-subtle text-primary border">
                      <i class="bi bi-currency-exchange me-1"></i>
                      {{ number_format((int)($job->budget_min ?? $job->budget_max)) }}
                      @if($job->budget_max) – {{ number_format((int)$job->budget_max) }} @endif VND
                    </span>
                  @endif

                  @if(!empty($job->location))
                    <span class="badge bg-light text-dark border"><i class="bi bi-geo-alt me-1"></i>{{ $job->location }}</span>
                  @endif

                  <span class="badge bg-light text-dark border">
                    <i class="bi bi-calendar-event me-1"></i>
                    {{ \Illuminate\Support\Carbon::parse($job->created_at)->diffForHumans() }}
                  </span>
                </div>

                @php
                  $skills = collect($job->skills ?? $job->skill_list ?? [])->filter()->take(5);
                @endphp
                @if($skills->count())
                  <div class="mb-3 d-flex flex-wrap gap-2">
                    @foreach($skills as $s)
                      <span class="badge rounded-pill text-bg-secondary">{{ is_array($s) ? ($s['name'] ?? '') : $s }}</span>
                    @endforeach
                  </div>
                @endif

                <div class="mt-auto d-flex justify-content-between align-items-center">
                  <a href="{{ route('jobs.show', $job->job_id) }}" class="btn btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>Xem chi tiết
                  </a>
                  <div class="text-muted small">
                    <i class="bi bi-people me-1"></i>
                    <span>{{ $job->applications_count ?? ($job->applications_count = ($job->applications_count ?? 0)) }}</span> ứng tuyển
                  </div>
                </div>
              </div>
            </div>
          </div>
          @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
          {{ $favorites->links('pagination::bootstrap-5') }}
        </div>
      @endif
    </div>
  </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.favorite-btn');
  if (!btn) return;

  const jobId = btn.dataset.jobId;
  const card  = document.getElementById('fav-' + jobId);
  const icon  = btn.querySelector('i');

  // Optimistic: đổi icon ngay
  icon.classList.remove('bi-heart-fill','text-danger');
  icon.classList.add('bi-heart');

  try {
    const res = await fetch(`{{ url('/jobs') }}/${jobId}/favorite`, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json'}
    });

    if (!res.ok) throw new Error('Network');

    const data = await res.json();
    if (data.status === 'removed') {
      // loại card khỏi lưới
      card?.classList.add('fade');
      setTimeout(() => card?.remove(), 150);
      // nếu hết item trên trang hiện tại, có thể reload để chuyển trang trước đó
      if (!document.querySelector('.fav-item')) {
        location.reload();
      }
    } else {
      // user vừa add lại từ nơi khác → cập nhật icon
      icon.classList.remove('bi-heart');
      icon.classList.add('bi-heart-fill','text-danger');
    }
  } catch (err) {
    // hoàn tác icon nếu fail
    icon.classList.remove('bi-heart');
    icon.classList.add('bi-heart-fill','text-danger');
    alert('Không thể cập nhật yêu thích. Vui lòng thử lại.');
  }
});
</script>
@endpush
