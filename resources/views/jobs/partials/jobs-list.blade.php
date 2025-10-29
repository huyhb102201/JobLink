<div id="jobs-list" class="row g-3">
    @forelse($jobs as $job)
        <div class="col-12">
            <div class="card shadow-sm h-100 hover-shadow position-relative">
                <!-- Badge trạng thái góc trên bên phải -->
                <span class="position-absolute top-0 end-0 m-2 px-3 py-1 rounded-pill text-white fw-bold small shadow-sm
                    @switch($job->status)
                        @case('open') bg-primary @break
                        @case('in_progress') bg-warning text-dark @break
                        @case('completed') bg-success @break
                        @default bg-secondary @endswitch">
                    @switch($job->status)
                        @case('open') Đang tuyển @break
                        @case('in_progress') Đang làm @break
                        @case('completed') Hoàn thành @break
                        @default Không xác định
                    @endswitch
                </span>

                <div class="row g-0">
                    <!-- Ảnh job -->
                    <div class="col-md-4 d-flex justify-content-center align-items-center p-2">
                        <img src="{{ optional($job->jobCategory)->img_url ?? 'https://res.cloudinary.com/dkviqml2h/image/upload/v1761275988/messages/image.png' }}"
                            alt="{{ optional($job->jobCategory)->name ?? 'Others' }}"
                            class="img-fluid rounded">
                    </div>
                    <!-- Thông tin job -->
                    <div class="col-md-8">
                        <div class="card-body d-flex flex-column h-100">
                           <h5 class="card-title">
                                <a href="{{ route('jobs.show', $job->job_id) }}" class="mb-0 fw-bold">
                                    {{ $job->title }}
                                </a>
                            </h5>

                            <p class="text-muted mb-2"><i class="bi bi-tags me-1"></i>{{ $job->jobCategory->name ?? 'Khác' }}</p>
                            <p class="mb-2 fs-5"><strong>{{ number_format($job->salary ?? $job->budget, 0, ',', '.') }} VNĐ</strong> / {{ $job->payment_type ?? 'tháng' }}</p>
                            <p class="text-truncate mb-2">{{ Str::limit($job->description, 120) }}</p>

                            <!-- Người đăng -->
                            <div class="d-flex align-items-center mb-2">
                                <img src="{{ optional($job->account)->avatar_url ?? 'https://res.cloudinary.com/dkviqml2h/image/upload/v1761276631/messages/defaultavatar.jpg' }}"
                                    alt="{{ optional($job->account)->name ?? 'Người đăng' }}"
                                    class="rounded-circle me-2"
                                    width="40" height="40">
                                <div>
                                    <p class="mb-0 fw-bold">
                                        @if($job->account?->profile?->username)
                                            <a href="{{ route('portfolios.show', $job->account->profile->username) }}">
                                                {{ $job->account->name }}
                                            </a>
                                        @else
                                            {{ $job->account?->name ?? 'Người đăng ẩn danh' }}
                                        @endif
                                    </p>
                                    <p class="mb-0 text-muted"><time datetime="{{ $job->created_at }}">Đăng ngày {{ $job->created_at->format('h:i:s A d/m/Y') }}</time></p>
                                </div>
                            </div>

                            <!-- Nút chi tiết -->
                            <div class="mt-3 d-flex justify-content-between align-items-center">
                            <a href="{{ route('jobs.show', $job->job_id) }}" class="btn btn-sm text-white"
                                style="background-color: #0ea2bd; border-color: #0ea2bd;">Xem chi tiết</a>

                            {{-- Nút Yêu thích --}}
                            <button
                                class="btn p-0 text-danger fs-5 d-flex align-items-center justify-content-center btn-fav"
                                type="button"
                                data-job-id="{{ $job->job_id }}"
                                aria-pressed="{{ !empty($job->is_favorited) ? 'true' : 'false' }}"
                                title="{{ !empty($job->is_favorited) ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' }}">
                                <i class="bi {{ !empty($job->is_favorited) ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                            </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-warning text-center">
                Không có dữ liệu phù hợp.
            </div>
        </div>
    @endforelse
</div>
<script>
(function () {
  const list = document.getElementById('jobs-list');
  if (!list) return;

  const CSRF = '{{ csrf_token() }}';

  list.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.btn-fav');
    if (!btn || !list.contains(btn)) return;

    const jobId = btn.dataset.jobId;
    const icon  = btn.querySelector('i');
    const wasFavorited = btn.getAttribute('aria-pressed') === 'true';

    // Optimistic UI: đổi icon trước
    setHeart(icon, !wasFavorited);
    btn.setAttribute('aria-pressed', (!wasFavorited).toString());
    btn.title = (!wasFavorited) ? 'Bỏ yêu thích' : 'Thêm vào yêu thích';
    btn.disabled = true;

    try {
      const res = await fetch(`{{ url('/jobs') }}/${jobId}/favorite`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF,
          'Accept': 'application/json'
        }
      });

      if (res.status === 401) {
        // Chưa đăng nhập → chuyển đến trang đăng nhập
        const back = encodeURIComponent(location.href);
        window.location.href = `/login?redirect=${back}`;
        return;
      }

      if (!res.ok) throw new Error('Network');

      const data = await res.json();
      const shouldBeFavorited = (data.status === 'added');
      setHeart(icon, shouldBeFavorited);
      btn.setAttribute('aria-pressed', shouldBeFavorited.toString());
      btn.title = shouldBeFavorited ? 'Bỏ yêu thích' : 'Thêm vào yêu thích';
    } catch (e) {
      // Hoàn tác nếu lỗi
      setHeart(icon, wasFavorited);
      btn.setAttribute('aria-pressed', wasFavorited.toString());
      btn.title = wasFavorited ? 'Bỏ yêu thích' : 'Thêm vào yêu thích';
      // Thông báo nhẹ
      alert('Không thể cập nhật yêu thích. Vui lòng thử lại.');
    } finally {
      btn.disabled = false;
    }
  });

  function setHeart(icon, active) {
    if (!icon) return;
    if (active) {
      icon.classList.add('bi-heart-fill');
      icon.classList.remove('bi-heart');
    } else {
      icon.classList.add('bi-heart');
      icon.classList.remove('bi-heart-fill');
    }
  }
})();
</script>
