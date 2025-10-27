@extends('layouts.app')
@section('title', 'JobLink - Công việc')
@section('content')
    <main class="main">
        <!-- Page Title -->
        <div class="page-title">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Chi tiết công việc</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li><a href="{{ route('jobs.index') }}">Công việc</a></li>
                        <li class="current">{{ $job->title }}</li>
                    </ol>
                </nav>
            </div>
        </div><!-- End Page Title -->

        <div class="container">
            <div class="row">

                <div class="col-lg-8">

                    <!-- Blog Details Section -->
                    <section id="blog-details" class="blog-details section">
                        <div class="container">

                            <article class="article">
                                @php
                                    $userId = auth()->id();
                                    $hasApplied = false;
                                    $applyStatus = null;
                                    $accountTypeId = auth()->check() ? auth()->user()->account_type_id : null;

                                    if ($userId) {
                                        // Kiểm tra xem user đã apply chưa
                                        $apply = $job->applicants()->where('user_id', $userId)->first();
                                        if ($apply) {
                                            $hasApplied = true;
                                            $applyStatus = $apply->pivot->status; // lấy trạng thái apply (int)
                                        }
                                    }

                                    // Kiểm tra deadline
                                    $isExpired = $job->deadline && \Carbon\Carbon::parse($job->deadline)->lt(\Carbon\Carbon::now());

                                    // Kiểm tra status open
                                    $isOpen = $job->status === 'open';
                                @endphp
                                <div id="applyAlertContainer" class="mb-3">
                                    @if($hasApplied)
                                        @php
                                            // Map trạng thái int sang badge + label
                                            switch ($applyStatus) {
                                                case 1:
                                                    $statusLabel = 'Chờ duyệt';
                                                    $statusClass = 'alert-primary';
                                                    break;
                                                case 2:
                                                    $statusLabel = 'Đã duyệt';
                                                    $statusClass = 'alert-success';
                                                    break;
                                                case 3:
                                                    $statusLabel = 'Từ chối';
                                                    $statusClass = 'alert-danger';
                                                    break;
                                                default:
                                                    $statusLabel = 'Không xác định';
                                                    $statusClass = 'alert-secondary';
                                            }
                                        @endphp

                                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center"
                                            role="alert">
                                            <i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>
                                            <div>Bạn đã ứng tuyển vào công việc này ({{ $statusLabel }}) </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                    @endif
                                </div>

                                <h2 class="title">{{ $job->title }}
                                </h2>

                                <div class="meta-top">
                                    <ul>
                                        <li class="d-flex align-items-center"><i class="bi bi-person"></i> <a
                                                href="blog-details.html">{{ $job->account->name ?? 'Người đăng ẩn danh' }}</a>
                                        </li>
                                        <li class="d-flex align-items-center"><i class="bi bi-clock"></i> <a
                                                href="blog-details.html">{{ $job->created_at->format('d/m/Y') }}</time></a>
                                        </li>
                                        <li class="d-flex align-items-center"><i class="bi bi-chat-dots"></i> <a
                                                href="blog-details.html">{{ $job->comments->count() }} Bình luận</a></li>
                                    </ul>
                                </div><!-- End meta top -->

                                <div class="content">
                                    @foreach($job->jobDetails as $detail)
                                        {!! $detail->content !!}
                                    @endforeach
                                    <img src="{{ asset('assets/img/blog/blog-6.jpg') }}" class="img-fluid"
                                        alt="SEO Website">
                                </div><!-- End post content -->


                                <div class="meta-bottom">
                                    <i class="bi bi-folder"></i>
                                    <ul class="cats">
                                        <li><a href="#">Business</a></li>
                                    </ul>

                                    <i class="bi bi-tags"></i>
                                    <ul class="tags">
                                        <li><a href="#">{{ $job->category->name }}</a></li>
                                        <li><a href="#">Tips</a></li>
                                        <li><a href="#">Creative</a></li>
                                    </ul>
                                </div><!-- End meta bottom -->

                            </article>

                        </div>
                    </section><!-- /Blog Details Section -->

                    <!-- Blog Author Section -->
                    <section id="blog-author" class="blog-author section">

                        <div class="container">
                            <div class="author-container d-flex align-items-center">
                                <img src="{{ optional($job->account)->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                                    class="rounded-circle flex-shrink-0" alt="">
                                <div>
                                    <h4>
                                        @if($job->account?->profile?->username)
                                            <a href="{{ route('portfolios.show', $job->account->profile->username) }}">
                                                {{ $job->account->name }}
                                            </a>
                                        @else
                                            {{ $job->account?->name ?? 'Người đăng ẩn danh' }}
                                        @endif
                                    </h4>

                                    <div class="social-links">
                                        <!-- Facebook -->
                                        <a href="https://facebook.com/yourpage" target="_blank"><i
                                                class="bi bi-facebook"></i></a>

                                        <!-- Gmail -->
                                        <a href="https://mail.google.com/mail/?view=cm&to={{ $job->account->email ?? '22004027@st.vlute.edu.vn' }}"
                                            target="_blank">
                                            <i class="bi bi-envelope"></i>
                                        </a>

                                        <!-- Chat (biểu tượng tùy chỉnh của web) -->
                                        <a href="{{ route('chat.job', ['job' => $job->job_id]) }}" id="custom-chat">
                                            <i class="bi bi-chat-dots"></i>
                                        </a>

                                    </div>

                                    <p>
                                        Yêu cầu: {{ Str::limit($job->description, 120) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                    </section><!-- /Blog Author Section -->

                    <!-- Blog Comments Section -->
                    <section id="blog-comments" class="blog-comments section">
                        @include('jobs.partials.comments-list')
                    </section><!-- /Blog Comments Section -->
                    <br>
                    <!-- Comment Form Section 
                                                            <section id="comment-form" class="comment-form section">
                                                                <div class="container">

                                                                    <form action="">

                                                                        <h4>Post Comment</h4>
                                                                        <p>Your email address will not be published. Required fields are marked * </p>
                                                                        <div class="row">
                                                                            <div class="col-md-6 form-group">
                                                                                <input name="name" type="text" class="form-control" placeholder="Your Name*">
                                                                            </div>
                                                                            <div class="col-md-6 form-group">
                                                                                <input name="email" type="text" class="form-control" placeholder="Your Email*">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col form-group">
                                                                                <input name="website" type="text" class="form-control" placeholder="Your Website">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col form-group">
                                                                                <textarea name="comment" class="form-control"
                                                                                    placeholder="Your Comment*"></textarea>
                                                                            </div>
                                                                        </div>

                                                                        <div class="text-center">
                                                                            <button type="submit" class="btn btn-primary">Post Comment</button>
                                                                        </div>

                                                                    </form>

                                                                </div>
                                                            </section> /Comment Form Section -->

                </div>

                <div class="col-lg-4 sidebar">

                    <div class="widgets-container">

                        <!-- Search Widget -->
                        <div class="search-widget widget-item">

                            <h3 class="widget-title">Tìm kiếm</h3>
                            <form action="">
                                <input type="text">
                                <button type="submit" title="Search"><i class="bi bi-search"></i></button>
                            </form>

                        </div><!--/Search Widget -->

                        <!-- Categories Widget -->
                        <div class="categories-widget widget-item">
                            <h3 class="widget-title">Danh mục</h3>
                            <ul class="mt-3">
                                @php
                                    use App\Models\JobCategory;
                                    $categories = JobCategory::withCount('jobs')->get();
                                @endphp
                                @foreach($categories as $category)
                                    <li>
                                        <a href="{{ route('jobs.index') }}" class="filter-category"
                                            data-id="{{ $category->category_id }}">
                                            {{ $category->name }} <span>({{ $category->jobs_count }})</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const categoryLinks = document.querySelectorAll('.filter-category');

                                categoryLinks.forEach(link => {
                                    link.addEventListener('click', function (e) {
                                        e.preventDefault(); // ngăn link load ngay lập tức

                                        const categoryId = this.dataset.id;

                                        // Lấy localStorage hiện tại hoặc tạo mới
                                        let filters = JSON.parse(localStorage.getItem('jobFilters')) || {
                                            payment_type: [],
                                            status: [],
                                            category: [],
                                            page: 1
                                        };

                                        // Gán category vừa click
                                        filters.category = [categoryId];
                                        filters.page = 1;

                                        // Lưu lại localStorage
                                        localStorage.setItem('jobFilters', JSON.stringify(filters));

                                        // Chuyển sang trang /jobs
                                        window.location.href = this.href;
                                    });
                                });
                            });
                        </script>

                        <!--/Categories Widget -->

                        <!-- Recent Posts Widget -->
                        <div class="recent-posts-widget widget-item">

                            <h3 class="widget-title">Bài viết liên quan</h3>

                            @forelse($relatedJobs as $related)
                                <div class="post-item">
                                    <img src="{{ $related->thumbnail ? asset('storage/' . $related->thumbnail) : asset('assets/img/blog/blog-recent-1.jpg') }}"
                                        alt="" class="flex-shrink-0">
                                    <div>
                                        <h4>
                                            <a href="{{ route('jobs.show', $related->job_id) }}">
                                                {{ $related->title }}
                                            </a>
                                        </h4>
                                        <time datetime="{{ $related->created_at->toDateString() }}">
                                            {{ $related->created_at->isoFormat('D MMMM, YYYY') }}
                                        </time>
                                        <div class="author-info" style="margin-top: 5px; display: flex; align-items: center;">
                                            <img src="{{ $related->account?->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                                                alt="avatar"
                                                style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover; margin-right: 8px;">

                                            <span>
                                                @if($related->account?->profile?->username)
                                                    <a href="{{ route('portfolios.show', $related->account->profile->username) }}">
                                                        {{ $related->account?->name ?? 'Người đăng ẩn danh' }}
                                                    </a>
                                                @else
                                                    {{ $related->account?->name ?? 'Người đăng ẩn danh' }}
                                                @endif
                                            </span>
                                        </div>


                                    </div>
                                </div><!-- End recent post item-->
                            @empty
                                <p>Không có bài viết liên quan.</p>
                            @endforelse

                        </div><!--/Recent Posts Widget -->

                        <!-- Tags Widget -->
                        <div class="tags-widget widget-item">

                            <h3 class="widget-title">Thẻ</h3>
                            <ul>
                                <li><a href="#">App</a></li>
                                <li><a href="#">IT</a></li>
                                <li><a href="#">Business</a></li>
                                <li><a href="#">Mac</a></li>
                                <li><a href="#">Design</a></li>
                                <li><a href="#">Office</a></li>
                                <li><a href="#">Creative</a></li>
                                <li><a href="#">Studio</a></li>
                                <li><a href="#">Smart</a></li>
                                <li><a href="#">Tips</a></li>
                                <li><a href="#">Marketing</a></li>
                            </ul>

                        </div><!--/Tags Widget -->

                    </div>

                </div>

                @if($isOpen && !$isExpired)
                    @if(!$hasApplied && in_array($accountTypeId, [1, 2]))
                        <!-- 🔴 Nút Báo cáo -->
                        <a href="javascript:void(0);"
                            class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center position-fixed end-0 me-3"
                            style="bottom: 150px; width:55px; height:55px;" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Báo cáo công việc này">
                            <i class="bi bi-flag-fill fs-5"></i>
                        </a>

                        <!-- 🟢 Nút Ứng tuyển -->
                        <a href="javascript:void(0);"
                            class="btn btn-success rounded-circle d-flex align-items-center justify-content-center position-fixed end-0 me-3"
                            style="bottom: 85px; width:55px; height:55px;" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Ứng Tuyển Ngay">
                            <i class="bi bi-briefcase-fill fs-5"></i>
                        </a>

                    @endif
                @endif


                <!-- Modal Thông Báo -->
                <div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="applyModalLabel">Thông báo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="applyModalBody">
                                <!-- Spinner mặc định, sẽ hiển thị khi chờ AJAX -->
                                <div id="applySpinner" class="text-center my-3" style="display:none;">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div>Đang xử lý...</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <a href="{{ route('login') }}" class="btn btn-primary" id="loginBtn"
                                    style="display:none;">Đăng nhập</a>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    $(document).ready(function () {
                        $('.apply-btn').click(function () {
                            var $button = $(this);
                            var jobId = $button.data('job-id');
                            var $alertContainer = $('#applyAlertContainer');

                            // Hiển thị modal với spinner
                            var modalEl = document.getElementById('applyModal');
                            var modal = new bootstrap.Modal(modalEl);
                            $('#applyModalBody').html(
                                '<div class="text-center my-3">' +
                                '<div class="spinner-border text-success" role="status"></div>' +
                                '<div>Đang xử lý...</div>' +
                                '</div>'
                            );
                            $('#loginBtn').hide();
                            modal.show();

                            // Hiển thị spinner tạm thời trong alert container trên trang
                            $alertContainer.html(
                                '<div class="d-flex align-items-center">' +
                                '<div class="spinner-border text-success me-2" role="status"></div>' +
                                '<div>Đang xử lý...</div>' +
                                '</div>'
                            );

                            $.ajax({
                                url: '/jobs/apply/' + jobId,
                                method: 'GET',
                                dataType: 'json',
                                success: function (response) {
                                    // Ẩn nút Apply
                                    $button.fadeOut();

                                    if (response.success) {
                                        // Hiển thị thông báo thành công trong modal
                                        $('#applyModalBody').html(
                                            '<div class="alert alert-success d-flex align-items-center" role="alert">' +
                                            '<i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>' +
                                            '<div>Bạn đã ứng tuyển vào công việc này (' + response.statusLabel + ')</div>' +
                                            '</div>'
                                        );

                                        // Hiển thị thông báo thành công trên trang
                                        $alertContainer.html(
                                            '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">' +
                                            '<i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>' +
                                            '<div>Bạn đã ứng tuyển vào công việc này (' + response.statusLabel + ')</div>' +
                                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                            '</div>'
                                        );

                                    } else if (response.login_required) {
                                        $('#applyModalBody').html(
                                            '<div class="alert alert-warning d-flex align-items-center" role="alert">' +
                                            'Vui lòng <a href="/login">đăng nhập</a> để ứng tuyển.' +
                                            '</div>'
                                        );
                                        $('#loginBtn').show();
                                        $alertContainer.empty();
                                    } else {
                                        $('#applyModalBody').html(
                                            '<div class="alert alert-danger d-flex align-items-center" role="alert">' +
                                            response.message +
                                            '</div>'
                                        );
                                        $alertContainer.html(
                                            '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">' +
                                            response.message +
                                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                            '</div>'
                                        );
                                    }
                                },
                                error: function () {
                                    $('#applyModalBody').html(
                                        '<div class="alert alert-danger d-flex align-items-center" role="alert">' +
                                        'Có lỗi xảy ra. Vui lòng thử lại.' +
                                        '</div>'
                                    );
                                    $alertContainer.html(
                                        '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">' +
                                        'Có lỗi xảy ra. Vui lòng thử lại.' +
                                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                        '</div>'
                                    );
                                }
                            });
                        });
                    });
                </script>

                <!-- Modal Báo cáo -->
                <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content border-0 shadow-lg">

                            <!-- Header -->
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title d-flex align-items-center gap-2 text-white" id="reportModalLabel">
                                    <i class="bi bi-flag-fill"></i>
                                    Báo cáo công việc
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Đóng"></button>
                            </div>

                            <!-- Body -->
                            <div class="modal-body">
                                <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Hãy báo cáo công việc nếu bạn thấy có dấu hiệu <strong>lừa đảo hoặc sai phạm</strong>.
                                </div>

                                <form id="reportForm" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="job_id" id="reportJobId">

                                    <!-- Lý do -->
                                    <div class="mb-3">
                                        <label for="reportReason" class="form-label fw-semibold">
                                            Lý do báo cáo <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="reportReason" name="reason" required>
                                            <option value="" selected disabled>-- Chọn lý do --</option>
                                            <option value="Lừa đảo / Gian lận">Lừa đảo / Gian lận</option>
                                            <option value="Thông tin sai sự thật">Thông tin sai sự thật</option>
                                            <option value="Nội dung không phù hợp">Nội dung không phù hợp</option>
                                            <option value="Spam hoặc quảng cáo">Spam hoặc quảng cáo</option>
                                            <option value="Khác">Khác</option>
                                        </select>
                                    </div>

                                    <!-- Mô tả -->
                                    <div class="mb-3">
                                        <label for="reportMessage" class="form-label fw-semibold">
                                            Mô tả chi tiết <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="reportMessage" name="message" rows="4" required
                                            placeholder="Hãy mô tả chi tiết vấn đề bạn gặp phải..."></textarea>
                                    </div>

                                    <!-- Khu vực tải ảnh -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Đính kèm hình ảnh (tối đa 5)</label>

                                        <div id="dropZone"
                                            class="border border-2 border-danger border-dashed rounded-4 p-4 text-center bg-light-subtle position-relative"
                                            role="button" tabindex="0">
                                            <i class="bi bi-cloud-arrow-up fs-1 text-danger"></i>
                                            <p class="mb-0 mt-2 text-muted">Kéo thả hình ảnh vào đây hoặc nhấn để chọn</p>

                                            <!-- Input file ẩn an toàn -->
                                            <input type="file" id="reportImages" name="images[]" accept="image/*" multiple
                                                style="position:absolute; inset:0; opacity:0; cursor:pointer;">
                                        </div>

                                        <div class="form-text text-muted mt-1">Chọn tối đa 5 ảnh (jpg, png, webp...)</div>

                                        <!-- Preview -->
                                        <div id="imagePreview" class="mt-3 d-flex flex-wrap gap-2"></div>
                                    </div>
                                </form>

                                <div id="reportAlert"></div>
                            </div>

                            <!-- Footer -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i> Đóng
                                </button>
                                <button type="button" class="btn btn-danger" id="submitReport">
                                    <i class="bi bi-send-fill me-1"></i> Gửi báo cáo
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <style>
                    /* Hiệu ứng viền đứt */
                    .border-dashed {
                        border-style: dashed !important;
                        transition: all 0.25s ease;
                    }

                    #dropZone {
                        cursor: pointer;
                    }

                    #dropZone.dragover {
                        background-color: #fff5f5;
                        border-color: #dc3545;
                        box-shadow: 0 0 0.5rem rgba(220, 53, 69, 0.3);
                    }

                    /* Preview ảnh */
                    #imagePreview .thumb {
                        position: relative;
                        width: 90px;
                        height: 90px;
                        border-radius: 0.5rem;
                        overflow: hidden;
                        border: 2px dashed #dc3545;
                        transition: transform 0.2s;
                    }

                    #imagePreview .thumb img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }

                    #imagePreview .thumb:hover {
                        transform: scale(1.05);
                    }

                    /* Nút xóa ảnh */
                    #imagePreview .remove-btn {
                        position: absolute;
                        top: 4px;
                        right: 4px;
                        background: rgba(0, 0, 0, 0.6);
                        color: #fff;
                        border: none;
                        width: 22px;
                        height: 22px;
                        border-radius: 50%;
                        font-size: 16px;
                        line-height: 1;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        opacity: 0;
                        transition: all 0.2s ease;
                        cursor: pointer;
                    }

                    #imagePreview .thumb:hover .remove-btn {
                        opacity: 1;
                    }
                </style>

                <script>
                    $(function () {
                        const dropZone = $('#dropZone');
                        const fileInput = $('#reportImages');
                        const preview = $('#imagePreview');
                        const alertBox = $('#reportAlert');
                        const MAX_FILES = 5;
                        let selectedFiles = [];

                        function renderPreview() {
                            preview.empty();
                            selectedFiles.forEach((file, i) => {
                                const url = URL.createObjectURL(file);
                                const thumb = $(`
                      <div class="thumb" data-index="${i}">
                        <img src="${url}" alt="">
                        <button type="button" class="remove-btn">&times;</button>
                      </div>
                    `);

                                thumb.find('.remove-btn').on('click', (e) => {
                                    e.stopPropagation();
                                    selectedFiles.splice(i, 1);
                                    renderPreview();
                                });

                                preview.append(thumb);
                            });

                            const dt = new DataTransfer();
                            selectedFiles.forEach(f => dt.items.add(f));
                            fileInput[0].files = dt.files;
                        }

                        function addFiles(files) {
                            const incoming = Array.from(files).filter(f => f.type.startsWith('image/'));
                            if (selectedFiles.length + incoming.length > MAX_FILES) {
                                showAlert(`Chỉ được chọn tối đa ${MAX_FILES} ảnh.`, 'danger');
                                return;
                            }
                            selectedFiles = selectedFiles.concat(incoming);
                            renderPreview();
                        }

                        function showAlert(msg, type = 'danger') {
                            alertBox.html(`<div class="alert alert-${type} mt-3">${msg}</div>`);
                        }

                        // Kéo thả ảnh
                        dropZone.on('dragover', (e) => {
                            e.preventDefault();
                            dropZone.addClass('dragover');
                        });

                        dropZone.on('dragleave drop', (e) => {
                            e.preventDefault();
                            dropZone.removeClass('dragover');
                        });

                        dropZone.on('drop', (e) => {
                            const files = e.originalEvent.dataTransfer.files;
                            addFiles(files);
                        });

                        // Chọn ảnh qua input
                        fileInput.on('change', function () {
                            addFiles(this.files);
                            $(this).val('');
                        });

                        // Mở modal
                        $('.report-floating').on('click', function () {
                            const jobId = $(this).data('job-id');
                            $('#reportJobId').val(jobId);
                            $('#reportForm')[0].reset();
                            selectedFiles = [];
                            preview.html('');
                            alertBox.html('');
                            new bootstrap.Modal(document.getElementById('reportModal')).show();
                        });

                        // Gửi form
                        $('#submitReport').on('click', function () {
                            const jobId = $('#reportJobId').val();
                            const reason = $('#reportReason').val();
                            const message = $('#reportMessage').val().trim();

                            if (!reason) return showAlert('Vui lòng chọn lý do báo cáo.');
                            if (!message) return showAlert('Vui lòng nhập mô tả chi tiết.');

                            const formData = new FormData();
                            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                            formData.append('job_id', jobId);
                            formData.append('reason', reason);
                            formData.append('message', message);
                            selectedFiles.forEach(f => formData.append('images[]', f));

                            alertBox.html(`
                    <div class="d-flex align-items-center justify-content-center my-3 text-muted">
                      <div class="spinner-border spinner-border-sm text-danger me-2"></div> Đang gửi báo cáo...
                    </div>
                  `);

                            $.ajax({
                                url: '/jobs/report/' + jobId,
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: res => {
                                    if (res.success) {
                                        showAlert('Báo cáo của bạn đã được gửi. Cảm ơn bạn!', 'success');
                                        setTimeout(() => $('#reportModal').modal('hide'), 1500);
                                    } else showAlert(res.message || 'Không thể gửi báo cáo.');
                                },
                                error: () => showAlert('Đã xảy ra lỗi. Vui lòng thử lại sau.')
                            });
                        });
                    });
                </script>


            </div>
        </div>

        </div>
    </main>
@endsection