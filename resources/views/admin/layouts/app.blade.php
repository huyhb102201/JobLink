<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Responsive layout cho zoom */
        html {
            overflow-x: hidden;
            overflow-y: scroll; /* Luôn hiển thị scrollbar để tránh layout shift */
        }
        
        body {
            overflow-x: hidden;
            overflow-y: auto;
            max-width: 100vw;
        }
        
        /* Ngăn layout shift khi mở modal - Áp dụng cho tất cả trang */
        body.modal-open {
            overflow: hidden;
            padding-right: 0 !important; /* Không thêm padding khi mở modal */
        }
        
        /* Đảm bảo navbar và các element cố định không bị shift */
        body.modal-open .navbar,
        body.modal-open .fixed-top,
        body.modal-open .sidebar {
            padding-right: 0 !important;
        }
        
        /* Ẩn icon mặc định của DataTables */
        table.dataTable thead th.sorting:before,
        table.dataTable thead th.sorting:after,
        table.dataTable thead th.sorting_asc:before,
        table.dataTable thead th.sorting_asc:after,
        table.dataTable thead th.sorting_desc:before,
        table.dataTable thead th.sorting_desc:after {
            display: none !important;
        }
        
        /* Viền bảng đậm hơn */
        .table-bordered {
            border: 2px solid #dee2e6 !important;
        }
        
        .table-bordered > :not(caption) > * {
            border-width: 2px !important;
        }
        
        .table-bordered > :not(caption) > * > * {
            border-width: 2px !important;
        }
        
        /* Card border đậm hơn */
        .card {
            border: 2px solid #e3e6f0 !important;
        }
        
        /* DataTables border */
        table.dataTable {
            border-collapse: collapse !important;
        }
        
        table.dataTable thead th,
        table.dataTable thead td {
            border-bottom: 2px solid #dee2e6 !important;
        }
        
        table.dataTable tbody td {
            border-top: 1px solid #dee2e6 !important;
        }
        
        /* Sidebar toggle */
        .admin-layout {
            position: relative;
            max-width: 100vw;
            overflow-x: hidden;
        }
        
        .sidebar {
            transition: all 0.3s ease;
            width: 320px !important;
            min-width: 320px !important;
            max-width: 320px !important;
            overflow-x: hidden;
            overflow-y: auto;
            flex-shrink: 0;
        }
        
        .sidebar.collapsed {
            margin-left: -320px;
        }
        
        .main-content {
            transition: all 0.3s ease;
            flex: 1;
            min-width: 0;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .content-wrapper {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        #sidebarToggle {
            transition: all 0.3s ease;
        }
        
        /* Dropdown menu trong sidebar */
        .sidebar .collapse {
            width: 100%;
        }
        
        .sidebar .nav-link {
            cursor: pointer;
        }
        
        /* Ngăn sidebar expand khi dropdown mở */
        .sidebar .collapse.show {
            display: block;
        }
        
        /* Icon chevron rotation */
        .sidebar .nav-link[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
            transition: transform 0.3s ease;
        }
        
        .sidebar .nav-link[aria-expanded="false"] .fa-chevron-down {
            transform: rotate(0deg);
            transition: transform 0.3s ease;
        }
    </style>

    @stack('styles')
</head>
<body style="background-color:#f8f9fa; font-family:Roboto,sans-serif;">
<div class="admin-layout d-flex">

    {{-- Sidebar --}}
    @include('admin.layouts.partials.sidebar')

    {{-- Main content --}}
    <main class="main-content flex-grow-1 p-3">
        {{-- Header --}}
        @include('admin.layouts.partials.header')

        {{-- Nội dung động --}}
        <div class="content-wrapper">
            @yield('content')
        </div>
    </main>
</div>

{{-- jQuery phải load TRƯỚC tất cả --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Global Loading Functions using SweetAlert2
window.showLoading = function(message = 'Đang xử lý, vui lòng đợi...') {
    Swal.fire({
        title: 'Đang xử lý...',
        html: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
};

window.hideLoading = function() {
    Swal.close();
};

// Setup CSRF token cho tất cả AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Thêm sorting case-insensitive cho DataTables
$.fn.dataTable.ext.type.order['string-case-insensitive-pre'] = function(data) {
    return typeof data === 'string' ? data.toLowerCase() : data;
};

// Set default sorting type là case-insensitive
$.extend($.fn.dataTable.defaults, {
    columnDefs: [{
        type: 'string',
        targets: '_all'
    }]
});

// Sidebar toggle
$(document).ready(function() {
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').toggleClass('collapsed');
    });
    
    // Xử lý dropdown trong sidebar
    $('.sidebar [data-bs-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var target = $(this).attr('href');
        var $target = $(target);
        
        // Toggle collapse
        $target.collapse('toggle');
        
        // Update aria-expanded
        var isExpanded = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', !isExpanded);
    });
    
    // Ngăn sidebar expand khi dropdown đang mở/đóng
    $('.sidebar .collapse').on('show.bs.collapse hide.bs.collapse', function(e) {
        e.stopPropagation();
    });
});
</script>

@stack('scripts')
</body>
</html>