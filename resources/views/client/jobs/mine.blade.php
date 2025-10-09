@extends('layouts.app')
@section('title', 'Công việc đã đăng')

@section('content')
  <div class="container" style="max-width: 1100px; margin-top: 60px; margin-bottom: 200px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h1 class="h4 mb-1">Công việc đã đăng</h1>
        <div class="text-muted small">Quản lý các job bạn đã đăng và xem ứng viên đã nộp.</div>
      </div>
      <a class="btn btn-primary" href="{{ route('client.jobs.choose') }}">
        <i class="bi bi-plus-circle me-1"></i> Đăng job
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($jobs->isEmpty())
      <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5">
          <div class="display-6 mb-2">🗂️</div>
          <h5 class="mb-1">Bạn chưa đăng công việc nào</h5>
          <p class="text-muted mb-3">Bắt đầu đăng job để nhận hồ sơ từ freelancer.</p>
          <a class="btn btn-primary" href="{{ route('client.jobs.choose') }}">
            <i class="bi bi-plus-circle me-1"></i> Đăng job đầu tiên
          </a>
        </div>
      </div>
    @else
      {{-- group id để điều khiển đóng/mở bằng JS --}}
      <div id="applicants-group" class="vstack gap-3">
        @foreach($jobs as $job)
          @php
  $statusMap = [
    'open' => ['label' => 'ĐANG MỞ', 'class' => 'success'],
    'pending' => ['label' => 'CHỜ DUYỆT', 'class' => 'warning'],
    'in_progress' => ['label' => 'ĐANG LÀM', 'class' => 'info'],
    'cancelled' => ['label' => 'ĐÃ HỦY', 'class' => 'secondary'],
    'closed' => ['label' => 'ĐÃ ĐÓNG', 'class' => 'dark'],
  ];
  $cfg = $statusMap[$job->status] ?? ['label' => strtoupper($job->status), 'class' => 'secondary'];

  // Thêm map escrow
  $escrowMap = [
    'pending'  => ['label' => 'CHƯA THANH TOÁN',     'class' => 'warning'],
    'funded'   => ['label' => 'ĐÃ THANH TOÁN',       'class' => 'primary'],
    'released' => ['label' => 'ĐÃ GIẢI NGÂN', 'class' => 'success'],
    'refunded' => ['label' => 'HOÀN TIỀN',    'class' => 'secondary'],
  ];
  $esc = $escrowMap[$job->escrow_status ?? 'pending'] ?? $escrowMap['pending'];

  $applyCount = $job->applicants_count
      ?? ($job->relationLoaded('applicants') ? $job->applicants->count() : 0);

  $countBadge = $applyCount > 0
      ? 'bg-primary-subtle text-primary border border-primary-subtle'
      : 'bg-secondary-subtle text-secondary border border-secondary-subtle';

  // Quyền xử lý ứng viên chỉ khi đã cọc
  $canManageApplicants = ($job->escrow_status === 'funded');
@endphp

          <div class="card shadow-sm border-0 job-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start gap-3">
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a class="fw-semibold text-decoration-none link-dark h5 mb-0"
                      href="{{ route('jobs.show', $job->job_id) }}">
                      {{ $job->title }}
                    </a>
                    <span class="badge rounded-pill bg-{{ $esc['class'] }}-subtle text-{{ $esc['class'] }} border border-{{ $esc['class'] }}-subtle">
                      {{ $esc['label'] }}
                    </span>
                    <span
                      class="badge rounded-pill bg-{{ $cfg['class'] }}-subtle text-{{ $cfg['class'] }} border border-{{ $cfg['class'] }}-subtle">
                      {{ $cfg['label'] }}
                    </span>
                    <span class="badge rounded-pill {{ $countBadge }}">
                      <i class="bi bi-people me-1"></i>
                      <strong>{{ $applyCount }}</strong> ứng viên
                    </span>
                  </div>

                  <div class="small text-muted mt-2">
                    <span class="me-3"><i class="bi bi-tag"></i> {{ $job->categoryRef->name ?? '—' }}</span>
                    <span class="me-3"><i class="bi bi-wallet2"></i> {{ $job->payment_type }}@if($job->budget) ·
                    ${{ number_format($job->budget, 2) }}@endif</span>
                    @if($job->deadline)
                      <span><i class="bi bi-calendar-event"></i>
                        {{ \Illuminate\Support\Carbon::parse($job->deadline)->toDateString() }}
                      </span>
                    @endif
                  </div>

                  <div class="text-truncate mt-2 text-secondary" style="max-width: 820px;">
                    {{ $job->description }}
                  </div>
                </div>
                <div class="text-end">
                  @if(($job->escrow_status ?? 'pending') === 'pending'&& $job->status !== 'cancelled')
  <form action="{{ route('job-payments.create', $job->job_id) }}" method="POST" class="d-inline">
    @csrf
    <button class="btn btn-sm btn-warning">
      <i class="bi bi-credit-card"></i> Thanh toán
    </button>
  </form>
