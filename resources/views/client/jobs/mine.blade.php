@extends('layouts.app')
@section('title', 'Công việc đã đăng')

@section('content')
  <main class="main">
    <!-- Page Title -->
    <div class="page-title">
      <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0">Danh sách công việc của tôi</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="current">Danh sách công việc</li>
          </ol>
        </nav>
      </div>
    </div>

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
                'completed' => ['label' => 'ĐÃ HOÀN THÀNH', 'class' => 'success'],
                'cancelled' => ['label' => 'ĐÃ HỦY', 'class' => 'secondary'],
                'closed' => ['label' => 'ĐÃ ĐÓNG', 'class' => 'dark'],
              ];
              $cfg = $statusMap[$job->status] ?? ['label' => strtoupper($job->status), 'class' => 'secondary'];

              $escrowMap = [
                'pending' => ['label' => 'CHƯA THANH TOÁN', 'class' => 'warning'],
                'funded' => ['label' => 'ĐÃ THANH TOÁN', 'class' => 'primary'],
                'released' => ['label' => 'ĐÃ GIẢI NGÂN', 'class' => 'success'],
                'refunded' => ['label' => 'HOÀN TIỀN', 'class' => 'secondary'],
              ];
              $esc = $escrowMap[$job->escrow_status ?? 'pending'] ?? $escrowMap['pending'];

              $applyCount = $job->applicants_count
                ?? ($job->relationLoaded('applicants') ? $job->applicants->count() : 0);

              $countBadge = $applyCount > 0
                ? 'bg-primary-subtle text-primary border border-primary-subtle'
                : 'bg-secondary-subtle text-secondary border border-secondary-subtle';

              $acceptedCount = collect($job->applicants ?? collect())
                ->filter(fn($u) => (int) ($u->pivot->status ?? 0) === 2)
                ->count();

              $quantity = (int) ($job->quantity ?? 1);
              $isFull = $acceptedCount >= $quantity;

              $canManageApplicants = ($job->escrow_status === 'funded');
              $canRelease = ($job->escrow_status === 'funded')
                && ($acceptedCount > 0)
                && ($job->status !== 'closed' && $job->status !== 'cancelled');

              $acceptedApplicants = collect(($job->applicants ?? collect()))
                ->filter(fn($u) => (int) ($u->pivot->status ?? -1) === 2)
                ->values();

              $firstAssigneeId = optional($acceptedApplicants->first())->account_id;
              $jobTasksMap = $tasksByJobAndUser[$job->job_id] ?? [];
              $firstUserTasks = $firstAssigneeId ? ($jobTasksMap[$firstAssigneeId] ?? []) : [];

              // slot còn lại cho phép duyệt
              $remain = max(0, (int) ($quantity - $acceptedCount));
            @endphp

            <div class="card shadow-sm border-0 job-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                  {{-- LEFT: Title + badges + meta + desc --}}
                  <div class="flex-grow-1">
                    {{-- Tiêu đề --}}
                    <a class="fw-semibold text-decoration-none link-dark h5 mb-1 d-inline-block"
                      href="{{ route('jobs.show', $job->job_id) }}">
                      {{ \Illuminate\Support\Str::limit(strip_tags($job->title), 60) }}
                    </a>

                    {{-- Dòng nhãn gọn --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap small">
                      <span
                        class="badge rounded-pill bg-{{ $esc['class'] }}-subtle text-{{ $esc['class'] }} border border-{{ $esc['class'] }}-subtle">
                        {{ $esc['label'] }}
                      </span>
                      <span
                        class="badge rounded-pill bg-{{ $cfg['class'] }}-subtle text-{{ $cfg['class'] }} border border-{{ $cfg['class'] }}-subtle">
                        {{ $cfg['label'] }}
                      </span>
                      <span class="badge rounded-pill {{ $countBadge }}">
                        <i class="bi bi-people me-1"></i>{{ $applyCount }} ứng viên
                      </span>
                      <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">
                        <i class="bi bi-person-check me-1"></i>{{ $acceptedCount }}/{{ $quantity }} đã nhận
                      </span>
                    </div>

                    {{-- Meta nhỏ --}}
                    <div class="text-muted mt-2 small">
                      <span class="me-3"><i class="bi bi-tag"></i> {{ $job->categoryRef->name ?? '—' }}</span>
                      <span class="me-3">
                        <i class="bi bi-wallet2"></i> {{ $job->payment_type }}
                        @if($job->total_budget)
                                    ${{ $job->total_budget == floor($job->total_budget)
                          ? number_format($job->total_budget, 0)
                          : rtrim(rtrim(number_format($job->total_budget, 2, '.', ','), '0'), '.') }}
                        @endif

                      </span>
                      @if($job->deadline)
                        <span><i class="bi bi-calendar-event"></i>
                          {{ \Illuminate\Support\Carbon::parse($job->deadline)->toDateString() }}</span>
                      @endif
                    </div>

                    {{-- Mô tả ngắn --}}
                    <div class="mt-2 text-secondary" style="max-width: 820px;">
                      {{ \Illuminate\Support\Str::limit(strip_tags($job->description), 50) }}
                    </div>
                  </div>

                  {{-- RIGHT: 1 nút Hành động (dropdown) --}}
                  <div class="text-end">
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="bi bi-list"></i> Hành động
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        {{-- Thanh toán (khi cọc chưa thanh toán & chưa huỷ) --}}
                        @if(($job->escrow_status ?? 'pending') === 'pending' && $job->status !== 'cancelled')
                          <li>
                            <form action="{{ route('job-payments.create', $job->job_id) }}" method="POST">
                              @csrf
                              <button type="submit" class="dropdown-item">
                                <i class="bi bi-credit-card me-1"></i> Thanh toán cọc
                              </button>
                            </form>
                          </li>
                        @endif

                        {{-- Giao/Xem task (chỉ khi đã funded) --}}
                        <li>
                          <button class="dropdown-item @if(!$canManageApplicants) disabled @endif" @if($canManageApplicants && $acceptedApplicants->count() > 0) data-bs-toggle="modal"
                          data-bs-target="#assignTaskModal-{{ $job->job_id }}" @endif @if(!$canManageApplicants)
                            data-bs-toggle="tooltip" title="Cần thanh toán cọc trước" @endif>
                            <i class="bi bi-clipboard-check me-1"></i> Giao / Xem task
                          </button>
                        </li>

                        {{-- Toggle ứng viên --}}
                        <li>
                          <button
                            class="dropdown-item @if($job->status === 'pending' || $job->status === 'cancelled') disabled @endif"
                            type="button" data-bs-target="#applicants-{{ $job->job_id }}" data-collapse>
                            <i class="bi bi-people me-1"></i> Ứng viên
                          </button>
                        </li>

                        {{-- Hoàn thành & giải ngân --}}
                        <li>
                          <hr class="dropdown-divider">
                        </li>
                        <li>
                          @if($canRelease)
                            <form class="complete-job-form" data-job-id="{{ $job->job_id }}"
                              action="{{ route('client.jobs.complete', $job->job_id) }}" method="POST">
                              @csrf
                              @method('PATCH')
                              <button type="submit" class="dropdown-item text-success d-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                <i class="bi bi-check2-circle"></i>
                                <span>Hoàn thành & giải ngân</span>
                              </button>
                            </form>
                          @else
                            <button class="dropdown-item disabled" data-bs-toggle="tooltip"
                              title="Cần trạng thái cọc: ĐÃ THANH TOÁN và có ít nhất 1 ứng viên đã được nhận">
                              <i class="bi bi-check2-circle me-1"></i> Hoàn thành & giải ngân
                            </button>
                          @endif
                        </li>

                        {{-- Xoá job (chỉ khi đã huỷ) --}}
                        @if($job->status === 'cancelled')
                          <li>
                            <hr class="dropdown-divider">
                          </li>
                          <li>
                            <form action="{{ route('client.jobs.destroy', $job->job_id) }}" method="POST"
                              onsubmit="return confirm('Bạn có chắc muốn xóa công việc này?');">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-trash me-1"></i> Xoá job
                              </button>
                            </form>
                          </li>
                        @endif
                      </ul>
                    </div>
                  </div>
                </div>

                {{-- MODAL GIAO/XEM/GIA HẠN TASK CHO JOB NÀY --}}
                <div class="modal fade" id="assignTaskModal-{{ $job->job_id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow">
                      <div class="modal-header">
                        <h5 class="modal-title">
                          Task – {{ \Illuminate\Support\Str::limit(strip_tags($job->title), 50) }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>

                      <div class="modal-body pt-0">
                        {{-- Tabs --}}
                        <ul class="nav nav-tabs mt-3" role="tablist">
                          <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-assign-{{ $job->job_id }}" data-bs-toggle="tab"
                              data-bs-target="#pane-assign-{{ $job->job_id }}" type="button" role="tab">
                              <i class="bi bi-clipboard-check me-1"></i> Giao task
                            </button>
                          </li>
                          <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-view-{{ $job->job_id }}" data-bs-toggle="tab"
                              data-bs-target="#pane-view-{{ $job->job_id }}" type="button" role="tab">
                              <i class="bi bi-list-check me-1"></i> Xem task
                            </button>
                          </li>
                          <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-extend-{{ $job->job_id }}" data-bs-toggle="tab"
                              data-bs-target="#pane-extend-{{ $job->job_id }}" type="button" role="tab">
                              <i class="bi bi-clock-history me-1"></i> Gia hạn task
                            </button>
                          </li>
                        </ul>

                        <div class="tab-content pt-3">
                          {{-- PANE 1: GIAO TASK --}}
                          <div class="tab-pane fade show active" id="pane-assign-{{ $job->job_id }}" role="tabpanel"
                            aria-labelledby="tab-assign-{{ $job->job_id }}">
                            <form method="POST" action="{{ route('client.tasks.store') }}"
                              id="assignTaskForm-{{ $job->job_id }}">
                              @csrf
                              <input type="hidden" name="job_id" value="{{ $job->job_id }}">

                              <div class="mb-3">
                                <label class="form-label">Giao cho</label>
                                <select class="form-select" name="assignee_account_ids[]" multiple required
                                  size="{{ min(6, max(3, $acceptedApplicants->count())) }}">
                                  @foreach($acceptedApplicants as $u)
                                    @php
                                      $p = $u->profile ?? null;
                                      $n = $p->fullname ?? $u->name ?? ('#' . $u->account_id);
                                    @endphp
                                    <option value="{{ $u->account_id }}">{{ $n }}</option>
                                  @endforeach
                                </select>
                                <div class="form-text">Giữ Ctrl/⌘ để chọn nhiều người.</div>
                              </div>

                              <div class="mb-3">
                                <label class="form-label">Tiêu đề task</label>
                                <input type="text" class="form-control" name="title" maxlength="150" required
                                  placeholder="VD: Thiết kế landing page v1">
                              </div>

                              <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" name="description" rows="4"
                                  placeholder="Mô tả rõ phạm vi, tiêu chí bàn giao, checklist..."></textarea>
                              </div>

                              <div class="row g-3">
                                <div class="col-md-6">
                                  <label class="form-label">Ngày bắt đầu</label>
                                  <input type="date" class="form-control" name="start_date"
                                    value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Hạn hoàn thành</label>
                                  <input type="date" class="form-control" name="due_date">
                                </div>
                              </div>

                              <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                                <button type="submit" class="btn btn-primary">
                                  <span class="spinner-border spinner-border-sm me-2 d-none"
                                    id="assignSpin-{{ $job->job_id }}"></span>
                                  Giao task
                                </button>
                              </div>
                            </form>
                          </div>

                          {{-- PANE 2: GIA HẠN TASK --}}
                          <div class="tab-pane fade" id="pane-extend-{{ $job->job_id }}" role="tabpanel"
                            aria-labelledby="tab-extend-{{ $job->job_id }}">
                            <form method="POST" action="{{ route('client.tasks.extend') }}"
                              id="extendTaskForm-{{ $job->job_id }}">
                              @csrf
                              @method('PATCH')
                              <input type="hidden" name="job_id" value="{{ $job->job_id }}">

                              <div class="row g-3">
                                <div class="col-md-6">
                                  <label class="form-label">Chọn freelancer</label>
                                  <select class="form-select extend-assignee" id="extend-assignee-{{ $job->job_id }}"
                                    name="assignee_account_id" data-job-id="{{ $job->job_id }}">
                                    @foreach($acceptedApplicants as $u)
                                      @php
                                        $p = $u->profile ?? null;
                                        $n = $p->fullname ?? $u->name ?? ('#' . $u->account_id);
                                      @endphp
                                      <option value="{{ $u->account_id }}" @selected($u->account_id == $firstAssigneeId)>{{ $n }}
                                      </option>
                                    @endforeach
                                  </select>
                                </div>

                                <div class="col-md-6">
                                  <label class="form-label">Chọn task</label>
                                  <select class="form-select" name="task_id" id="extend-task-{{ $job->job_id }}" required>
                                    {{-- sẽ được JS đổ options dựa trên freelancer --}}
                                  </select>
                                </div>

                                <div class="col-md-6">
                                  <label class="form-label">Hạn mới</label>
                                  <input type="date" class="form-control" name="new_due_date"
                                    id="extend-date-{{ $job->job_id }}" required min="{{ now()->addDay()->toDateString() }}">
                                  <div class="form-text">Chọn ngày sau hôm nay.</div>
                                </div>
                              </div>

                              <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                  <i class="bi bi-save2 me-1"></i> Lưu gia hạn
                                </button>
                              </div>
                            </form>
                          </div>

                          {{-- PANE 3: XEM TASK --}}
                          <div class="tab-pane fade" id="pane-view-{{ $job->job_id }}" role="tabpanel"
                            aria-labelledby="tab-view-{{ $job->job_id }}">
                            <div class="row g-3 align-items-end">
                              <div class="col-md-6">
                                <label class="form-label">Chọn freelancer</label>
                                <select class="form-select view-assignee" id="view-assignee-{{ $job->job_id }}"
                                  data-job-id="{{ $job->job_id }}">
                                  @foreach($acceptedApplicants as $u)
                                    @php
                                      $p = $u->profile ?? null;
                                      $n = $p->fullname ?? $u->name ?? ('#' . $u->account_id);
                                    @endphp
                                    <option value="{{ $u->account_id }}" @selected($u->account_id == $firstAssigneeId)>{{ $n }}
                                    </option>
                                  @endforeach
                                </select>
                              </div>
                            </div>

                            <div class="mt-3" id="taskList-{{ $job->job_id }}">
                              @if($firstAssigneeId && !empty($firstUserTasks))
                                <div class="list-group small">
                                  @foreach($firstUserTasks as $t)
                                    @php
                                      $fileUrls = collect(explode('|', (string) ($t->file_url ?? '')))
                                        ->map(fn($u) => trim($u))->filter()->values();
                                      $prettyName = function ($u) {
                                        $p = parse_url($u, PHP_URL_PATH) ?? '';
                                        return urldecode($p ? basename($p) : 'file');
                                      };
                                    @endphp

                                    <div class="list-group-item border-0 ps-0">
                                      <div class="fw-semibold">
                                        #{{ $t->task_id }}
                                        <span class="fw-normal text-secondary">· {{ $t->title }}</span>
                                        <small class="text-muted ms-2">
                                          <i class="bi bi-clock-history"></i>
                                          {{ \Illuminate\Support\Carbon::parse($t->updated_at)->format('Y-m-d H:i') }}
                                        </small>
                                      </div>

                                      @if($fileUrls->count())
                                        <ul class="mb-0 mt-1">
                                          @foreach($fileUrls as $u)
                                            <li>
                                              <a href="{{ $u }}" target="_blank" rel="noopener" download>
                                                {{ $prettyName($u) }}
                                              </a>
                                            </li>
                                          @endforeach
                                        </ul>
                                      @else
                                        <div class="text-muted small">Không có tệp đính kèm.</div>
                                      @endif
                                    </div>
                                  @endforeach
                                </div>
                              @else
                                <div class="text-muted small"><i class="bi bi-info-circle"></i> Chưa có task.</div>
                              @endif
                            </div>
                          </div>
                        </div>
                      </div> {{-- /modal-body --}}
                    </div>
                  </div>
                </div>

                {{-- Danh sách ứng viên (có bulk chọn nhiều) --}}
                <div class="collapse mt-3" id="applicants-{{ $job->job_id }}">

                  {{-- TOOLBAR BULK --}}
                  <form class="bulk-form mb-2" data-job-id="{{ $job->job_id }}" data-remaining="{{ $remain }}"
                    action="{{ route('client.jobs.applications.bulk', $job->job_id) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="d-flex align-items-center gap-2">
                      <div class="form-check me-3">
                        <input class="form-check-input bulk-check-all" type="checkbox" id="bulk-all-{{ $job->job_id }}"
                          @if($remain === 0) disabled @endif>
                        <label class="form-check-label" for="bulk-all-{{ $job->job_id }}">Chọn tất cả</label>
                      </div>

                      <button type="button" class="btn btn-sm btn-success bulk-accept" @if(!$canManageApplicants || $job->status === 'cancelled' || $remain === 0) disabled @endif>
                        <i class="bi bi-check2"></i> Duyệt đã chọn
                      </button>

                      <button type="button" class="btn btn-sm btn-outline-danger bulk-reject" @if(!$canManageApplicants || $job->status === 'cancelled') disabled @endif>
                        <i class="bi bi-x"></i> Từ chối đã chọn
                      </button>

                      <span
                        class="badge rounded-pill {{ $remain > 0 ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} ms-2">
                        @if($remain > 0) Còn {{ $remain }} slot @else Đã đủ số lượng @endif
                      </span>

                      <span class="text-muted small ms-2 d-none bulk-hint">0 mục đã chọn</span>
                    </div>
                  </form>

                  @forelse(($job->applicants ?? collect()) as $acc)
                    @php
                      $pro = $acc->profile ?? null;
                      $name = $pro->fullname ?? $acc->name ?? 'Ứng viên';
                      $email = $acc->email ?? null;
                      $intro = $acc->pivot->introduction ?? null;
                      $apAt = $acc->pivot->created_at ?? null;
                      $st = (int) ($acc->pivot->status ?? 0); // 0 từ chối, 1 chờ duyệt, 2 đã nhận
                      $avatar = $acc->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=64';
                      $stBadge = match ($st) {
                        1 => ['Chờ duyệt', 'secondary'],
                        0 => ['Đã từ chối', 'warning'],
                        default => ['Đã duyệt', 'success'],
                      };
                    @endphp

                    <div class="list-group-item px-0 border-top pt-2">
                      <div class="d-flex align-items-start gap-3">

                        {{-- CHECKBOX bulk: chỉ hiển thị khi CHỜ DUYỆT và job cho phép thao tác --}}
                        <div style="width:30px">
                          @if($st === 1 && $canManageApplicants && $job->status !== 'cancelled')
                            <input type="checkbox" class="form-check-input bulk-cb" value="{{ $acc->account_id }}"
                              data-job-id="{{ $job->job_id }}">
                          @endif
                        </div>

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
                              $skillNames = collect(explode(',', (string) $pro->skill))
                                ->filter()
                                ->map(fn($id) => $skillMap[(int) $id] ?? null)
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
      {{-- ====== KHỐI: Công việc của doanh nghiệp (READ-ONLY) ====== --}}
      @if(isset($orgOwnerJobs) && $orgOwnerJobs instanceof \Illuminate\Contracts\Pagination\Paginator && $orgOwnerJobs->count())
        <hr class="my-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <h2 class="h5 mb-1">Công việc của doanh nghiệp</h2>
            <div class="text-muted small">Các job do <strong>Chủ doanh nghiệp</strong> đăng trong tổ chức bạn đang tham gia
              (chế độ chỉ xem).</div>
          </div>
        </div>

        <div id="org-owner-jobs" class="vstack gap-3">
          @foreach($orgOwnerJobs as $job)
            @php
              $statusMap = [
                'open' => ['label' => 'ĐANG MỞ', 'class' => 'success'],
                'pending' => ['label' => 'CHỜ DUYỆT', 'class' => 'warning'],
                'in_progress' => ['label' => 'ĐANG LÀM', 'class' => 'info'],
                'completed' => ['label' => 'ĐÃ HOÀN THÀNH', 'class' => 'success'],
                'cancelled' => ['label' => 'ĐÃ HỦY', 'class' => 'secondary'],
                'closed' => ['label' => 'ĐÃ ĐÓNG', 'class' => 'dark'],
              ];
              $cfg = $statusMap[$job->status] ?? ['label' => strtoupper($job->status), 'class' => 'secondary'];

              $escrowMap = [
                'pending' => ['label' => 'CHƯA THANH TOÁN', 'class' => 'warning'],
                'funded' => ['label' => 'ĐÃ THANH TOÁN', 'class' => 'primary'],
                'released' => ['label' => 'ĐÃ GIẢI NGÂN', 'class' => 'success'],
                'refunded' => ['label' => 'HOÀN TIỀN', 'class' => 'secondary'],
              ];
              $esc = $escrowMap[$job->escrow_status ?? 'pending'] ?? $escrowMap['pending'];

              $applyCount = $job->applicants_count
                ?? ($job->relationLoaded('applicants') ? $job->applicants->count() : 0);

              $countBadge = $applyCount > 0
                ? 'bg-primary-subtle text-primary border border-primary-subtle'
                : 'bg-secondary-subtle text-secondary border border-secondary-subtle';

              $acceptedCount = collect($job->applicants ?? collect())
                ->filter(fn($u) => (int) ($u->pivot->status ?? 0) === 2)
                ->count();

              $quantity = (int) ($job->quantity ?? 1);
            @endphp

            <div class="card shadow-sm border-0 job-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                  {{-- LEFT: Tiêu đề + badges + meta + mô tả --}}
                  <div class="flex-grow-1">
                    <a class="fw-semibold text-decoration-none link-dark h5 mb-1 d-inline-block"
                      href="{{ route('jobs.show', $job->job_id) }}">
                      {{ \Illuminate\Support\Str::limit(strip_tags($job->title), 60) }}
                    </a>

                    <div class="d-flex align-items-center gap-2 flex-wrap small">
                      <span class="badge rounded-pill bg-dark-subtle text-dark border border-dark-subtle">Chủ DN</span>
                      <span
                        class="badge rounded-pill bg-{{ $esc['class'] }}-subtle text-{{ $esc['class'] }} border border-{{ $esc['class'] }}-subtle">
                        {{ $esc['label'] }}
                      </span>
                      <span
                        class="badge rounded-pill bg-{{ $cfg['class'] }}-subtle text-{{ $cfg['class'] }} border border-{{ $cfg['class'] }}-subtle">
                        {{ $cfg['label'] }}
                      </span>
                      <span class="badge rounded-pill {{ $countBadge }}">
                        <i class="bi bi-people me-1"></i>{{ $applyCount }} ứng viên
                      </span>
                      <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">
                        <i class="bi bi-person-check me-1"></i>{{ $acceptedCount }}/{{ $quantity }} đã nhận
                      </span>
                    </div>

                    <div class="text-muted mt-2 small">
                      <span class="me-3"><i class="bi bi-tag"></i> {{ $job->categoryRef->name ?? '—' }}</span>
                      <span class="me-3">
                        <i class="bi bi-wallet2"></i> {{ $job->payment_type }}
                        @if($job->total_budget)
                                    ${{ $job->total_budget == floor($job->total_budget)
                          ? number_format($job->total_budget, 0)
                          : rtrim(rtrim(number_format($job->total_budget, 2, '.', ','), '0'), '.') }}
                        @endif
                      </span>
                      @if($job->deadline)
                        <span><i class="bi bi-calendar-event"></i>
                          {{ \Illuminate\Support\Carbon::parse($job->deadline)->toDateString() }}</span>
                      @endif
                    </div>

                    <div class="mt-2 text-secondary" style="max-width: 820px;">
                      {{ \Illuminate\Support\Str::limit(strip_tags($job->description), 50) }}
                    </div>
                  </div>

                  {{-- RIGHT: chỉ có nút xem chi tiết, không thao tác --}}
                  {{-- RIGHT: mở modal CHỈ XEM TASK --}}
                  <div class="text-end d-flex flex-column align-items-end gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('jobs.show', $job->job_id) }}">
                      Xem chi tiết
                    </a>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                      data-bs-target="#viewTasksModal-{{ $job->job_id }}">
                      Xem task
                    </button>
                  </div>


                </div>
              </div>
            </div>
            {{-- MODAL: XEM TASK (view-only) --}}
<div class="modal fade" id="viewTasksModal-{{ $job->job_id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title">
          Task – {{ \Illuminate\Support\Str::limit(strip_tags($job->title), 50) }} (Chỉ xem)
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        @php
          // Map task đã được controller truyền vào: $tasksByJobAndUser[job_id][account_id] = [task...]
          $jobTasksMap   = $tasksByJobAndUser[$job->job_id] ?? [];
          $acceptedUsers = collect(($job->applicants ?? collect()))
                            ->filter(fn($u) => (int) ($u->pivot->status ?? -1) === 2)
                            ->values();
          $firstUserId   = optional($acceptedUsers->first())->account_id;
          $firstUserTasks = $firstUserId ? ($jobTasksMap[$firstUserId] ?? []) : [];
        @endphp

        <div class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Freelancer</label>
            <select class="form-select" disabled>
              @forelse($acceptedUsers as $u)
                @php
                  $p = $u->profile ?? null;
                  $n = $p->fullname ?? $u->name ?? ('#'.$u->account_id);
                @endphp
                <option value="{{ $u->account_id }}" @selected($u->account_id == $firstUserId)>{{ $n }}</option>
              @empty
                <option>(Chưa có freelancer được nhận)</option>
              @endforelse
            </select>
            <div class="form-text">Chế độ chỉ xem</div>
          </div>
        </div>

        <div class="mt-3">
          @if($firstUserId && !empty($firstUserTasks))
            <div class="list-group small">
              @foreach($firstUserTasks as $t)
                @php
                  $fileUrls = collect(explode('|', (string) ($t->file_url ?? '')))
                    ->map(fn($u) => trim($u))->filter()->values();
                  $prettyName = function ($u) {
                    $p = parse_url($u, PHP_URL_PATH) ?? '';
                    return urldecode($p ? basename($p) : 'file');
                  };
                @endphp
                <div class="list-group-item border-0 ps-0">
                  <div class="fw-semibold">
                    #{{ $t->task_id }}
                    <span class="fw-normal text-secondary">· {{ $t->title }}</span>
                    <small class="text-muted ms-2">
                      <i class="bi bi-clock-history"></i>
                      {{ \Illuminate\Support\Carbon::parse($t->updated_at)->format('Y-m-d H:i') }}
                    </small>
                  </div>
                  @if($fileUrls->count())
                    <ul class="mb-0 mt-1">
                      @foreach($fileUrls as $u)
                        <li><a href="{{ $u }}" target="_blank" rel="noopener" download>{{ $prettyName($u) }}</a></li>
                      @endforeach
                    </ul>
                  @else
                    <div class="text-muted small">Không có tệp đính kèm.</div>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            <div class="text-muted small"><i class="bi bi-info-circle"></i> Chưa có task.</div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

          @endforeach
        </div>

        <div class="mt-3">
          {{ $orgOwnerJobs->links() }}
        </div>
      @endif

    </div>

  </main>

  <style>
    .job-card {
      transition: box-shadow .2s, transform .05s;
    }

    .job-card:hover {
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
    }

    .disabled-link {
      pointer-events: none;
      opacity: 0.5;
      cursor: not-allowed;
    }
  </style>
@endsection

@push('scripts')
  {{-- SweetAlert2 CDN --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Map toàn bộ tasks: { [job_id]: { [account_id]: [task,...] } } --}}
  <script>window.TASKS_BY_JOB = @json($tasksByJobAndUser ?? []);</script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.bootstrap) return;

      // ===== CSRF =====
      const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      // Enable tooltip
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

      const group = document.getElementById('applicants-group');

      // Toggle collapse "Ứng viên" theo nhóm (đảm bảo chỉ 1 cái mở)
      document.querySelectorAll('[data-collapse]').forEach(function (btn) {
        const targetSel = btn.getAttribute('data-bs-target');
        const target = document.querySelector(targetSel);
        if (!target) return;

        const inst = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });

        btn.addEventListener('click', function () { inst.toggle(); });

        target.addEventListener('show.bs.collapse', function () {
          if (!group) return;
          group.querySelectorAll('.collapse.show').forEach(function (el) {
            if (el !== target) bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
          });
        });
      });

      // ===== AJAX: GIAO TASK =====
      document.querySelectorAll('form[id^="assignTaskForm-"]').forEach(form => {
        form.addEventListener('submit', async function (e) {
          e.preventDefault();
          const jobId = this.id.replace('assignTaskForm-', '');
          const spin = document.getElementById('assignSpin-' + jobId);
          const btn = this.querySelector('button[type="submit"]');
          spin?.classList.remove('d-none');
          btn?.setAttribute('disabled', 'disabled');

          try {
            const res = await fetch(this.getAttribute('action'), {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(CSRF ? { 'X-CSRF-TOKEN': CSRF } : {})
              },
              body: new FormData(this)
            });

            let json; const text = await res.text();
            try { json = JSON.parse(text); } catch { json = { message: text }; }
            if (!res.ok) throw new Error(json?.message || 'Có lỗi xảy ra.');

            // 1) Cập nhật cache TASKS_BY_JOB để tab "Xem task" dùng ngay
            const payload = json.data || {};
            const jId = String(payload.job_id || jobId);
            window.TASKS_BY_JOB = window.TASKS_BY_JOB || {};
            window.TASKS_BY_JOB[jId] = window.TASKS_BY_JOB[jId] || {};

            (payload.tasks || []).forEach(t => {
              const uid = String(t.assigned_to);
              window.TASKS_BY_JOB[jId][uid] = window.TASKS_BY_JOB[jId][uid] || [];
              const exists = window.TASKS_BY_JOB[jId][uid].some(x => Number(x.task_id) === Number(t.task_id));
              if (!exists) {
                window.TASKS_BY_JOB[jId][uid].unshift({
                  task_id: t.task_id,
                  title: t.title,
                  file_url: t.file_url || '',
                  updated_at: t.updated_at || (new Date()).toISOString().slice(0, 16).replace('T', ' ')
                });
              }
            });

            // 2) Reset form
            this.reset();
            const startDate = this.querySelector('input[name="start_date"]');
            if (startDate) startDate.value = new Date().toISOString().slice(0, 10);
            const multi = this.querySelector('select[name="assignee_account_ids[]"]');
            if (multi) Array.from(multi.options).forEach(o => o.selected = false);

            // 3) Chuyển tab "Xem task" và render lại
            const viewTabBtn = document.getElementById('tab-view-' + jobId);
            if (viewTabBtn) bootstrap.Tab.getOrCreateInstance(viewTabBtn).show();

            const viewSel = document.getElementById('view-assignee-' + jobId);
            if (viewSel) {
              const firstAssigned = (payload.tasks && payload.tasks[0]) ? String(payload.tasks[0].assigned_to) : null;
              if (firstAssigned && Array.from(viewSel.options).some(o => o.value === firstAssigned)) {
                viewSel.value = firstAssigned;
              }
              const holder = document.getElementById('taskList-' + jobId);
              if (holder) {
                const tasks = (window.TASKS_BY_JOB?.[jId]?.[viewSel.value]) || [];
                holder.innerHTML = renderTaskListHtml(tasks);
              }
            }

            // 4) Nạp lại dropdown "Gia hạn task"
            const extendAssigneeSel = document.getElementById('extend-assignee-' + jobId);
            if (extendAssigneeSel) {
              const currentAssignee = extendAssigneeSel.value ||
                (payload.tasks && String(payload.tasks[0].assigned_to)) || '';
              populateExtendTasks(jobId, currentAssignee);
            }

            flashOk(json.message || 'Đã giao task.');
          } catch (err) {
            flashErr(err.message);
          } finally {
            spin?.classList.add('d-none');
            btn?.removeAttribute('disabled');
          }
        });
      });

      // ===== Handler tab "Xem task": đổi freelancer -> render danh sách =====
      document.body.addEventListener('change', function (e) {
        const sel = e.target.closest('.view-assignee');
        if (!sel) return;

        const jobId = sel.getAttribute('data-job-id');
        const assigneeId = sel.value;
        const mapByJob = window.TASKS_BY_JOB || {};
        const tasksByUser = mapByJob[jobId] || {};
        const tasks = tasksByUser[assigneeId] || {};

        const holder = document.getElementById('taskList-' + jobId);
        if (!holder) return;

        holder.innerHTML = renderTaskListHtml(tasks);
      });

      // ===== Khởi tạo pane "Gia hạn" khi modal mở lần đầu =====
      document.querySelectorAll('[id^="assignTaskModal-"]').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
          const jobId = this.id.replace('assignTaskModal-', '');
          initExtendTaskPane(jobId);
        }, { once: true });
      });

      // Đổi freelancer trong pane Gia hạn -> nạp tasks
      document.body.addEventListener('change', function (e) {
        const sel = e.target.closest('.extend-assignee');
        if (!sel) return;
        const jobId = sel.getAttribute('data-job-id');
        populateExtendTasks(jobId, sel.value);
      });

      function initExtendTaskPane(jobId) {
        const assigneeSel = document.getElementById('extend-assignee-' + jobId);
        if (!assigneeSel) return;
        populateExtendTasks(jobId, assigneeSel.value);
      }

      function populateExtendTasks(jobId, assigneeId) {
        const mapByJob = window.TASKS_BY_JOB || {};
        const tasksByUser = mapByJob[jobId] || {};
        const tasks = tasksByUser[assigneeId] || [];

        const taskSel = document.getElementById('extend-task-' + jobId);
        if (!taskSel) return;

        if (!tasks.length) {
          taskSel.innerHTML = '<option value="">(Freelancer chưa có task)</option>';
          taskSel.disabled = true;
          return;
        }

        taskSel.disabled = false;
        taskSel.innerHTML = tasks.map(t => {
          const title = escapeHtml(t.title || '');
          return `<option value="${t.task_id}">#${t.task_id} · ${title}</option>`;
        }).join('');
      }

      // ===== AJAX: Gia hạn task =====
      document.querySelectorAll('form[id^="extendTaskForm-"]').forEach(form => {
        form.addEventListener('submit', async function (e) {
          e.preventDefault();
          const btn = this.querySelector('button[type="submit"]');

          let spin = btn.querySelector('.spinner-border');
          if (!spin) {
            spin = document.createElement('span');
            spin.className = 'spinner-border spinner-border-sm me-2';
            spin.setAttribute('role', 'status');
            spin.setAttribute('aria-hidden', 'true');
            spin.style.display = 'none';
            btn.prepend(spin);
          }

          btn?.setAttribute('disabled', 'disabled');
          const oldHtml = btn.innerHTML;
          spin.style.display = '';
          btn.innerHTML = spin.outerHTML + '<span>Đang lưu gia hạn...</span>';

          const method = (this.querySelector('input[name="_method"]')?.value || this.getAttribute('method') || 'POST').toUpperCase();

          try {
            const res = await fetch(this.getAttribute('action'), {
              method: method === 'PATCH' ? 'POST' : method,
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(CSRF ? { 'X-CSRF-TOKEN': CSRF } : {})
              },
              body: new FormData(this)
            });

            let json; const text = await res.text();
            try { json = JSON.parse(text); } catch { json = { message: text }; }
            if (!res.ok) throw new Error(json?.message || 'Có lỗi xảy ra.');

            const d = json.data || {};
            const jobId = String(d.job_id);
            const uid = String(d.assignee_account_id);
            const taskId = Number(d.task_id);

            const arr = window.TASKS_BY_JOB?.[jobId]?.[uid];
            if (arr) {
              const found = arr.find(t => Number(t.task_id) === taskId);
              if (found) {
                found.due_date = d.new_due_date;
                found.updated_at = (new Date()).toISOString().slice(0, 16).replace('T', ' ');
              }
            }

            const holder = document.getElementById('taskList-' + jobId);
            const currentSel = document.getElementById('view-assignee-' + jobId);
            if (holder && currentSel && String(currentSel.value) === uid) {
              holder.innerHTML = renderTaskListHtml(window.TASKS_BY_JOB?.[jobId]?.[uid] || []);
            }

            flashOk(json.message || 'Đã gia hạn task.');
          } catch (err) {
            flashErr(err.message);
          } finally {
            btn.innerHTML = oldHtml;
            btn?.removeAttribute('disabled');
          }
        });
      });

      // ===== AJAX: HOÀN THÀNH & GIẢI NGÂN =====
      document.querySelectorAll('form.complete-job-form').forEach(form => {
        form.addEventListener('submit', async function (e) {
          e.preventDefault();

          const jobId = this.getAttribute('data-job-id');
          const action = this.getAttribute('action');
          const fd = new FormData(this); // _method=PATCH + _token
          const btn = this.querySelector('button[type="submit"]');

          btn?.setAttribute('disabled', 'disabled');

          Swal.fire({
            title: 'Xác nhận giải ngân?',
            text: 'Hành động này không thể hoàn tác.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: async () => {
              try {
                const res = await fetch(action, {
                  method: 'POST',
                  headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                      ? { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                      : {})
                  },
                  body: fd
                });

                const textRes = await res.text();
                let json;
                try { json = JSON.parse(textRes); } catch { json = { message: textRes }; }

                if (!res.ok) {
                  Swal.showValidationMessage(json?.message || 'Có lỗi xảy ra.');
                  throw new Error(json?.message || 'Có lỗi xảy ra.');
                }
                return json;
              } catch (err) {
                Swal.showValidationMessage(err?.message || 'Không thể kết nối máy chủ.');
                throw err;
              }
            }
          }).then(async (result) => {
            if (!result.isConfirmed) return;
            const json = result.value || {};
            await Swal.fire({
              title: 'Thành công',
              text: json?.message || 'Đã hoàn thành job và giải ngân thành công.',
              icon: 'success',
              confirmButtonText: 'OK'
            });

            const card = document.querySelector(`#assignTaskModal-${jobId}`)?.closest('.card')
              || document.querySelector(`[data-job-id="${jobId}"]`)?.closest('.card')
              || form.closest('.card');

            if (card) {
              const badges = card.querySelectorAll('.badge');
              badges.forEach(b => {
                const t = (b.textContent || '').trim().toUpperCase();
                if (t.includes('THANH TOÁN') || t.includes('GIẢI NGÂN')) {
                  b.className = 'badge rounded-pill bg-success-subtle text-success border border-success-subtle';
                  b.textContent = 'ĐÃ GIẢI NGÂN';
                }
                if (['ĐANG MỞ', 'CHỜ DUYỆT', 'ĐANG LÀM', 'ĐÃ HỦY', 'ĐÃ ĐÓNG', 'COMPLETED', 'ĐÃ HOÀN THÀNH'].includes(t)) {
                  b.className = 'badge rounded-pill bg-success-subtle text-success border border-success-subtle';
                  b.textContent = 'ĐÃ HOÀN THÀNH';
                }
              });
            }

            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Đã giải ngân';
            btn.classList.add('disabled');
            btn.setAttribute('disabled', 'disabled');
          }).catch(() => {
            btn?.removeAttribute('disabled');
          }).finally(() => {
            btn?.removeAttribute('disabled');
          });
        });
      });

      // ===== Bulk chọn nhiều ứng viên =====
      document.querySelectorAll('.bulk-form').forEach(form => {
        const jobId = form.getAttribute('data-job-id');
        const remaining = parseInt(form.getAttribute('data-remaining') || '0', 10);
        const wrap = document.getElementById('applicants-' + jobId);
        const checkAll = form.querySelector('.bulk-check-all');
        const hint = form.querySelector('.bulk-hint');
        const btnAccept = form.querySelector('.bulk-accept');
        const btnReject = form.querySelector('.bulk-reject');

        // (NEW) Tạo overlay mờ để khoá thao tác khu vực danh sách khi đang xử lý
        let overlay = document.createElement('div');
        overlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-none';
        overlay.style.background = 'rgba(255,255,255,.6)';
        overlay.style.backdropFilter = 'blur(1px)';
        overlay.innerHTML = `
            <div class="position-absolute top-50 start-50 translate-middle">
              <div class="spinner-border" role="status" aria-hidden="true"></div>
              <div class="small text-muted mt-2 text-center">Đang xử lý...</div>
            </div>`;
        // bọc collapse relative để overlay định vị đúng
        const collapse = document.getElementById('applicants-' + jobId);
        if (collapse && !collapse.classList.contains('position-relative')) {
          collapse.classList.add('position-relative');
        }
        collapse?.appendChild(overlay);

        const cbs = () => wrap.querySelectorAll('.bulk-cb');

        function updateHint() {
          const n = [...cbs()].filter(cb => cb.checked).length;
          if (hint) {
            hint.textContent = n + ' mục đã chọn' + (remaining > 0 ? ` (tối đa ${remaining})` : '');
            hint.classList.toggle('d-none', n === 0);
          }
          const disabled = n === 0;
          btnAccept?.toggleAttribute('disabled', disabled || remaining === 0);
          btnReject?.toggleAttribute('disabled', disabled);
        }

        // Chọn tất cả: chỉ tick tối đa "remaining"
        checkAll?.addEventListener('change', e => {
          const wantCheck = e.target.checked;
          if (!wantCheck) {
            cbs().forEach(cb => cb.checked = false);
            updateHint();
            return;
          }
          let slots = remaining;
          cbs().forEach(cb => {
            if (cb.disabled) return;
            if (slots > 0) { cb.checked = true; slots--; }
            else { cb.checked = false; }
          });
          if (remaining === 0 || slots === 0) checkAll.checked = [...cbs()].every(cb => cb.checked);
          updateHint();
        });

        // Tick lẻ từng mục: chặn nếu vượt limit
        wrap.addEventListener('change', e => {
          if (!e.target.classList.contains('bulk-cb')) return;
          if (e.target.checked) {
            const nChecked = [...cbs()].filter(cb => cb.checked).length;
            if (nChecked > remaining) {
              e.target.checked = false;
              if (window.Swal) {
                Swal.fire({ icon: 'info', title: 'Đã đủ chỉ tiêu', text: `Bạn chỉ có thể duyệt tối đa ${remaining} ứng viên.`, timer: 1800, showConfirmButton: false });
              } else {
                alert(`Bạn chỉ có thể duyệt tối đa ${remaining} ứng viên.`);
              }
            }
          }
          if (!e.target.checked && checkAll) checkAll.checked = false;
          updateHint();
        });

        // (NEW) helper set/restore loading trên nút
        function setBtnLoading(btn, text = 'Đang xử lý...') {
          if (!btn) return () => { };
          const prev = btn.innerHTML;
          btn.setAttribute('disabled', 'disabled');
          btn.innerHTML = `
              <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              <span>${text}</span>`;
          return () => {
            btn.innerHTML = prev;
            btn.removeAttribute('disabled');
          };
        }

        // (NEW) bật/tắt overlay + khoá checkbox
        function setBusy(on) {
          overlay?.classList.toggle('d-none', !on);
          cbs().forEach(cb => cb.disabled = on);
          checkAll?.toggleAttribute('disabled', on);
        }

        async function submitBulk(action) {
          const selected = [...cbs()].filter(x => x.checked).map(x => x.value);
          if (selected.length === 0) return;

          if (action === 'accept' && selected.length > remaining) {
            if (window.Swal) {
              await Swal.fire({ icon: 'error', title: 'Vượt quá chỉ tiêu', text: `Bạn đang chọn ${selected.length} người, nhưng chỉ còn ${remaining} slot. Hãy bỏ chọn bớt.` });
            } else {
              alert(`Chỉ còn ${remaining} slot.`);
            }
            return;
          }

          const pretty = action === 'accept' ? 'Duyệt' : 'Từ chối';
          const ok = await Swal.fire({
            title: `${pretty} ${selected.length} ứng viên?`,
            text: action === 'accept' ? `Tối đa ${remaining} người sẽ được duyệt.` : '',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Thực hiện',
            cancelButtonText: 'Hủy'
          });
          if (!ok.isConfirmed) return;

          const fd = new FormData();
          fd.append('_method', 'PATCH');
          const token = document.querySelector('meta[name="csrf-token"]')?.content;
          if (token) fd.append('_token', token);
          fd.append('action', action);
          selected.forEach(id => fd.append('user_ids[]', id));

          // (NEW) bật loading UI
          const restoreAccept = action === 'accept' ? setBtnLoading(btnAccept, 'Đang duyệt...') : () => { };
          const restoreReject = action === 'reject' ? setBtnLoading(btnReject, 'Đang từ chối...') : () => { };
          setBusy(true);
          // (NEW) dùng SweetAlert loading (tuỳ chọn)
          let loadingSwal;
          if (window.Swal) {
            loadingSwal = Swal.fire({
              title: 'Đang xử lý...',
              html: 'Vui lòng đợi trong giây lát.',
              allowOutsideClick: false,
              allowEscapeKey: false,
              didOpen: () => Swal.showLoading()
            });
          }

          try {
            const res = await fetch(form.getAttribute('action'), {
              method: 'POST',
              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              body: fd
            });
            const text = await res.text();
            let json; try { json = JSON.parse(text); } catch { json = { message: text }; }
            if (!res.ok) throw new Error(json?.message || 'Có lỗi xảy ra.');

            if (window.Swal) {
              await Swal.fire({ icon: 'success', title: 'Thành công', text: json?.message || 'Đã xử lý.' });
            } else {
              alert(json?.message || 'Đã xử lý.');
            }
            // đồng bộ UI nhanh nhất: reload
            location.reload();
          } catch (err) {
            if (window.Swal) {
              Swal.fire({ icon: 'error', title: 'Lỗi', text: err.message || 'Không xử lý được.' });
            } else {
              alert(err.message || 'Không xử lý được.');
            }
          } finally {
            // tắt loading UI (sẽ không chạy nếu đã reload)
            restoreAccept();
            restoreReject();
            setBusy(false);
          }
        }

        btnAccept?.addEventListener('click', () => submitBulk('accept'));
        btnReject?.addEventListener('click', () => submitBulk('reject'));

        updateHint();
      });
      // ===== Helpers =====
      function renderTaskListHtml(tasks) {
        if (!tasks || tasks.length === 0) {
          return '<div class="text-muted small"><i class="bi bi-info-circle"></i> Chưa có task.</div>';
        }
        let html = '<div class="list-group small">';
        for (const t of tasks) {
          const files = (t.file_url || '').split('|').map(s => s.trim()).filter(Boolean);
          const fileLis = files.map(u => {
            try {
              const name = decodeURIComponent((new URL(u)).pathname.split('/').pop() || 'file');
              return `<li><a href="${u}" target="_blank" rel="noopener" download>${name}</a></li>`;
            } catch {
              return `<li><a href="${u}" target="_blank" rel="noopener" download>${u}</a></li>`;
            }
          }).join('');

          html += `
                  <div class="list-group-item border-0 ps-0">
                    <div class="fw-semibold">
                      #${t.task_id ?? ''}
                      ${t.title ? `<span class="fw-normal text-secondary">· ${escapeHtml(t.title)}</span>` : ''}
                      ${t.updated_at ? `<small class="text-muted ms-2"><i class="bi bi-clock-history"></i> ${t.updated_at}</small>` : ''}
                    </div>
                    ${files.length ? `<ul class="mb-0 mt-1">${fileLis}</ul>` : `<div class="text-muted small">Không có tệp đính kèm.</div>`}
                  </div>
                `;
        }
        html += '</div>';
        return html;
      }

      function escapeHtml(s) {
        return String(s).replace(/[&<>\"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
      }

      function flashOk(msg) {
        Swal.fire({ title: 'Thành công', text: msg || 'Thao tác hoàn tất.', icon: 'success', confirmButtonText: 'OK' });
      }

      function flashErr(msg) {
        Swal.fire({ title: 'Lỗi', text: msg || 'Có lỗi xảy ra, vui lòng thử lại.', icon: 'error', confirmButtonText: 'Đóng' });
      }
    });
  </script>
@endpush