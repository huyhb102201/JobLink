@extends('layouts.app')
@section('title', 'JobLink - Công việc')
@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <style>
        .choices__list--dropdown {
            position: absolute;
            z-index: 9999 !important;
        }

        /* CSS for skeleton loading */
        .skeleton {
            background-color: #f0f0f0;
            border-radius: 4px;
            animation: skeleton-loading 1s linear infinite alternate;
        }

        @keyframes skeleton-loading {
            0% {
                background-color: #f0f0f0;
            }

            100% {
                background-color: #e0e0e0;
            }
        }

        .skeleton-job {
            height: 150px;
            /* Estimated height of each job card */
            margin-bottom: 20px;
        }

        /* Fade effect for loading */
        #jobs-list,
        #pagination-wrapper {
            transition: opacity 0.3s ease-in-out;
        }

        .loading {
            opacity: 0.3;
            /* Dim the content during loading */
        }

        .skeleton-container {
            display: none;
            /* Hidden by default */
        }

        .skeleton-container.active {
            display: block;
            /* Show skeletons during loading */
        }
    </style>
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
                        <button class="btn btn-primary d-xl-none mb-2" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                            <i class="fa-solid fa-sliders-h me-1"></i> Bộ lọc
                        </button>
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
                    <aside class="col-xl-4 col-xxl-3 mb-4 mb-xl-0">
                        <div class="card p-3 d-none d-xl-block sticky-top" style="top: 80px;">
                            <h6 class="mb-3">Hình thức trả lương</h6>
                            <div class="form-check cursor-pointer mb-2">
                                <input class="form-check-input" type="checkbox" value="fixed" id="type1"
                                    name="payment_type[]">
                                <label class="form-check-label" for="type1">Cố định</label>
                            </div>
                            <div class="form-check cursor-pointer mb-2">
                                <input class="form-check-input" type="checkbox" value="hourly" id="type2"
                                    name="payment_type[]">
                                <label class="form-check-label" for="type2">Theo giờ</label>
                            </div>
                            <hr>
                            <h6 class="mb-3">Trạng thái công việc</h6>
                            <div class="form-check cursor-pointer mb-2">
                                <input class="form-check-input" type="checkbox" value="open" id="status1" name="status[]">
                                <label class="form-check-label" for="status1">Đang tuyển</label>
                            </div>
                            <div class="form-check cursor-pointer mb-2">
                                <input class="form-check-input" type="checkbox" value="in_progress" id="status2"
                                    name="status[]">
                                <label class="form-check-label" for="status2">Đang làm</label>
                            </div>
                            <div class="form-check cursor-pointer mb-2">
                                <input class="form-check-input" type="checkbox" value="completed" id="status3"
                                    name="status[]">
                                <label class="form-check-label" for="status3">Hoàn thành</label>
                            </div>
                            <hr>
                            <h6 class="mb-3">Loại công việc</h6>
                            <select id="category" name="category[]" multiple></select>
                        </div>

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

                    <div class="col-xl-8 col-xxl-9">
                        <div id="jobs-list">
                            <!-- Skeleton loading placeholder -->
                            <div class="skeleton-container">
                                <div class="skeleton skeleton-job"></div>
                                <div class="skeleton skeleton-job"></div>
                                <div class="skeleton skeleton-job"></div>
                                <div class="skeleton skeleton-job"></div>
                                <div class="skeleton skeleton-job"></div>
                            </div>
                        </div>
                        <div id="pagination-wrapper" class="mt-4">
                            {{ $jobs->links('components.pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Initialize categories from server
        const categories = @json(\App\Models\JobCategory::all());
        const categorySelect = document.getElementById('category');
        let choices = null;

        // Add options to select
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.category_id;
            option.textContent = category.name;
            categorySelect.appendChild(option);
        });

        // Initialize Choices.js only once
        function initializeChoices() {
            if (!choices) {
                choices = new Choices(categorySelect, {
                    removeItemButton: true,
                    placeholderValue: 'Chọn loại công việc',
                    searchPlaceholderValue: 'Tìm kiếm...',
                    shouldSort: false, // Keep server order
                });
            }
        }

        $(document).ready(function () {
            // Cache for AJAX results (key: JSON.stringify(filters), value: {jobs, pagination, timestamp})
            const cache = {};
            const CACHE_EXPIRATION = 5 * 60 * 1000; // 5 minutes

            // Get filter data from localStorage
            function getFilterData() {
                let storedFilters = JSON.parse(localStorage.getItem('jobFilters')) || {};
                let payment_type = Array.isArray(storedFilters.payment_type) ? storedFilters.payment_type : [];
                let status = Array.isArray(storedFilters.status) ? storedFilters.status : [];
                let category = Array.isArray(storedFilters.category) ? storedFilters.category.map(String) : [];
                let gia = Array.isArray(storedFilters.gia) ? storedFilters.gia : [];

                // Update payment_type checkboxes
                $("input[name='payment_type[]']").each(function () {
                    $(this).prop('checked', payment_type.includes($(this).val()));
                });

                // Update status checkboxes
                $("input[name='status[]']").each(function () {
                    $(this).prop('checked', status.includes($(this).val()));
                });

                // Update gia checkboxes (mobile)
                $("input[name='gia[]']").each(function () {
                    $(this).prop('checked', gia.includes($(this).val()));
                });

                // Update Choices.js with categories from localStorage
                if (category.length > 0) {
                    initializeChoices();
                    const validCategories = category.filter(id => categories.find(cat => cat.category_id == id));
                    choices.clearStore();
                    choices.setChoices(
                        categories.map(cat => ({
                            value: cat.category_id,
                            label: cat.name,
                            selected: validCategories.includes(String(cat.category_id))
                        })),
                        'value',
                        'label',
                        true
                    );
                    choices.setChoiceByValue(validCategories);
                } else {
                    initializeChoices();
                }

                return { payment_type, status, category, gia, page: storedFilters.page || 1 };
            }

            // Save filter data to localStorage
            function saveFilterData(filters) {
                localStorage.setItem('jobFilters', JSON.stringify(filters));
            }

            // Load jobs via AJAX with cache and fade effect
            function loadJobs(page = null) {
                let filters = getFilterData();
                if (page !== null) filters.page = page;
                saveFilterData(filters);

                // Create cache key
                const cacheKey = JSON.stringify(filters);

                // Check cache
                const cachedData = cache[cacheKey];
                if (cachedData && (Date.now() - cachedData.timestamp < CACHE_EXPIRATION)) {
                    // Use cached data
                    $("#jobs-list").html(cachedData.jobs);
                    $("#pagination-wrapper").html(cachedData.pagination);
                    $("#jobs-list").removeClass('loading');
                    $("#pagination-wrapper").removeClass('loading');
                    $(".skeleton-container").removeClass('active');
                    return;
                }

                // Show loading state
                $("#jobs-list").addClass('loading');
                $("#pagination-wrapper").addClass('loading');
                $(".skeleton-container").addClass('active');

                // Perform AJAX request
                $.ajax({
                    url: "/jobs",
                    type: "GET",
                    data: filters,
                    dataType: "json",
                    success: function (res) {
                        if (!res.jobs) {
                            alert('Không load được dữ liệu mới từ server.');
                            return;
                        }
                        // Update content and remove loading state
                        $("#jobs-list").html(res.jobs);
                        $("#pagination-wrapper").html(res.pagination);
                        $("#jobs-list").removeClass('loading');
                        $("#pagination-wrapper").removeClass('loading');
                        $(".skeleton-container").removeClass('active');

                        // Cache the response
                        cache[cacheKey] = {
                            jobs: res.jobs,
                            pagination: res.pagination,
                            timestamp: Date.now()
                        };
                    },
                    error: function () {
                        alert('Có lỗi xảy ra. Vui lòng thử lại.');
                        $("#jobs-list").removeClass('loading');
                        $("#pagination-wrapper").removeClass('loading');
                        $(".skeleton-container").removeClass('active');
                    }
                });
            }

            // Handle pagination click
            $(document).on('click', '.ajax-page-link', function (e) {
                e.preventDefault();
                let page = $(this).data('page');
                loadJobs(page);
            });

            // Handle payment_type checkbox change
            $("input[name='payment_type[]']").on('change', function () {
                let payment_type = [];
                $("input[name='payment_type[]']:checked").each(function () {
                    payment_type.push($(this).val());
                });
                let filters = getFilterData();
                filters.payment_type = payment_type;
                filters.page = 1;
                saveFilterData(filters);
                loadJobs(1);
            });

            // Handle status checkbox change
            $("input[name='status[]']").on('change', function () {
                let status = [];
                $("input[name='status[]']:checked").each(function () {
                    status.push($(this).val());
                });
                let filters = getFilterData();
                filters.status = status;
                filters.page = 1;
                saveFilterData(filters);
                loadJobs(1);
            });

            // Handle gia checkbox change (mobile)
            $("input[name='gia[]']").on('change', function () {
                let gia = [];
                $("input[name='gia[]']:checked").each(function () {
                    gia.push($(this).val());
                });
                let filters = getFilterData();
                filters.gia = gia;
                filters.page = 1;
                saveFilterData(filters);
                loadJobs(1);
            });

            // Handle Choices.js category change
            $(categorySelect).on('change', function () {
                let category = choices.getValue(true);
                let filters = getFilterData();
                filters.category = category;
                filters.page = 1;
                saveFilterData(filters);
                loadJobs(1);
            });

            // Load jobs on page load
            loadJobs();
        });
    </script>
@endsection