@extends('layouts.app')
@section('title', 'JobLink - Công việc')
@section('content')
    <main class="main">
        <!-- Page Title -->
        <div class="page-title py-4 bg-light">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Danh sách công việc</h1>
                <nav class="breadcrumbs">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item active">Công việc</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Job List Section -->
        <section class="pt-4">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                        <!-- Filter Button (Mobile) -->
                        <button class="btn btn-primary d-xl-none mb-2" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                            <i class="fa-solid fa-sliders-h me-1"></i> Bộ lọc
                        </button>
                        <!-- View Mode Tabs -->
                        <ul class="nav nav-pills mb-2">
                            <li class="nav-item">
                                <a class="nav-link active" href="job-list.php"><i class="bi bi-list-ul"></i></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="job-grid.php"><i class="bi bi-grid-fill"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="row">
                    <!-- Sidebar Filters -->
                   <!-- Sidebar Filters -->
<aside class="col-xl-4 col-xxl-3 mb-4 mb-xl-0">
    <!-- Desktop Sidebar (sticky, luôn hiện) -->
    <div class="card p-3 d-none d-xl-block sticky-top" style="top: 80px;">
        <h6 class="mb-3">Mức lương</h6>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="duoi500" id="salary1" name="gia[]">
            <label class="form-check-label" for="salary1">Dưới 500.000</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="500k1m" id="salary2" name="gia[]">
            <label class="form-check-label" for="salary2">500.000 - 1.000.000</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1m2m" id="salary3" name="gia[]">
            <label class="form-check-label" for="salary3">1 - 2.000.000</label>
        </div>
    </div>

    <!-- Mobile Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-end d-xl-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel" data-bs-backdrop="true">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Bộ lọc nâng cao</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <h6 class="mb-3">Mức lương</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="duoi500" id="m_salary1" name="gia[]">
                <label class="form-check-label" for="m_salary1">Dưới 500.000</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="500k1m" id="m_salary2" name="gia[]">
                <label class="form-check-label" for="m_salary2">500.000 - 1.000.000</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1m2m" id="m_salary3" name="gia[]">
                <label class="form-check-label" for="m_salary3">1 - 2.000.000</label>
            </div>
        </div>
    </div>
</aside>



                    <!-- Job Cards -->
                    <div class="col-xl-8 col-xxl-9">
                        <div class="row g-3"> <!-- g-3 giảm khoảng cách giữa các row -->

                            @foreach($jobs as $job)
                                <div class="col-12">
                                    <div class="card shadow-sm h-100 hover-shadow">
                                        <div class="row g-0">
                                            <!-- Ảnh job -->
                                            <div class="col-md-4 d-flex justify-content-center align-items-center p-2">
                                                <img src="{{ $job->image ?? asset('assets/img/blog/blog-1.jpg') }}"
                                                    class="img-fluid rounded" alt="{{ $job->title }}">
                                            </div>

                                            <!-- Thông tin job -->
                                            <div class="col-md-8">
                                                <div class="card-body d-flex flex-column h-100">
                                                    <h5 class="card-title">{{ $job->title }}</h5>
                                                    <p class="text-muted mb-2">
                                                        <i class="bi bi-tags me-1"></i>{{ $job->jobCategory->name ?? 'Khác' }}
                                                    </p>

                                                    <p class="mb-2 fs-5">
                                                        <strong>{{ number_format($job->salary ?? $job->budget, 0, ',', '.') }}
                                                            VNĐ</strong> /
                                                        {{ $job->payment_type ?? 'tháng' }}
                                                    </p>

                                                    <p class="text-truncate mb-2">{{ Str::limit($job->description, 120) }}</p>

                                                    <!-- Thông tin người đăng -->
                                                    <div class="d-flex align-items-center mb-2">
                                                        <img src="{{ optional($job->account)->avatar_url ?? asset('assets/img/blog/blog-author.jpg') }}"
                                                            alt="{{ optional($job->account)->name ?? 'Người đăng' }}"
                                                            class="rounded-circle me-2" width="40" height="40">
                                                        <div>
                                                            <p class="mb-0 fw-bold">
                                                                {{ $job->account->name ?? 'Người đăng ẩn danh' }}
                                                            </p>
                                                            <p class="mb-0 text-muted">
                                                                <time datetime="{{ $job->created_at }}">
                                                                    Đăng ngày {{ $job->created_at->format('h:i:s A d/m/Y') }}
                                                                </time>
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Nút chi tiết và yêu thích -->
                                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                                        <a href="{{ route('jobs.show', $job->job_id) }}"
                                                            class="btn btn-sm text-white"
                                                            style="background-color: #0ea2bd; border-color: #0ea2bd;">
                                                            Xem chi tiết
                                                        </a>

                                                        <button
                                                            class="btn p-0 text-danger fs-5 d-flex align-items-center justify-content-center"
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

                        <!-- Pagination -->
                        <div class="mt-4" id="pagination-wrapper">
                            {{ $jobs->links('components.pagination') }}
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </main>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- AJAX Pagination --}}
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