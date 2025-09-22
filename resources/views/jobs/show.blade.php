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
                                <!-- Blog Details Section 
                                        <div class="post-img">
                                            <img src="{{ asset('assets/img/blog/blog-1.jpg') }}" alt="" class="img-fluid">
                                        </div>-->

                                <h2 class="title">{{ $job->title }}
                                </h2>

                                <div class="meta-top">
                                    <ul>
                                        <li class="d-flex align-items-center"><i class="bi bi-person"></i> <a
                                                href="blog-details.html">{{ $job->account->name ?? 'Người đăng ẩn danh' }}</a>
                                        </li>
                                        <li class="d-flex align-items-center"><i class="bi bi-clock"></i> <a
                                                href="blog-details.html">{{ $job->created_at->format('h:i:s A d/m/Y') }}</time></a>
                                        </li>
                                        <li class="d-flex align-items-center"><i class="bi bi-chat-dots"></i> <a
                                                href="blog-details.html">12 Bình luận</a></li>
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
                                        <li><a href="#">{{ $job->category }}</a></li>
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
                                <img src="{{ optional($job->account)->avatar_url ?? asset('assets/img/blog/blog-author.jpg') }}"
                                    class="rounded-circle flex-shrink-0" alt="">
                                <div>
                                    <h4>{{ $job->account->name ?? 'Người đăng ẩn danh' }}</h4>
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

                        <div class="container">

                            <h4 class="comments-count">8 Comments</h4>

                            <div id="comment-1" class="comment">
                                <div class="d-flex">
                                    <div class="comment-img"><img src="{{ asset('assets/img/blog/comments-1.jpg') }}"
                                            alt=""></div>
                                    <div>
                                        <h5><a href="">Georgia Reader</a> <a href="#" class="reply"><i
                                                    class="bi bi-reply-fill"></i> Reply</a></h5>
                                        <time datetime="2020-01-01">01 Jan,2022</time>
                                        <p>
                                            Et rerum totam nisi. Molestiae vel quam dolorum vel voluptatem et et. Est ad aut
                                            sapiente quis molestiae est qui cum soluta.
                                            Vero aut rerum vel. Rerum quos laboriosam placeat ex qui. Sint qui facilis et.
                                        </p>
                                    </div>
                                </div>
                            </div><!-- End comment #1 -->

                            <div id="comment-2" class="comment">
                                <div class="d-flex">
                                    <div class="comment-img"><img src="{{ asset('assets/img/blog/comments-2.jpg') }}"
                                            alt=""></div>
                                    <div>
                                        <h5><a href="">Aron Alvarado</a> <a href="#" class="reply"><i
                                                    class="bi bi-reply-fill"></i> Reply</a></h5>
                                        <time datetime="2020-01-01">01 Jan,2022</time>
                                        <p>
                                            Ipsam tempora sequi voluptatem quis sapiente non. Autem itaque eveniet saepe.
                                            Officiis illo ut beatae.
                                        </p>
                                    </div>
                                </div>

                                <div id="comment-reply-1" class="comment comment-reply">
                                    <div class="d-flex">
                                        <div class="comment-img"><img src="{{ asset('assets/img/blog/comments-3.jpg') }}"
                                                alt=""></div>
                                        <div>
                                            <h5><a href="">Lynda Small</a> <a href="#" class="reply"><i
                                                        class="bi bi-reply-fill"></i> Reply</a></h5>
                                            <time datetime="2020-01-01">01 Jan,2022</time>
                                            <p>
                                                Enim ipsa eum fugiat fuga repellat. Commodi quo quo dicta. Est ullam
                                                aspernatur ut vitae quia mollitia id non. Qui ad quas nostrum rerum sed
                                                necessitatibus aut est. Eum officiis sed repellat maxime vero nisi natus.
                                                Amet nesciunt nesciunt qui illum omnis est et dolor recusandae.

                                                Recusandae sit ad aut impedit et. Ipsa labore dolor impedit et natus in
                                                porro aut. Magnam qui cum. Illo similique occaecati nihil modi eligendi.
                                                Pariatur distinctio labore omnis incidunt et illum. Expedita et dignissimos
                                                distinctio laborum minima fugiat.

                                                Libero corporis qui. Nam illo odio beatae enim ducimus. Harum reiciendis
                                                error dolorum non autem quisquam vero rerum neque.
                                            </p>
                                        </div>
                                    </div>

                                    <div id="comment-reply-2" class="comment comment-reply">
                                        <div class="d-flex">
                                            <div class="comment-img"><img
                                                    src="{{ asset('assets/img/blog/comments-4.jpg') }}" alt=""></div>
                                            <div>
                                                <h5><a href="">Sianna Ramsay</a> <a href="#" class="reply"><i
                                                            class="bi bi-reply-fill"></i> Reply</a></h5>
                                                <time datetime="2020-01-01">01 Jan,2022</time>
                                                <p>
                                                    Et dignissimos impedit nulla et quo distinctio ex nemo. Omnis quia
                                                    dolores cupiditate et. Ut unde qui eligendi sapiente omnis ullam.
                                                    Placeat porro est commodi est officiis voluptas repellat quisquam
                                                    possimus. Perferendis id consectetur necessitatibus.
                                                </p>
                                            </div>
                                        </div>

                                    </div><!-- End comment reply #2-->

                                </div><!-- End comment reply #1-->

                            </div><!-- End comment #2-->

                            <div id="comment-3" class="comment">
                                <div class="d-flex">
                                    <div class="comment-img"><img src="{{ asset('assets/img/blog/comments-5.jpg') }}"
                                            alt=""></div>
                                    <div>
                                        <h5><a href="">Nolan Davidson</a> <a href="#" class="reply"><i
                                                    class="bi bi-reply-fill"></i> Reply</a></h5>
                                        <time datetime="2020-01-01">01 Jan,2022</time>
                                        <p>
                                            Distinctio nesciunt rerum reprehenderit sed. Iste omnis eius repellendus quia
                                            nihil ut accusantium tempore. Nesciunt expedita id dolor exercitationem
                                            aspernatur aut quam ut. Voluptatem est accusamus iste at.
                                            Non aut et et esse qui sit modi neque. Exercitationem et eos aspernatur. Ea est
                                            consequuntur officia beatae ea aut eos soluta. Non qui dolorum voluptatibus et
                                            optio veniam. Quam officia sit nostrum dolorem.
                                        </p>
                                    </div>
                                </div>

                            </div><!-- End comment #3 -->

                            <div id="comment-4" class="comment">
                                <div class="d-flex">
                                    <div class="comment-img"><img src="{{ asset('assets/img/blog/comments-6.jpg') }}"
                                            alt=""></div>
                                    <div>
                                        <h5><a href="">Kay Duggan</a> <a href="#" class="reply"><i
                                                    class="bi bi-reply-fill"></i> Reply</a></h5>
                                        <time datetime="2020-01-01">01 Jan,2022</time>
                                        <p>
                                            Dolorem atque aut. Omnis doloremque blanditiis quia eum porro quis ut velit
                                            tempore. Cumque sed quia ut maxime. Est ad aut cum. Ut exercitationem non in
                                            fugiat.
                                        </p>
                                    </div>
                                </div>

                            </div><!-- End comment #4 -->

                        </div>

                    </section><!-- /Blog Comments Section -->

                    <!-- Comment Form Section -->
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
                    </section><!-- /Comment Form Section -->

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
                                <li><a href="#">General <span>(25)</span></a></li>
                                <li><a href="#">Lifestyle <span>(12)</span></a></li>
                                <li><a href="#">Travel <span>(5)</span></a></li>
                                <li><a href="#">Design <span>(22)</span></a></li>
                                <li><a href="#">Creative <span>(8)</span></a></li>
                                <li><a href="#">Educaion <span>(14)</span></a></li>
                            </ul>

                        </div><!--/Categories Widget -->

                        <!-- Recent Posts Widget -->
                        <div class="recent-posts-widget widget-item">

                            <h3 class="widget-title">Bài viết liên quan</h3>

                            <div class="post-item">
                                <img src="{{ asset('assets/img/blog/blog-recent-1.jpg') }}" alt="" class="flex-shrink-0">
                                <div>
                                    <h4><a href="blog-details.html">Nihil blanditiis at in nihil autem</a></h4>
                                    <time datetime="2020-01-01">Jan 1, 2020</time>
                                </div>
                            </div><!-- End recent post item-->

                            <div class="post-item">
                                <img src="{{ asset('assets/img/blog/blog-recent-2.jpg') }}" alt="" class="flex-shrink-0">
                                <div>
                                    <h4><a href="blog-details.html">Quidem autem et impedit</a></h4>
                                    <time datetime="2020-01-01">Jan 1, 2020</time>
                                </div>
                            </div><!-- End recent post item-->

                            <div class="post-item">
                                <img src="{{ asset('assets/img/blog/blog-recent-3.jpg') }}" alt="" class="flex-shrink-0">
                                <div>
                                    <h4><a href="blog-details.html">Id quia et et ut maxime similique occaecati ut</a></h4>
                                    <time datetime="2020-01-01">Jan 1, 2020</time>
                                </div>
                            </div><!-- End recent post item-->

                            <div class="post-item">
                                <img src="{{ asset('assets/img/blog/blog-recent-4.jpg') }}" alt="" class="flex-shrink-0">
                                <div>
                                    <h4><a href="blog-details.html">Laborum corporis quo dara net para</a></h4>
                                    <time datetime="2020-01-01">Jan 1, 2020</time>
                                </div>
                            </div><!-- End recent post item-->

                            <div class="post-item">
                                <img src="{{ asset('assets/img/blog/blog-recent-5.jpg') }}" alt="" class="flex-shrink-0">
                                <div>
                                    <h4><a href="blog-details.html">Et dolores corrupti quae illo quod dolor</a></h4>
                                    <time datetime="2020-01-01">Jan 1, 2020</time>
                                </div>
                            </div><!-- End recent post item-->

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
                <!-- Floating Apply Button -->
                @php
                    $userId = auth()->check() ? auth()->id() : null;
                    $hasApplied = $userId && $job->apply_id ? in_array($userId, explode(',', $job->apply_id)) : false;
                    $isExpired = $job->deadline && \Carbon\Carbon::parse($job->deadline)->lt(\Carbon\Carbon::now());
                @endphp

                @if(!$hasApplied && !$isExpired)
                    <a href="javascript:void(0);" class="btn btn-success rounded-circle apply-floating apply-btn"
                        data-job-id="{{ $job->job_id }}" data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Ứng Tuyển Ngay">
                        <i class="bi bi-briefcase-fill"></i>
                    </a>
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
                            var jobId = $(this).data('job-id');

                            // Hiển thị modal ngay, hiện spinner
                            $('#applyModalBody').html($('#applySpinner').show());
                            $('#loginBtn').hide();
                            var modal = new bootstrap.Modal(document.getElementById('applyModal'));
                            modal.show();

                            $.ajax({
                                url: '/jobs/apply/' + jobId,
                                method: 'GET',
                                success: function (response) {
                                    // Ẩn spinner và hiển thị thông báo
                                    $('#applySpinner').hide();
                                    $('#applyModalBody').html(response.message);

                                    if (response.login_required) {
                                        $('#loginBtn').show();
                                    } else {
                                        $('#loginBtn').hide();
                                    }
                                },
                                error: function () {
                                    $('#applySpinner').hide();
                                    $('#applyModalBody').html('Có lỗi xảy ra. Vui lòng thử lại.');
                                    $('#loginBtn').hide();
                                }
                            });
                        });
                    });
                </script>

                <style>
                    .apply-floating {
                        position: fixed;
                        bottom: 75px;
                        right: 8px;
                        width: 55px;
                        height: 55px;
                        padding: 0;
                        z-index: 9999;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 24px;
                        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
                        transition: transform 0.2s, background-color 0.2s;
                    }

                    .apply-floating:hover {
                        transform: translateY(-3px);
                        background-color: #198754;
                    }
                </style>

            </div>
        </div>

        </div>
    </main>
@endsection