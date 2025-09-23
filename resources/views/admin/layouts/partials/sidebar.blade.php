<nav class="sidebar bg-dark text-white p-3" style="width:260px; min-height:100vh;">
    <div class="admin-title h4 text-center fw-bold mb-4">Admin Dashboard</div>
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a class="nav-link text-white {{ request()->is('admin/accounts*') ? 'active' : '' }}"
               href="{{ route('admin.accounts.index') }}">
                <i class="fa-solid fa-users me-2"></i> Tài khoản
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="#">
                <i class="fa-solid fa-tags me-2"></i> Loại tài khoản
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="#">
                <i class="fa-solid fa-id-card me-2"></i> Hồ sơ
            </a>
        </li>
        <li class="nav-item mt-auto">
            <a class="nav-link text-white" href="#">
                <i class="fa-solid fa-gear me-2"></i> Cài đặt
            </a>
        </li>
    </ul>
</nav>
