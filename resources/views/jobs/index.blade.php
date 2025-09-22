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
                        <div class="offcanvas offcanvas-end d-xl-none" tabindex="-1" id="offcanvasSidebar"
                            aria-labelledby="offcanvasSidebarLabel" data-bs-backdrop="true">
                            <div class="offcanvas-header">
                                <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Bộ lọc nâng cao</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                    aria-label="Close"></button>
                            </div>
                            <div class="offcanvas-body">
                                <h6 class="mb-3">Mức lương</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="duoi500" id="m_salary1"
                                        name="gia[]">
                                    <label class="form-check-label" for="m_salary1">Dưới 500.000</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="500k1m" id="m_salary2"
                                        name="gia[]">
                                    <label class="form-check-label" for="m_salary2">500.000 - 1.000.000</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1m2m" id="m_salary3"
                                        name="gia[]">
                                    <label class="form-check-label" for="m_salary3">1 - 2.000.000</label>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Job Cards -->
                    <div class="col-xl-8 col-xxl-9">
                        {{-- Bao toàn bộ partial --}}
                         @include('jobs.partials.jobs-list')
                    </div>


                </div>
            </div>
        </section>
    </main>

   <script>
function loadJobs(page) {
    $("#jobs-list").fadeTo(200, 0.5);

    $.ajax({
        url: "/jobs?page=" + page,
        type: "GET",
        success: function(res) {
            const newJobs = $(res).find("#jobs-list").html();
            const newPagination = $(res).find("#pagination-wrapper").html();

            if (!newJobs) {
                alert('Không load được dữ liệu mới từ server.');
                $("#jobs-list").fadeTo(200, 1);
                return;
            }

            $("#jobs-list").html(newJobs);
            $("#pagination-wrapper").html(newPagination);
            $("#jobs-list").fadeTo(200, 1);
        },
        error: function() {
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
            $("#jobs-list").fadeTo(200, 1);
        }
    });
}

// Click phân trang
$(document).on('click', '.ajax-page-link', function(e) {
    e.preventDefault();
    let page = $(this).data('page');
    loadJobs(page);
    history.pushState({ page: page }, '', '?page=' + page);
});

// Back/forward
window.onpopstate = function(event) {
    let page = event.state?.page || 1;
    loadJobs(page);
};

</script>


@endsection