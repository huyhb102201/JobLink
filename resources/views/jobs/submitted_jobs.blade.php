@extends('layouts.app')
@section('title', 'Công việc đã ứng tuyển')

@section('content')
    <style>
        .job-card {
            transition: box-shadow .2s, transform .05s;
        }

        .job-card:hover {
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
        }

        #jobs-group .d-flex {
            display: flex;
        }

        #jobs-group .flex-fill {
            display: flex;
            flex-direction: column;
        }

        .job-title {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            white-space: normal;
        }

        .job-description {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            line-height: 1.5em;
            min-height: 3em;
            max-height: 3em;
            max-width: 100%;
        }

        @media (max-width: 768px) {
            .job-title {
                -webkit-line-clamp: 1;
            }
        }

        @media (min-width: 769px) {
            .job-title {
                -webkit-line-clamp: 2;
            }
        }
    </style>
    <main class="main">
        <!-- Page Title -->
        <div class="page-title py-4 bg-light">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Danh sách công việc đã ứng tuyển</h1>
                <nav class="breadcrumbs">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item active">Công việc đã ứng tuyển</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Job List Section -->
        <section class="pt-4">
            <div class="container" style="max-width: 1100px;">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($applies->isEmpty())
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <h5 class="mb-1">Bạn chưa ứng tuyển công việc nào</h5>
                            <p class="text-muted">Hãy tìm công việc phù hợp và ứng tuyển ngay.</p>
                            <a href="{{ route('jobs.index') }}" class="btn btn-primary">
                                <i class="bi bi-search"></i> Tìm việc ngay
                            </a>
                        </div>
                    </div>
                @else
                    <div id="jobs-group" class="vstack gap-3">
                        @include('jobs.partials.jobs_apply_list', ['applies' => $applies])
                    </div>

                    <!-- Phân trang -->
                    <div class="mt-4" id="pagination-wrapper">
                        {{ $applies->links('components.pagination') }}
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.bootstrap) return;

            const group = document.getElementById('jobs-group');

            document.querySelectorAll('[data-collapse]').forEach(function (btn) {
                const targetSel = btn.getAttribute('data-bs-target');
                const target = document.querySelector(targetSel);
                if (!target) return;

                const inst = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
                const icon = btn.querySelector('.icon');
                const label = btn.querySelector('.label');
                const openText = btn.getAttribute('data-open-text') || 'Ẩn chi tiết';
                const closeText = btn.getAttribute('data-close-text') || 'Chi tiết';

                // Xử lý click mở/đóng
                btn.addEventListener('click', function () {
                    inst.toggle();
                });

                // Đóng các khối khác khi mở một khối
                target.addEventListener('show.bs.collapse', function () {
                    if (!group) return;
                    group.querySelectorAll('.collapse.show').forEach(function (el) {
                        if (el !== target) {
                            bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
                        }
                    });
                });

                // Khi mở: cập nhật giao diện + load task
                // Xử lý sự kiện khi collapse được mở
                $('.collapse').on('shown.bs.collapse', function () {
                    const target = this;
                    const targetSel = '#' + target.id;
                    const btn = document.querySelector(`[data-bs-target="${targetSel}"]`);
                    const icon = btn.querySelector('.icon');
                    const label = btn.querySelector('.label');
                    const openText = btn.dataset.openText || 'Ẩn chi tiết';

                    // Cập nhật giao diện nút
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-secondary');
                    if (icon) icon.className = 'bi bi-chevron-up me-1 icon';
                    if (label) label.textContent = openText;

                    // Gọi AJAX để tải user-tasks
                    const jobId = targetSel.replace('#details-', '');
                    if (!target.classList.contains('loaded')) {
                        target.classList.add('loaded');
                        $(target).html('<div class="text-center py-2 text-muted small">Đang tải task...</div>');

                        $.ajax({
                            url: `/jobs/${jobId}/user-tasks`,
                            type: 'GET',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            success: function (html) {
                                // Chèn nội dung HTML
                                $(target).html(html);

                                // Tìm và thực thi các thẻ <script> trong nội dung tải về
                                const scripts = $(target).find('script');
                                scripts.each(function () {
                                    const scriptContent = $(this).html();
                                    try {
                                        // Thực thi script bằng eval (cẩn thận với nội dung script)
                                        eval(scriptContent);
                                    } catch (err) {
                                        console.error('Lỗi khi thực thi script:', err);
                                        $(target).html('<div class="text-danger">Không thể tải task. Vui lòng thử lại.</div>');
                                    }
                                });
                            },
                            error: function (xhr) {
                                console.error('Lỗi khi tải user-tasks:', xhr);
                                $(target).html('<div class="text-danger">Không thể tải task. Vui lòng thử lại.</div>');
                            }
                        });
                    }
                });


                // Khi đóng
                target.addEventListener('hidden.bs.collapse', function () {
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-outline-secondary');
                    if (icon) icon.className = 'bi bi-info-circle me-1 icon';
                    if (label) label.textContent = closeText;
                });

                // Trạng thái ban đầu
                if (target.classList.contains('show')) {
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-secondary');
                    if (icon) icon.className = 'bi bi-chevron-up me-1 icon';
                    if (label) label.textContent = openText;
                } else {
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-outline-secondary');
                    if (icon) icon.className = 'bi bi-info-circle me-1 icon';
                    if (label) label.textContent = closeText;
                }
            });


            // ==== Giữ nguyên phần load applies qua AJAX ====
            function loadapplies(page = 1) {
                let filters = $("#applies-filter").serialize();
                $.ajax({
                    url: "/submitted_jobs?page=" + page,
                    type: "GET",
                    dataType: "json",
                    data: filters,
                    beforeSend: function () { $("#jobs-group").css("opacity", "0.5"); },
                    success: function (res) {
                        $("#jobs-group").html(res.applies);
                        $("#pagination-wrapper").html(res.pagination);
                        $("#jobs-group").css("opacity", "1");
                    },
                    error: function () {
                        alert("Không load được dữ liệu. Vui lòng thử lại.");
                        $("#jobs-group").css("opacity", "1");
                    }
                });
            }

            $(document).on('click', '.ajax-page-link', function (e) {
                e.preventDefault();
                let page = $(this).data('page');
                loadapplies(page);
            });

            $('#applies-filter input[name="keyword"]').on('keyup', function () {
                loadapplies(1);
            });
            $('#applies-filter select').on('change', function () {
                loadapplies(1);
            });
        });
    </script>
@endpush