@endif
@if($job->status === 'cancelled')
  <form action="{{ route('client.jobs.destroy', $job->job_id) }}" method="POST" class="d-inline"
        onsubmit="return confirm('Bạn có chắc muốn xóa công việc này?');">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-outline-danger">
      <i class="bi bi-trash"></i> Xóa
    </button>
  </form>
@endif

                  <a href="{{ route('jobs.show', $job->job_id) }}"
   class="btn btn-sm btn-outline-primary @if($job->status !== 'open') disabled-link @endif">
  <i class="bi bi-eye me-1"></i> Xem
</a>


                  {{-- Nút toggle ứng viên: KHÔNG dùng data-bs-toggle để tránh double toggle --}}
                  <button class="btn btn-sm btn-outline-secondary ms-1"
        type="button"
        data-bs-target="#applicants-{{ $job->job_id }}"
        data-collapse
        data-open-text="Ẩn ứng viên"
        data-close-text="Ứng viên"
        @if($job->status !== 'open') disabled @endif>
    <i class="bi bi-people me-1 icon"></i>
    <span class="label">Ứng viên</span>
</button>

                </div>
              </div>

              {{-- Danh sách ứng viên --}}
              <div class="collapse mt-3" id="applicants-{{ $job->job_id }}">
                @forelse(($job->applicants ?? collect()) as $acc)
                  @php
                    $pro = $acc->profile ?? null;
                    $name = $pro->fullname ?? $acc->name ?? 'Ứng viên';
                    $email = $acc->email ?? null;
                    $intro = $acc->pivot->introduction ?? null;
                    $apAt = $acc->pivot->created_at ?? null;
                    $st = (int) ($acc->pivot->status ?? 0);
                    $avatar = $acc->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=64';
                    $stBadge = match ($st) {
                      1 => ['Chờ duyệt', 'secondary'],
                      0 => ['Đã từ Chối', 'warning'],
                      default => ['Đã duyệt', 'success'],
                    };
                  @endphp

                  <div class="list-group-item px-0 border-top pt-2">
                    <div class="d-flex align-items-start gap-3">
                      <img src="{{ $avatar }}" class="rounded-circle" style="width:42px;height:42px;object-fit:cover"
                        alt="avatar">
                      <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <div class="fw-semibold">
                              {{ $name }}
                              <span
                                class="badge rounded-pill bg-{{ $stBadge[1] }}-subtle text-{{ $stBadge[1] }} border border-{{ $stBadge[1] }}-subtle ms-1">
                                {{ $stBadge[0] }}
                              </span>
                            </div>
                            @if($email)
                              <div class="text-muted small">{{ $email }}</div>
                            @endif
                          </div>
                          @if($apAt)
                            <div class="text-muted small text-nowrap">
                              {{ \Illuminate\Support\Carbon::parse($apAt)->diffForHumans() }}
                            </div>
                          @endif
                        </div>

                        @if($intro)
                          <div class="mt-2">{{ \Illuminate\Support\Str::limit($intro, 200) }}</div>
                        @endif

                        @if(!empty($pro?->skill))
  @php
    $skillNames = collect(explode(',', (string)$pro->skill))
      ->filter()
      ->map(fn($id) => $skillMap[(int)$id] ?? null)
      ->filter()
      ->implode(', ');
  @endphp
  @if($skillNames !== '')
    <div class="small text-muted mt-1">
      <i class="bi bi-tools"></i> Kỹ năng: {{ $skillNames }}
    </div>
  @endif
@endif

                      </div>
                    </div>
                  </div>
                  @php
  $st = (int) ($acc->pivot->status ?? 0);
// badge theo map mới
  $stBadge = match ($st) {
    2 => ['ĐÃ NHẬN', 'success'],
    1 => ['ĐANG XEM', 'warning'], // tuỳ bạn đặt tên
    0 => ['TỪ CHỐI', 'danger'],
    default => ['KHÁC', 'secondary'],
  };
@endphp

<div class="text-end">
  {{-- Chỉ hiện nút khi chưa được nhận --}}
{{-- Chỉ hiện nút khi ĐÃ CỌC và ứng viên chưa được nhận --}}
@if($canManageApplicants && $st !== 2 && $st != 0)
  <form class="d-inline" method="POST"
        action="{{ route('client.jobs.applications.update', ['job_id' => $job->job_id, 'user_id' => $acc->account_id]) }}">
    @csrf @method('PATCH')
    <input type="hidden" name="status" value="2">
    <button class="btn btn-sm btn-success">
      <i class="bi bi-check2"></i> Chấp nhận
    </button>
  </form>
