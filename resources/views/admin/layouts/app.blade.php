<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
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
        .sidebar {
            transition: margin-left 0.3s ease;
        }
        
        .sidebar.collapsed {
            margin-left: -250px;
        }
        
        .main-content {
            transition: margin-left 0.3s ease;
        }
        
        #sidebarToggle {
            transition: all 0.3s ease;
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
});
</script>

@stack('scripts')
</body>
</html>