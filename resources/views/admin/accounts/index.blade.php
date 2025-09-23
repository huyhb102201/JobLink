<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background-color: #212529;
            color: #fff;
            flex-shrink: 0;
            padding: 1rem;
        }

        .sidebar .admin-title {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2rem;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #343a40;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }

        .sidebar .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex-grow: 1;
            padding: 2rem;
        }

        .stat-card {
            border: none;
            border-radius: 0.5rem;
        }

        .stat-card .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }

        .table-wrapper {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            font-weight: 500;
            color: #6c757d;
            border-bottom-width: 1px;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
        }

        .user-id {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .action-buttons button,
        .action-buttons a {
            margin: 0 2px;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.3em 0.6em;
            border-radius: 0.25rem;
        }

        .pagination-info {
            color: #6c757d;
        }

        /* Style cho cả 2 modal */
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            color: #212529;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.25);
        }

        .form-control:disabled {
            background-color: #e9ecef;
            color: #6c757d;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #fff;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease-in-out;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        /* Thêm đoạn CSS này để sửa lỗi nút chuyển trang */
        .pagination .page-link {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            line-height: 1.25;
            color: #0d6efd;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .pagination .page-link:hover {
            z-index: 2;
            color: #0a58ca;
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        .pagination .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        /* Điều chỉnh kích thước và vị trí của các mũi tên */
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            margin: 0 2px;
        }

        .pagination .page-item a.page-link i {
            font-size: 0.875rem;
        }

        /* CSS tùy chỉnh cho Datatables để phù hợp với giao diện */
        #accounts-table_wrapper .row:first-child {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        #accounts-table_wrapper .dataTables_length,
        #accounts-table_wrapper .dataTables_filter {
            margin-bottom: 0;
        }

        #accounts-table_wrapper .dataTables_filter input {
            border-radius: 0.25rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }

        #accounts-table_wrapper .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
        }
    </style>
</head>

