@extends('layouts.app')

@section('title', $profile->fullname . ' - Trang cá nhân')

@section('content')
    <div class="container my-4">

        <div class="position-relative mb-5">
            <!-- Cover -->
            <div class="rounded-4" style="height: 180px; background: linear-gradient(135deg, #4e73df, #1cc88a);"></div>

            <!-- Avatar -->
            <div class="position-absolute top-100 start-50 translate-middle" style="margin-top: -60px;">
                <div class="position-relative d-inline-block">
                    <img src="{{ $account->avatar_url ?? asset('assets/img/blog/blog-author.jpg') }}"
                        class="rounded-circle border border-4 border-white shadow-lg"
                        style="width:160px; height:160px; object-fit:cover;">

                    <!-- Trạng thái xác minh-->
                    <span
                        class="position-absolute bottom-0 end-0 bg-secondary border border-white rounded-circle d-flex align-items-center justify-content-center"
                        style="width:36px; height:36px; box-shadow:0 2px 6px rgba(0,0,0,0.2); cursor:pointer;">
                        <i class="bi bi-camera-fill text-white fs-6"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Name + Stats -->
        <div class="text-center mt-5 mb-4">
            <h2 class="fw-bold">{{ $profile->fullname }}</h2>
            <p class="text-muted mb-1">@ {{ $profile->username }}</p>
            <p><i class="bi bi-geo-alt"></i> {{ $profile->location ?? 'Chưa cập nhật địa chỉ' }}</p>

            <div class="row justify-content-center mt-4 g-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded shadow-sm p-3 h-100">
                        <h4 class="fw-bold">{{ $stats['total_jobs'] }}</h4>
                        <small class="text-muted">Tổng công việc</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded shadow-sm p-3 h-100">
                        <h4 class="fw-bold text-success">{{ $stats['completed_jobs'] }}</h4>
                        <small class="text-muted">Hoàn thành</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded shadow-sm p-3 h-100">
                        <h4 class="fw-bold text-warning">{{ $stats['ongoing_jobs'] }}</h4>
                        <small class="text-muted">Đang làm</small>
                    </div>
                </div>
            </div>
        </div>
        <br>

        <!-- Layout 2 cột -->
        <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-lg-4">
                <!-- Giới thiệu -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-person-lines-fill me-2"></i>Giới thiệu
                        </h5>
                        <p>{{ $profile->about ?? 'Chưa cập nhật giới thiệu.' }}</p>
                    </div>
                </div>

                <!-- Liên hệ ngay -->
                <div class="d-grid mb-4">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#chatModal">
                        <i class="bi bi-chat-dots me-2"></i> Liên hệ ngay
                    </button>
                </div>

                <!-- Kỹ năng -->
                @if(in_array($profile->account_type, [1, 2]))
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-lightning-charge-fill me-2"></i>Kỹ năng
                            </h5>
                            <div>
                                @foreach($profile->skills ?? [] as $skill)
                                    <span class="badge bg-secondary me-1">{{ $skill }}</span>
                                @endforeach
                                @if(empty($profile->skills))
                                    <p class="text-muted">Chưa cập nhật kỹ năng.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Liên hệ & MXH -->
               <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-share me-2"></i>Liên hệ & Mạng xã hội</h5>

                        <div class="mt-2 d-flex align-items-center">
                            <!-- GitHub -->
                            <a href="{{ $profile->github ?? '#' }}" class="btn btn-outline-dark btn-sm me-1"
                                target="_blank">
                                <i class="bi bi-github"></i>
                            </a>

                            <!-- Facebook -->
                            <a href="{{ $profile->facebook ?? '#' }}" class="btn btn-outline-primary btn-sm me-1"
                                target="_blank">
                                <i class="bi bi-facebook"></i>
                            </a>

                            <!-- Gmail -->
                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to={{ $account->email }}"
                                class="btn btn-outline-danger btn-sm" target="_blank">
                                <i class="bi bi-envelope-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải -->
            <div class="col-lg-8">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" id="profileTabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#jobs">Công việc</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#portfolio">Doanh nghiệp</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#reviews">Đánh giá</a></li>
                </ul>

                <div class="tab-content">
                    <!-- Jobs -->
                    <div class="tab-pane fade show active" id="jobs">
                        <div class="position-relative" id="job-list">
                            @forelse($jobs as $index => $job)
                                @php
                                    $applicantsCount = $job->apply_id ? count(explode(',', $job->apply_id)) : 0;
                                @endphp

                                <div class="row g-0 mb-4 job-item {{ $index >= 3 ? 'd-none' : '' }}">
                                    <div class="col-auto d-flex flex-column align-items-center pe-3 position-relative">
                                        <!-- Icon -->
                                        <div class="bg-{{ $job->status == 'completed' ? 'success' : 'warning' }} rounded-circle d-flex align-items-center justify-content-center border border-2 border-white"
                                            style="width:48px; height:48px;">
                                            <i
                                                class="bi bi-{{ $job->status == 'completed' ? 'check-circle-fill' : 'clock-fill' }} text-white fs-4"></i>
                                        </div>

                                        <!-- Vertical Line -->
                                        @if(!$loop->last)
                                            <div class="flex-grow-1 w-1 bg-secondary mt-2" style="min-height:60px; opacity:0.2;">
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col">
                                        <div class="card shadow-sm border-0 rounded-3 p-3 ms-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="fw-bold mb-1">
                                                        <a href="{{ route('jobs.show', $job->job_id) }}"
                                                            class="text-decoration-none text-dark">
                                                            {{ $job->title }}
                                                        </a>
                                                    </h6>
                                                    <p class="text-muted small mb-2">{{ Str::limit($job->description, 120) }}
                                                    </p>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <span
                                                            class="badge bg-{{ $job->status == 'completed' ? 'success' : 'warning' }} rounded-pill px-2 py-1"
                                                            style="font-size:0.8rem;">
                                                            {{ $job->status == 'completed' ? 'Hoàn thành' : 'Đang làm' }}
                                                        </span>
                                                        <span
                                                            class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-1"
                                                            style="font-size:0.8rem;">
                                                            {{ $applicantsCount }} người ứng tuyển
                                                        </span>
                                                    </div>
                                                </div>
                                                <small class="text-muted">{{ $job->created_at->format('d/m/Y') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @empty
                                <p class="text-muted text-center" style="font-size:1.1rem;">Chưa có công việc nào.</p>
                            @endforelse
                        </div>

                        @if($jobs->count() > 3)
                            <div class="text-center mt-3">
                                <a href="javascript:void(0)" id="toggle-jobs" class="text-decoration-none fw-bold">
                                    Xem thêm <i class="bi bi-chevron-down"></i>
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Doanh nghiệp -->
                    <div class="tab-pane fade" id="portfolio">
                        <h5 class="fw-bold mb-3">Thông tin doanh nghiệp</h5>
                        <div class="row g-4">
                            @forelse($profile->portfolio_items ?? [] as $index => $item)
                                <div class="col-md-6 portfolio-item {{ $index >= 4 ? 'd-none' : '' }}">
                                    <div class="card shadow-sm border-0 h-100 position-relative overflow-hidden">
                                        <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top"
                                            style="height:200px; object-fit:cover;">
                                        <div class="card-body">
                                            <h6 class="fw-bold">{{ $item->title ?? 'Dự án' }}</h6>
                                            <p class="text-muted small">
                                                {{ Str::limit($item->description ?? 'Không có mô tả', 80) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">Chưa có doanh ngiệp nào.</p>
                            @endforelse
                        </div>
                        @if(count($profile->portfolio_items ?? []) > 4)
                            <div class="text-center mt-3">
                                <button id="show-more-portfolio" class="btn btn-primary">Xem thêm</button>
                            </div>
                        @endif
                    </div>

                    <!-- Reviews -->
                    <div class="tab-pane fade" id="reviews">
                        <h5 class="fw-bold mb-3">Đánh giá</h5>

                        {{-- Tóm tắt đánh giá --}}
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3 text-center">
                                <h2 class="mb-0 fw-bold text-primary">4.3</h2>
                                <small class="text-muted">/ 5</small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="mb-1" style="color:#f1c40f;">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                </div>
                                <small class="text-muted">3 đánh giá</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#reviewModal">
                                    <i class="bi bi-pencil-square me-1"></i> Đánh giá ngay
                                </button>
                            </div>
                        </div>

                        {{-- Danh sách đánh giá --}}
                        <div id="review-list">
                            <div class="card shadow-sm border-0 mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong class="me-2">Hồ Gia Huy</strong>
                                        <div style="color:#f1c40f;">
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star"></i>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-secondary">Làm việc rất chuyên nghiệp, giao dự án đúng hạn.</p>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong class="me-2">Nguyễn Văn A</strong>
                                        <div style="color:#f1c40f;">
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-secondary">Rất hài lòng, sẽ hợp tác lâu dài.</p>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 mb-3 d-none extra-review">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong class="me-2">Lê Văn C</strong>
                                        <div style="color:#f1c40f;">
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star"></i>
                                            <i class="bi bi-star"></i>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-secondary">Tạm ổn, cần cải thiện thêm về tốc độ phản hồi.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Nút xem thêm / thu gọn --}}
                        <div class="text-center mt-3">
                            <a href="javascript:void(0)" id="toggle-reviews"
                                class="text-decoration-none fw-bold text-primary">
                                Xem thêm <i class="bi bi-chevron-down"></i>
                            </a>
                        </div>
                    </div>


                    {{-- Modal thêm đánh giá --}}
                    <!-- Modal Đánh giá -->
                    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Đánh giá người dùng</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form>
                                        <div class="mb-3 text-center">
                                            <label class="form-label d-block">Chọn số sao</label>
                                            <div id="starRating" class="fs-3">
                                                <i class="bi bi-star" data-value="1"></i>
                                                <i class="bi bi-star" data-value="2"></i>
                                                <i class="bi bi-star" data-value="3"></i>
                                                <i class="bi bi-star" data-value="4"></i>
                                                <i class="bi bi-star" data-value="5"></i>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Nhận xét của bạn</label>
                                            <textarea class="form-control" rows="3"
                                                placeholder="Viết nhận xét..."></textarea>
                                        </div>

                                        <div class="text-end">
                                            <button type="button" class="btn btn-primary">Gửi đánh giá</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CSS: sao màu vàng -->
                    <style>
                        #starRating i {
                            cursor: pointer;
                            color: #ccc;
                        }

                        #starRating i.active,
                        #starRating i:hover,
                        #starRating i:hover~i {
                            color: #f1c40f;
                        }
                    </style>

                    <script>
                        document.querySelectorAll('#starRating i').forEach(star => {
                            star.addEventListener('click', function () {
                                let value = this.getAttribute('data-value');
                                document.querySelectorAll('#starRating i').forEach(s => {
                                    s.classList.remove('bi-star-fill', 'active');
                                    s.classList.add('bi-star');
                                });
                                for (let i = 0; i < value; i++) {
                                    let starEl = document.querySelector(`#starRating i:nth-child(${i + 1})`);
                                    starEl.classList.remove('bi-star');
                                    starEl.classList.add('bi-star-fill', 'active');
                                }
                            });
                        });

                        document.addEventListener('DOMContentLoaded', function () {
                            const toggleBtn = document.getElementById('toggle-reviews');
                            const extraReviews = document.querySelectorAll('.extra-review');
                            let expanded = false;

                            toggleBtn.addEventListener('click', function () {
                                extraReviews.forEach(r => r.classList.toggle('d-none'));
                                expanded = !expanded;
                                toggleBtn.innerHTML = expanded
                                    ? 'Thu gọn <i class="bi bi-chevron-up"></i>'
                                    : 'Xem thêm <i class="bi bi-chevron-down"></i>';
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggle-jobs');
            const jobItems = document.querySelectorAll('.job-item.d-none');
            let expanded = false;

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    jobItems.forEach(item => item.classList.toggle('d-none'));
                    expanded = !expanded;
                    toggleBtn.innerHTML = expanded
                        ? 'Thu gọn <i class="bi bi-chevron-up"></i>'
                        : 'Xem thêm <i class="bi bi-chevron-down"></i>';
                });
            }
        });
    </script>

@endsection