@elseif(!$canManageApplicants && $st !== 2 && $st != 0)
  {{-- Nếu chưa cọc: hiện nút mờ/tooltip (không submit) --}}
  <button class="btn btn-sm btn-success" disabled
          data-bs-toggle="tooltip" title="Cần thanh toán cọc trước khi xác nhận ứng viên">
    <i class="bi bi-check2"></i> Chấp nhận
  </button>
@endif

{{-- Nút từ chối: tùy bạn có muốn cho phép khi chưa cọc không.
    Nếu CHƯA CỌC mà vẫn cho từ chối thì giữ nguyên $st != 0 && $st != 2
    Còn nếu cũng chặn, dùng $canManageApplicants giống như trên.
--}}
@if($canManageApplicants && $st !== 0 && $st != 2)
  <form class="d-inline ms-1" method="POST"
        action="{{ route('client.jobs.applications.update', ['job_id' => $job->job_id, 'user_id' => $acc->account_id]) }}"
        onsubmit="return confirm('Từ chối ứng viên này?');">
    @csrf @method('PATCH')
    <input type="hidden" name="status" value="0">
    <button class="btn btn-sm btn-outline-danger">
      <i class="bi bi-x"></i> Từ chối
    </button>
  </form>
@elseif(!$canManageApplicants && $st !== 0 && $st != 2)
  <button class="btn btn-sm btn-outline-danger ms-1" disabled
          data-bs-toggle="tooltip" title="Cần thanh toán cọc trước khi thao tác với ứng viên">
    <i class="bi bi-x"></i> Từ chối
  </button>
@endif

</div>

                @empty
                  <div class="text-muted">Chưa có ứng viên nào nộp cho job này.</div>
                @endforelse
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-3">
        {{ $jobs->links() }}
      </div>
    @endif
  </div>

  <style>
    .job-card {
      transition: box-shadow .2s, transform .05s;
    }

    .job-card:hover {
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
    }
    .disabled-link {
  pointer-events: none; /* Không cho click */
  opacity: 0.5;         /* Làm mờ nút */
  cursor: not-allowed;  /* Đổi icon chuột */
}

  </style>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.bootstrap) return;

      const group = document.getElementById('applicants-group');

      // Khởi tạo và gắn toggle cho tất cả nút
      document.querySelectorAll('[data-collapse]').forEach(function (btn) {
        const targetSel = btn.getAttribute('data-bs-target');
        const target = document.querySelector(targetSel);
        if (!target) return;

        const inst = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
        const icon = btn.querySelector('.icon');
        const label = btn.querySelector('.label');
        const openText = btn.getAttribute('data-open-text') || 'Ẩn ứng viên';
        const closeText = btn.getAttribute('data-close-text') || 'Ứng viên';

        // Click => toggle
        btn.addEventListener('click', function () {
          inst.toggle();
        });

        // Khi chuẩn bị mở: đóng các khối khác
        target.addEventListener('show.bs.collapse', function () {
          if (!group) return;
          group.querySelectorAll('.collapse.show').forEach(function (el) {
            if (el !== target) {
              bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
            }
          });
        });

        // Cập nhật UI khi đã mở
        target.addEventListener('shown.bs.collapse', function () {
          btn.classList.remove('btn-outline-secondary');
          btn.classList.add('btn-secondary');
          if (icon) icon.className = 'bi bi-chevron-up me-1 icon';
          if (label) label.textContent = openText;
        });

        // Cập nhật UI khi đã đóng
        target.addEventListener('hidden.bs.collapse', function () {
          btn.classList.remove('btn-secondary');
          btn.classList.add('btn-outline-secondary');
          if (icon) icon.className = 'bi bi-people me-1 icon';
          if (label) label.textContent = closeText;
        });

        // Đặt trạng thái UI ban đầu theo tình trạng .show
        if (target.classList.contains('show')) {
          btn.classList.remove('btn-outline-secondary');
          btn.classList.add('btn-secondary');
          if (icon) icon.className = 'bi bi-chevron-up me-1 icon';
          if (label) label.textContent = openText;
        } else {
          btn.classList.remove('btn-secondary');
          btn.classList.add('btn-outline-secondary');
          if (icon) icon.className = 'bi bi-people me-1 icon';
          if (label) label.textContent = closeText;
        }
      });
    });
  </script>
@endpush