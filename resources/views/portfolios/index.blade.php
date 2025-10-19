@extends('layouts.app')

@section('title', $profile->fullname . ' - Trang cá nhân')

@section('content')
    <div class="container my-4">

        <div class="position-relative mb-5">
            <!-- Cover -->
            <div class="rounded-4" style="height: 180px; background: linear-gradient(135deg, #4e73df, #1cc88a);"></div>

            <!-- Avatar -->
            <div class="position-absolute top-100 start-50 translate-middle" style="margin-top: -60px;">
                <div class="position-relative d-inline-block" id="avatarWrapper">
                    {{-- Ảnh đại diện --}}
                    <img id="avatarImg" src="{{ $account->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                        class="rounded-circle border border-4 border-white shadow-lg"
                        style="width:160px; height:160px; object-fit:cover;">

                    {{-- Overlay spinner --}}
                    <div id="avatarSpinner" class="avatar-spinner d-none">
                        <div class="spinner-border text-light" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>

                    {{-- Tick xác minh --}}
                    @if($account->account_type_id == 2)
                        <span
                            class="position-absolute bottom-0 end-0 bg-warning border border-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width:36px; height:36px; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
                            <i class="bi bi-patch-check-fill text-white" style="font-size:20px;"></i>
                        </span>
                    @endif

                    {{-- Nút upload ảnh (chỉ hiện khi đúng chủ tài khoản) --}}
                    @if(Auth::check() && Auth::id() === $account->account_id)
                        <form action="{{ route('profile.avatar.upload') }}" method="POST" enctype="multipart/form-data"
                            id="avatarForm">
                            @csrf
                            <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none">
                        </form>

                        <span id="avatarBtn"
                            class="position-absolute bottom-0 end-0 bg-secondary border border-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width:42px; height:42px; box-shadow:0 2px 6px rgba(0,0,0,0.2); cursor:pointer;">
                            <i class="bi bi-camera-fill text-white fs-6"></i>
                        </span>
                    @endif
                </div>
            </div>

        </div>
        <style>
            #avatarWrapper {
                width: 160px;
                height: 160px;
            }

            .avatar-spinner {
                position: absolute;
                inset: 0;
                background: rgba(0, 0, 0, 0.25);
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: opacity .2s;
            }

            .is-uploading #avatarBtn {
                pointer-events: none;
                opacity: 0.6;
            }
        </style>
        <!-- Name + Stats -->
        <div class="text-center mt-5 mb-4">
            <h2 class="fw-bold">{{ $profile->fullname }}</h2>
            <p class="text-muted mb-1">@ {{ $profile->username }}</p>
            <div class="d-inline-flex align-items-center gap-2">
                <p class="mb-0">
                    <i class="bi bi-geo-alt"></i>
                    <span id="locationText">{{ $profile->location ?? 'Chưa cập nhật địa chỉ' }}</span>
                </p>


                @if(Auth::check() && Auth::id() === $account->account_id)
                    <button type="button" class="btn btn-link text-muted p-0 border-0" data-bs-toggle="modal"
                        data-bs-target="#editLocationModal" title="Chỉnh sửa địa chỉ" style="box-shadow:none;">
                        <i class="bi bi-pencil fs-15"></i>
                    </button>

                @endif
            </div>

            {{-- Modal chỉnh sửa Location --}}
            @if(Auth::check() && Auth::id() === $account->account_id)
                <div class="modal fade" id="editLocationModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <form id="locationAjaxForm" class="modal-content" method="POST"
                            action="{{ route('portfolios.location.update') }}">
                            @csrf @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title">Chỉnh sửa địa chỉ</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Tỉnh / Thành phố</label>
                                    <select id="c_province" class="form-select" style="width:100%">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phường / Xã</label>
                                    <select id="c_ward" class="form-select" style="width:100%" disabled>
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Địa chỉ lưu</label>
                                    <input type="text" name="location" id="locationInput" class="form-control"
                                        value="{{ old('location', $profile->location) }}" maxlength="150"
                                        placeholder="VD: Phường Bến Nghé, TP.HCM">
                                    <div class="form-text">Tối đa 150 ký tự. Sẽ tự ghép theo lựa chọn phía trên.</div>
                                </div>
                            </div>
                            <div class="modal-footer"> <button type="button" class="btn btn-light"
                                    data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary">Lưu</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

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
                @if(Auth::check() && Auth::id() != $account->account_id)
                    <div class="d-grid mb-4">
                        <a href="{{ url('/portfolios/' . $profile->username . '/chat') }}" class="btn btn-primary">
                            <i class="bi bi-chat-dots me-2"></i> Liên hệ ngay
                        </a>
                    </div>

                @endif

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
                                    $applicantsCount = $job->applicants()->count();
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
                    @if (session('ok'))
                        <div aria-live="polite" aria-atomic="true" class="position-relative">
                            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080">
                                <div id="okToast" class="toast align-items-center text-white bg-success border-0" role="alert"
                                    aria-live="assertive" aria-atomic="true">
                                    <div class="d-flex">
                                        <div class="toast-body">
                                            {{ session('ok') }}
                                        </div>
                                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                            data-bs-dismiss="toast" aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
                            const el = document.getElementById('okToast');
                            if (!el) return;
                            const toast = new bootstrap.Toast(el, { autohide: true, delay: 3000 });
                            toast.show();
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Giữ nguyên style avatar */
        #avatarWrapper {
            width: 160px;
            height: 160px;
        }

        .avatar-spinner {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .25);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: opacity .2s;
        }

        .is-uploading #avatarBtn {
            pointer-events: none;
            opacity: .6;
        }

        /* Chặn Bootstrap tự set padding-right khi mở modal (tránh giật/đơ cuộn) */
        body.modal-open {
            padding-right: 0 !important;
        }

        /* Nếu SweetAlert2 mở, vẫn cho cuộn (tránh kẹt) */
        body.swal2-shown {
            overflow: auto !important;
        }
    </style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ====== Helper gỡ mọi khóa cuộn (Modal/Swal) ======
        function unlockScroll() {
            // Bootstrap Modal leftovers
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
            document.documentElement.style.removeProperty('overflow');

            // SweetAlert2 leftovers
            ['swal2-shown', 'swal2-height-auto', 'swal2-no-backdrop', 'swal2-toast-shown']
                .forEach(c => document.body.classList.remove(c));

            // Xóa mọi backdrop còn sót
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }

        // ====== UI phụ khác (toggle jobs, avatar spinner) ======
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

        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('avatarBtn');
            const input = document.getElementById('avatarInput');
            const form = document.getElementById('avatarForm');
            const spinner = document.getElementById('avatarSpinner');
            const wrapper = document.getElementById('avatarWrapper');
            const img = document.getElementById('avatarImg');

            if (!btn || !input || !form || !spinner || !img) return;

            btn.addEventListener('click', () => input.click());

            input.addEventListener('change', () => {
                if (!input.files.length) return;

                const file = input.files[0];
                // Kiểm tra client-side (tùy thích)
                const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    Swal.fire({ icon: 'error', title: 'Tệp không hợp lệ', text: 'Chỉ chấp nhận JPG, PNG, WEBP.' });
                    input.value = '';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'Tệp quá lớn', text: 'Kích thước tối đa 5MB.' });
                    input.value = '';
                    return;
                }

                // UI loading
                spinner.classList.remove('d-none');
                wrapper.classList.add('is-uploading');

                // Chuẩn bị FormData
                const fd = new FormData();
                fd.append('avatar', file);

                // Lấy CSRF
                const token = (form.querySelector('input[name="_token"]') || {}).value
                    || (document.querySelector('meta[name="csrf-token"]') || {}).content
                    || '';

                // Gửi AJAX
                $.ajax({
                    url: form.action,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': token }
                })
                    .done(function (res) {
                        if (res && res.ok) {
                            // cập nhật ảnh (bypass cache)
                            const url = res.url + (res.url.includes('?') ? '&' : '?') + 't=' + Date.now();
                            img.src = url;

                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: res.message || 'Ảnh đại diện đã được cập nhật.',
                                timer: 1500,
                                showConfirmButton: false,
                                didOpen: unlockScroll, willClose: unlockScroll, didClose: unlockScroll, didDestroy: unlockScroll
                            });
                        } else {
                            const msg = (res && (res.message || (res.errors && Object.values(res.errors).flat().join(' ')))) || 'Upload thất bại.';
                            Swal.fire({
                                icon: 'error', title: 'Lỗi!', text: msg,
                                didOpen: unlockScroll, willClose: unlockScroll, didClose: unlockScroll, didDestroy: unlockScroll
                            });
                        }
                    })
                    .fail(function (xhr) {
                        let msg = 'Upload thất bại.';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error', title: 'Lỗi!', text: msg,
                            didOpen: unlockScroll, willClose: unlockScroll, didClose: unlockScroll, didDestroy: unlockScroll
                        });
                    })
                    .always(function () {
                        spinner.classList.add('d-none');
                        wrapper.classList.remove('is-uploading');
                        input.value = ''; // reset input để có thể chọn lại cùng file
                    });
            });
        });


        // ====== Modal Location + Select2 ======
        (function () {
            const $modal = $('#editLocationModal');
            let provincesData = [];
            let provincesLoaded = false;

            function ensureProvincesLoaded() {
                return new Promise((resolve, reject) => {
                    if (provincesLoaded && provincesData.length) return resolve(provincesData);

                    fetch('https://provinces.open-api.vn/api/v2/?depth=2', {
                        method: 'GET',
                        // KHÔNG set bất kỳ custom header nào để tránh preflight
                        cache: 'no-store',
                        mode: 'cors',
                        credentials: 'omit'
                    })
                        .then(res => {
                            if (!res.ok) throw new Error('Fetch provinces failed: ' + res.status);
                            return res.json();
                        })
                        .then(data => {
                            provincesData = Array.isArray(data) ? data : [];
                            provincesLoaded = true;
                            resolve(provincesData);
                        })
                        .catch(reject);
                });
            }


            function collectWards(p) {
                if (!p) return [];
                if (Array.isArray(p.wards) && p.wards.length) return p.wards;
                const wards = [];
                (p.districts || []).forEach(d => (d.wards || []).forEach(w => wards.push(w)));
                return wards;
            }

            function fillProvinces() {
                const $prov = $('#c_province');
                $prov.empty().append('<option value=""></option>');
                provincesData.forEach(p => $prov.append(`<option value="${p.code}">${p.name}</option>`));
                $prov.val('');
            }

            function initSelect2() {
                if ($('#c_province').hasClass('select2-hidden-accessible')) $('#c_province').select2('destroy');
                if ($('#c_ward').hasClass('select2-hidden-accessible')) $('#c_ward').select2('destroy');
                const s2 = { theme: 'bootstrap-5', allowClear: true, width: '100%', dropdownParent: $modal };
                $('#c_province').select2({ ...s2, placeholder: 'Chọn tỉnh / thành phố' });
                $('#c_ward').select2({ ...s2, placeholder: 'Chọn phường / xã' });

                $(document).off('select2:open._focus').on('select2:open._focus', () => {
                    const el = document.querySelector('.select2-container--open .select2-search__field');
                    if (el) el.focus();
                });
            }

            function setupHandlers() {
                $('#c_province').off('change').on('change', function () {
                    const provCode = $(this).val();
                    const $ward = $('#c_ward');
                    $ward.prop('disabled', true).empty().append('<option value=""></option>').val(null).trigger('change.select2');
                    $('#locationInput').val('');
                    if (!provCode) return;

                    const province = provincesData.find(p => String(p.code) === String(provCode));
                    if (!province) return;
                    $('#locationInput').val(province.name);

                    const wards = collectWards(province);
                    wards.forEach(w => $ward.append(`<option value="${w.code}" data-codename="${w.codename || ''}">${w.name}</option>`));
                    $ward.prop('disabled', false).val(null).trigger('change.select2');
                    syncLocation();
                });

                $('#c_ward').off('change').on('change', function () {
                    syncLocation();
                });
            }

            function syncLocation() {
                const pCode = $('#c_province').val();
                const wText = $('#c_ward').find(':selected').text() || '';
                const pName = provincesData.find(x => String(x.code) === String(pCode))?.name || '';
                $('#locationInput').val([wText, pName].filter(Boolean).join(', '));
            }

            $modal.on('shown.bs.modal', async function () {
                try {
                    await ensureProvincesLoaded();
                    fillProvinces();
                    initSelect2();
                    setupHandlers();
                } catch (e) { /* ignore */ }
            });

            $modal.on('hidden.bs.modal', function () {
                if ($('#c_province').hasClass('select2-hidden-accessible')) $('#c_province').select2('destroy');
                if ($('#c_ward').hasClass('select2-hidden-accessible')) $('#c_ward').select2('destroy');
                forceModalCleanup();
                unlockScroll(); // đảm bảo mở khóa cuộn khi modal đóng
            });
        })();

        // ====== AJAX cập nhật Location (SweetAlert2) — CHỈ 1 HANDLER ======
        (function () {
            const $modal = $('#editLocationModal');

            $modal.off('submit.location').on('submit.location', 'form#locationAjaxForm, form[action$="portfolios/location"]', function (e) {
                e.preventDefault();

                const $form = $(this);
                const action = $form.attr('action');
                const $btnSave = $form.find('button[type="submit"]');
                const token = $form.find('input[name="_token"]').val(); // fallback nếu meta csrf thiếu

                // khóa nút
                $btnSave.prop('disabled', true).addClass('disabled');
                const originalHtml = $btnSave.html();
                $btnSave.html('<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...');

                // payload
                const data = $form.serializeArray();
                if (!data.find(x => x.name === '_method')) data.push({ name: '_method', value: 'PATCH' });

                $.ajax({
                    url: action,
                    type: 'POST',
                    data: $.param(data),
                    headers: { 'X-CSRF-TOKEN': token || ($('meta[name="csrf-token"]').attr('content') || '') }
                })
                    .done(function (res) {
                        if (res?.ok) {
                            if (res.location) $('#locationText').text(res.location);

                            // đóng select2 trước khi ẩn
                            try { $('#c_province').select2('close'); } catch (e) { }
                            try { $('#c_ward').select2('close'); } catch (e) { }

                            const modalEl = document.getElementById('editLocationModal');
                            const m = bootstrap.Modal.getOrCreateInstance(modalEl);

                            // dọn rác khi hidden (chuẩn)
                            modalEl.addEventListener('hidden.bs.modal', function onHidden() {
                                modalEl.removeEventListener('hidden.bs.modal', onHidden);
                                forceModalCleanup();
                                unlockScroll(); // mở khóa cuộn khi đã hidden
                            }, { once: true });

                            m.hide(); // ẩn modal

                            // Dự phòng: cưỡng bức sau 150ms nếu event không bắn
                            setTimeout(function () { forceModalCleanup(); unlockScroll(); }, 150);

                            // thông báo
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: res.message || 'Đã lưu địa chỉ.',
                                timer: 1800,
                                showConfirmButton: false,
                                didOpen: unlockScroll,
                                willClose: unlockScroll,
                                didClose: unlockScroll,
                                didDestroy: unlockScroll
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: res?.message || 'Không thể cập nhật địa chỉ.',
                                didOpen: unlockScroll,
                                willClose: unlockScroll,
                                didClose: unlockScroll,
                                didDestroy: unlockScroll
                            });
                        }
                    })
                    .fail(function (xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Có lỗi xảy ra khi cập nhật.';
                        Swal.fire({
                            icon: 'error', title: 'Lỗi!', text: msg,
                            didOpen: unlockScroll, willClose: unlockScroll, didClose: unlockScroll, didDestroy: unlockScroll
                        });
                    })
                    .always(function () {
                        $btnSave.prop('disabled', false).removeClass('disabled').html(originalHtml);
                    });
            });

            // Hàm dọn rác backdrop + body.lock
            window.forceModalCleanup = function () {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            };
        })();

        // ====== Watchdog dọn backdrop còn sót (phòng edge cases) ======
        (function () {
            function cleanupBackdrops() {
                const hasOpen = document.querySelector('.modal.show');
                if (!hasOpen) {
                    window.forceModalCleanup && window.forceModalCleanup();
                    unlockScroll(); // thêm để chắc chắn
                }
            }
            document.addEventListener('hidden.bs.modal', cleanupBackdrops);
            document.addEventListener('shown.bs.modal', cleanupBackdrops);
            setInterval(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length && !document.querySelector('.modal.show')) cleanupBackdrops();
            }, 800);
        })();

        // ====== (Giữ nguyên các script rating / toast phía trên của bạn) ======
    </script>


@endsection