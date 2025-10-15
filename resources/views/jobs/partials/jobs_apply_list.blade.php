@foreach($applies as $applie)
    @php
        $statusMap = [
            0 => ['label' => 'BỊ TỪ CHỐI', 'class' => 'danger'],
            1 => ['label' => 'CHỜ DUYỆT', 'class' => 'warning'],
            2 => ['label' => 'ĐANG LÀM', 'class' => 'info'],
            3 => ['label' => 'HOÀN THÀNH', 'class' => 'success'],
        ];

        $cfg = $statusMap[$applie->status] ?? ['label' => 'KHÔNG RÕ', 'class' => 'secondary'];
        $job = $applie->job;

        // Task tiến độ
        $tasks = $job->tasks->where('assigned_to', Auth::id());
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();

        $progress = match (true) {
            $applie->status == 3 => 100,
            $applie->status == 2 && $totalTasks > 0 => round(($completedTasks / $totalTasks) * 100, 0),
            default => 0,
        };

        $progressClass = match ($applie->status) {
            0 => 'bg-danger',
            1 => 'bg-warning',
            2 => 'bg-info',
            3 => 'bg-success',
            default => 'bg-secondary',
        };

        // Đồng hành (status = 2 hoặc 3)
        $showColleagues = in_array($applie->status, [2, 3]);
        $colleagues = $showColleagues && isset($otherApplicants[$job->job_id])
            ? $otherApplicants[$job->job_id]
            : collect();
    @endphp

    <div class="card shadow-sm border-0 job-card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-start">
                <div class="col-12 col-md-9">
                    {{-- Tiêu đề + badge + owner --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0">
                            <a class="fw-bold job-title" href="{{ route('jobs.show', $job->job_id) }}">
                                {{ strip_tags($job->title) }}
                            </a>
                        </h5>

                        <span
                            class="badge rounded-pill bg-{{ $cfg['class'] }}-subtle text-{{ $cfg['class'] }} border border-{{ $cfg['class'] }}-subtle">
                            {{ $cfg['label'] }}
                        </span>

                        {{-- Avatar + tên chủ job --}}
                        <span class="badge rounded-pill bg-light text-dark border d-flex align-items-center gap-1">
                            <img src="{{ $job->account->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                                alt="{{ $job->account->name }}" class="rounded-circle" width="24" height="24">
                            <a href="{{ route('portfolios.show', $job->account->profile->username) }}">
                                {{ $job->account->name }}
                            </a>
                        </span>
                    </div>

                    {{-- Thông tin thêm --}}
                    <div class="small text-muted mt-2">
                        <span class="me-3"><i class="bi bi-tag"></i> {{ $job->jobCategory->name ?? 'Khác' }}</span>
                        <span class="me-3">
                            <i class="bi bi-wallet2"></i>
                            @if($job->payment_type === 'fixed') Trả trọn gói
                            @elseif($job->payment_type === 'hourly') Trả theo giờ
                            @else Không xác định @endif
                            @if($job->budget) · {{ number_format($job->budget, 0, ',', '.') }} ₫ @endif
                        </span>
                        @if($job->deadline)
                            <span><i class="bi bi-calendar-event"></i>
                                {{ \Carbon\Carbon::parse($job->deadline)->locale('vi')->translatedFormat('d F, Y') }}</span>
                        @endif
                    </div>

                    {{-- Mô tả --}}
                    <div class="mt-2 text-secondary job-description">{{ strip_tags($job->description) }}</div>

                    {{-- Thanh tiến độ --}}
                    @if(in_array($applie->status, [2, 3]))
                        <div class="mt-3">
                            <label class="small text-muted">Tiến độ công việc</label>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $progressClass }}" role="progressbar"
                                    style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">{{ $progress }}% hoàn thành
                                @if($totalTasks > 0) ({{ $completedTasks }}/{{ $totalTasks }} task) @endif
                            </small>
                        </div>
                    @endif

                    {{-- Ngày ứng tuyển --}}
                    @if($applie->created_at)
                        <div class="small text-muted mt-2">
                            <i class="bi bi-clock"></i> Ứng tuyển:
                            {{ \Carbon\Carbon::parse($applie->created_at)->diffForHumans() }}
                        </div>
                    @endif
                </div>

                {{-- Cột phải: Nút hành động --}}
                <div class="col-12 col-md-3 text-md-end text-center">
                    <div class="d-flex flex-wrap justify-content-md-end justify-content-center gap-2 mb-2">
                        <a href="{{ route('jobs.show', $job->job_id) }}" class="btn btn-sm btn-outline-primary w-auto">
                            <i class="bi bi-eye me-1"></i> Xem
                        </a>
                        <a href="{{ route('chat.all') }}" class="btn btn-sm btn-outline-success w-auto">
                            <i class="bi bi-chat-dots me-1"></i> Chat
                        </a>
                        <button class="btn btn-sm btn-outline-secondary w-auto" type="button"
                            data-bs-target="#details-{{ $job->job_id }}" data-collapse data-open-text="Ẩn"
                            data-close-text="Chi tiết">
                            <i class="bi bi-info-circle me-1 icon"></i>
                            <span class="label">Chi tiết</span>
                        </button>
                    </div>

                    {{-- Avatar đồng hành --}}
                    @if($showColleagues && $colleagues->isNotEmpty())
                        <div class="mt-2">
                            {{-- Chú thích thân thiện --}}
                            <div class="small text-muted mb-1">Đang làm cùng bạn:</div>
                            <div
                                class="d-flex align-items-center justify-content-md-end justify-content-center gap-2 flex-wrap">
                                @foreach($colleagues->take(3) as $applicant)
                                    @php
                                        $profile = $applicant->user->profile;
                                        $username = $profile->username ?? $applicant->user->name;
                                    @endphp
                                    <a href="{{ route('portfolios.show', $username) }}" class="d-inline-block"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $username }}">
                                        <img src="{{ $applicant->user->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                                            alt="{{ $username }}" class="rounded-circle border border-light" width="32" height="32">
                                    </a>
                                @endforeach
                                @if($colleagues->count() > 3)
                                    <span class="small text-muted">+{{ $colleagues->count() - 3 }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>

            </div>

            {{-- Chi tiết công việc (collapse) --}}
            <div class="collapse mt-3" id="details-{{ $job->job_id }}">
                {{-- Thêm chi tiết task nếu muốn --}}
            </div>
        </div>
    </div>
@endforeach