<div id="jobs-list" class="row g-3">
    @foreach($jobs as $job)
        <div class="col-12">
             <div class="card shadow-sm h-100 hover-shadow position-relative">
                <!-- Badge trạng thái góc trên bên phải -->
                <span class="position-absolute top-0 end-0 m-2 px-3 py-1 rounded-pill text-white fw-bold small shadow-sm
                    @switch($job->status)
                        @case('open') bg-primary @break                     {{-- Đang tuyển --}}
                        @case('in_progress') bg-warning text-dark @break    {{-- Đang làm --}}
                        @case('completed') bg-success @break                {{-- Hoàn thành --}}
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
                        <img src="{{ $job->image ?? asset('assets/img/blog/blog-1.jpg') }}" class="img-fluid rounded"
                            alt="{{ $job->title }}">
                    </div>
                    <!-- Thông tin job -->
                    <div class="col-md-8">
                        <div class="card-body d-flex flex-column h-100">
                            <h5 class="card-title">{{ $job->title }}</h5>
                            <p class="text-muted mb-2"><i
                                    class="bi bi-tags me-1"></i>{{ $job->jobCategory->name ?? 'Khác' }}</p>
                            <p class="mb-2 fs-5"><strong>{{ number_format($job->salary ?? $job->budget, 0, ',', '.') }}
                                    VNĐ</strong> / {{ $job->payment_type ?? 'tháng' }}</p>
                            <p class="text-truncate mb-2">{{ Str::limit($job->description, 120) }}</p>
                            <!-- Người đăng -->
                            <div class="d-flex align-items-center mb-2">
                                <img src="{{ optional($job->account)->avatar_url ?? asset('assets/img/blog/blog-author.jpg') }}"
                                    alt="{{ optional($job->account)->name ?? 'Người đăng' }}" class="rounded-circle me-2"
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

                                    <p class="mb-0 text-muted"><time datetime="{{ $job->created_at }}">Đăng ngày
                                            {{ $job->created_at->format('h:i:s A d/m/Y') }}</time></p>
                                </div>
                            </div>
                            <!-- Nút chi tiết -->
                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <a href="{{ route('jobs.show', $job->job_id) }}" class="btn btn-sm text-white"
                                    style="background-color: #0ea2bd; border-color: #0ea2bd;">Xem chi tiết</a>
                                <button class="btn p-0 text-danger fs-5 d-flex align-items-center justify-content-center"
                                    type="button">
                                    <i class="bi bi-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div id="pagination-wrapper" class="mt-4">
    {{ $jobs->links('components.pagination') }}
</div>