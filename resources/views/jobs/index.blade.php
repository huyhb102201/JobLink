@extends('layouts.app')
@section('title', 'JobLink - Công việc')
@section('content')
    <main class="main">
        <!-- Page Title -->
        <div class="page-title">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Danh sách công việc</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li class="current">Công việc</li>
                    </ol>
                </nav>
            </div>
        </div><!-- End Page Title -->

        <!-- Job List Section -->
        <section id="blog-posts" class="blog-posts section">
            <div class="container" id="jobs-list">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    @foreach($jobs as $job)
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                {{-- Ảnh job --}}
                                <img src="{{ $job->image ?? asset('assets/img/blog/blog-1.jpg') }}" class="card-img-top"
                                    alt="{{ $job->title }}">

                                <div class="card-body d-flex flex-column">
                                    {{-- Category --}}
                                    <span class="badge bg-primary mb-2">{{ $job->category ?? 'Khác' }}</span>

                                    {{-- Tiêu đề job --}}
                                    <h5 class="card-title">
                                        <a href="#" class="text-decoration-none">{{ $job->title }}</a>
                                    </h5>

                                    {{-- Mô tả ngắn --}}
                                    <p class="card-text text-truncate">
                                        {{ Str::limit($job->description, 120) }}
                                    </p>

                                    {{-- Thông tin job --}}
                                    <ul class="list-unstyled mt-auto mb-3">
                                        <li class="mb-1">
                                            <i class="bi bi-currency-dollar me-1"></i>
                                            <strong>Ngân sách:</strong> {{ number_format($job->budget, 0, ',', '.') }}
                                            {{ $job->payment_type }}
                                        </li>
                                        <li class="mb-1">
                                            <i class="bi bi-info-circle me-1"></i>
                                            <strong>Trạng thái:</strong> {{ ucfirst($job->status) }}
                                        </li>
                                        <li>
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <strong>Hạn cuối:</strong>
                                            {{ \Carbon\Carbon::parse($job->deadline)->format('d/m/Y') }}
                                        </li>
                                    </ul>

                                    {{-- Người đăng --}}
                                    <div class="d-flex align-items-center mt-3 border-top pt-3">
                                        <img src="{{ optional($job->account)->avatar_url ?? asset('assets/img/blog/blog-author.jpg') }}"
                                            alt="{{ optional($job->account)->name ?? 'Người đăng' }}"
                                            class="rounded-circle me-2" width="40" height="40">

                                        <div>
                                            <p class="mb-0 fw-bold">{{ $job->account->name ?? 'Người đăng ẩn danh' }}</p>
                                            <p class="mb-0 text-muted">
                                                <time datetime="{{ $job->created_at }}">
                                                    Đăng ngày {{ $job->created_at->format('h:i:s A d/m/Y') }}
                                                </time>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Button xem chi tiết --}}
                                <div class="card-footer bg-transparent border-top-0 pt-0 mt-2">
                                    <a href="{{ route('jobs.show', $job->job_id) }}"
                                        class="btn btn-sm btn-outline-primary w-100">
                                        Xem chi tiết
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </section>

        <!-- Pagination Section -->
        <div id="pagination-wrapper">
            {{ $jobs->links('components.pagination') }}
        </div>
    </main>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- AJAX pagination --}}
    <script>
        $(document).on('click', '.page-link', function (e) {
            e.preventDefault();
            let page = $(this).data('page');

            $.ajax({
                url: "/jobs?page=" + page,
                type: "GET",
                success: function (res) {
                    $("#jobs-list").html($(res).find("#jobs-list").html());
                    $("#pagination-wrapper").html($(res).find("#pagination-wrapper").html());
                }
            });
        });
    </script>
@endsection