<body>

    <div class="admin-layout">
        <nav class="sidebar">
            <div class="admin-title">Admin Dashboard</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="fa-solid fa-users"></i> Tài khoản
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fa-solid fa-tags"></i> Loại tài khoản
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fa-solid fa-id-card"></i> Hồ sơ
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link" href="#">
                        <i class="fa-solid fa-gear"></i> Cài đặt
                    </a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="d-flex justify-content-end align-items-center mb-4">
                {{-- Đã xoá nút Thêm tài khoản ở đây và di chuyển xuống dưới --}}
            </header>

            <h1 class="h3 mb-4">Quản lý tài khoản</h1>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div>
                                <h6 class="text-muted fw-normal">Tổng tài khoản</h6>
                                <h3 class="fw-bold">{{ $accounts->total() }}</h3>
                            </div>
                            <i class="fa-solid fa-users stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div>
                                <h6 class="text-muted fw-normal">Kích hoạt</h6>
                                <h3 class="fw-bold">4</h3>
                            </div>
                            <i class="fa-solid fa-circle-check text-success stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div>
                                <h6 class="text-muted fw-normal">Bị khoá</h6>
                                <h3 class="fw-bold">{{ $lockedAccountsCount ?? 0 }}</h3>
                            </div>
                            <i class="fa-solid fa-circle-xmark text-danger stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div>
                                <h6 class="text-muted fw-normal">Chưa xác minh email</h6>
                                <h3 class="fw-bold">3</h3>
                            </div>
                            <i class="fa-solid fa-envelope text-warning stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    {{-- Đã xóa thanh tìm kiếm tùy chỉnh ở đây để DataTables tự động tạo --}}
                    <div class="dropdown">
                        @php
                            $currentFilter = request('account_type_id');
                            $filterLabel = 'Tất cả';
                            if ($currentFilter) {
                                $selectedType = $accountTypes->firstWhere('account_type_id', $currentFilter);
                                if ($selectedType) {
                                    $filterLabel = $selectedType->name;
                                }
                            }
                        @endphp
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-filter me-1"></i> Lọc: {{ $filterLabel }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.accounts.index') }}">Tất cả</a></li>
                            @foreach($accountTypes as $type)
                                <li><a class="dropdown-item"
                                        href="{{ route('admin.accounts.index', ['account_type_id' => $type->account_type_id]) }}">{{ $type->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        {{-- NÚT THÊM TÀI KHOẢN MỚI --}}
                        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal"
                            data-bs-target="#createAccountModal">
                            <i class="fa-solid fa-user-plus me-1"></i> Thêm tài khoản
                        </button>
                        <button class="btn btn-outline-success btn-sm"><i class="fa-solid fa-unlock"></i> Mở
                            khóa</button>
                        <button class="btn btn-outline-warning btn-sm"><i class="fa-solid fa-lock"></i> Tạm
                            khóa</button>
                        <button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i> Xóa</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="accounts-table">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort"><input type="checkbox" class="form-check-input"></th>
                                <th scope="col">Người dùng</th>
                                <th scope="col">Email</th>
                                <th scope="col">Loại TK</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Xác minh</th>
                                <th scope="col">Nhà cung cấp</th>
                                <th scope="col" class="no-sort">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"></td>
                                    <td>
                                        <div class="user-info">
                                            <img src="{{ $account->avatar_url ?? asset('images/man.jpg') }}" alt="avatar"
                                                class="user-avatar">
                                            <div>
                                                <div class="fw-bold">{{ optional($account->profile)->fullname }}</div>
                                                <div class="user-id">#{{ $account->account_id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $account->email }}</td>
                                    <td>
                                        <span
                                            class="fw-bold text-uppercase">{{ optional($account->accountType)->name }}</span>
                                    </td>
                                    <td>
                                        @if($account->status == 1)
                                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill">Hoạt
                                                động</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill">Bị
                                                khoá</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($account->email_verified_at)
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                        @else
                                            <i class="fa-solid fa-circle-xmark text-danger"></i>
                                        @endif
                                    </td>
                                    <td>
                                        @if($account->provider == 'google')
                                            <i class="fa-brands fa-google text-danger" title="Google"></i>
                                        @elseif($account->provider == 'facebook')
                                            <i class="fa-brands fa-facebook text-primary" title="Facebook"></i>
                                        @elseif($account->provider == 'github')
                                            <i class="fa-brands fa-github text-dark" title="Github"></i>
                                        @else
                                            <i class="fa-solid fa-user-circle text-muted" title="Đăng ký thường"></i>
                                        @endif
                                    </td>
                                    <td class="action-buttons">
                                        <a href="#" class="btn btn-sm btn-outline-primary edit-btn" title="Sửa"
                                            data-bs-toggle="modal" data-bs-target="#editAccountModal"
                                            data-account-id="{{ $account->account_id }}"
                                            data-account-type-id="{{ $account->account_type_id }}" 
                                            data-full-name="{{ optional($account->profile)->fullname }}"
                                            data-email="{{ $account->email }}">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>

                                        <a href="#" class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                                class="fa-solid fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Không có tài khoản nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Đã xoá các controls phân trang và thông tin hiển thị ở đây --}}
            </div>
        </main>
    </div>

    {{-- Modal Thêm tài khoản mới --}}
    <div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAccountModalLabel">Thêm tài khoản mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form action="{{ route('admin.accounts.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Tên người dùng</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="createAccountType" class="form-label">Loại tài khoản</label>
                            <select class="form-select" id="createAccountType" name="account_type_id" required>
                                <option value="" disabled selected>Chọn loại tài khoản</option>
                                @foreach($accountTypes as $type)
                                    <option value="{{ $type->account_type_id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Chỉnh sửa tài khoản (đã có từ trước) --}}
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAccountModalLabel">Chỉnh sửa Tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editAccountForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="accountId" class="form-label">ID Tài khoản</label>
                            <input type="text" class="form-control" id="accountId" name="account_id" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Tên người dùng</label>
                            <input type="text" class="form-control" id="fullName" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label for="accountEmail" class="form-label">Email</label>
                            <input type="text" class="form-control" id="accountEmail" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label for="accountType" class="form-label">Loại tài khoản</label>
                            <select class="form-select" id="accountType" name="account_type_id" required>
                                @foreach($accountTypes as $type)
                                    <option value="{{ $type->account_type_id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('editAccountModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const accountId = button.getAttribute('data-account-id');
                const accountTypeId = button.getAttribute('data-account-type-id');
                const fullName = button.getAttribute('data-full-name');
                const email = button.getAttribute('data-email');

                const modalForm = document.getElementById('editAccountForm');
                const accountIdInput = modalForm.querySelector('#accountId');
                const fullNameInput = modalForm.querySelector('#fullName');
                const accountEmailInput = modalForm.querySelector('#accountEmail');
                const accountTypeSelect = modalForm.querySelector('#accountType');

                accountIdInput.value = accountId;
                fullNameInput.value = fullName;
                accountEmailInput.value = email;
                accountTypeSelect.value = accountTypeId;

                modalForm.action = `/admin/accounts/${accountId}`;
            });

            // Khởi tạo DataTables trên bảng
            $('#accounts-table').DataTable({
                "language": {
                    "lengthMenu": "Hiển thị _MENU_ mục",
                    "zeroRecords": "Không tìm thấy dữ liệu",
                    "info": "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                    "infoEmpty": "Hiển thị 0 đến 0 của 0 mục",
                    "infoFiltered": "(được lọc từ _MAX_ mục)",
                    "search": "Tìm kiếm:",
                    "paginate": {
                        "first": "Đầu",
                        "last": "Cuối",
                        "next": "Tiếp",
                        "previous": "Trước"
                    }
                },
                // Ngăn sắp xếp trên cột checkbox và thao tác
                "columnDefs": [
                    { "orderable": false, "targets": [0, 7] }
                ]
            });
        });
    </script>

</body>

</html>