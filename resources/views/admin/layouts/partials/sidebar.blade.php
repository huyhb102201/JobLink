<nav class="sidebar bg-dark text-white p-3" style="width:320px; min-width:320px; max-width:320px; min-height:100vh; position: relative;">
    <div class="admin-title h4 text-center fw-bold mb-4" style="white-space: nowrap;">Admin Dashboard</div>
    <ul class="nav flex-column" style="width: 100%;">
        <li class="nav-item mb-2">
            <a class="nav-link text-white {{ request()->is('admin') && !request()->is('admin/*') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-chart-line me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white {{ request()->is('admin/accounts*') ? 'active' : '' }}"
               href="{{ route('admin.accounts.index') }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-users me-2"></i> Tài khoản
            </a>
        </li>
        
        <!-- Dropdown Xét duyệt -->
        <li class="nav-item mb-2">
            <a class="nav-link text-white d-flex justify-content-between align-items-center {{ request()->is('admin/jobs*') || request()->is('admin/verifications*') ? 'active' : '' }}"
               data-bs-toggle="collapse" 
               href="#verificationMenu" 
               role="button" 
               aria-expanded="{{ request()->is('admin/jobs*') || request()->is('admin/verifications*') ? 'true' : 'false' }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <span><i class="fas fa-bars me-2"></i> Xét duyệt</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="collapse {{ request()->is('admin/jobs*') || request()->is('admin/verifications*') ? 'show' : '' }}" id="verificationMenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link text-white {{ request()->is('admin/jobs*') ? 'active' : '' }}"
                           href="{{ route('admin.jobs.pending') }}"
                           style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="fa-solid fa-file-circle-check me-2"></i> Duyệt bài đăng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white {{ request()->is('admin/verifications*') ? 'active' : '' }}"
                           href="{{ route('admin.verifications.index') }}"
                           style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="fa-solid fa-building-flag me-2"></i> Xét duyệt doanh nghiệp
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="#"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-tags me-2"></i> Loại tài khoản
            </a>
        </li>

        <!-- Quản lý danh mục -->
        <li class="nav-item mb-2">
            <a class="nav-link text-white {{ request()->is('admin/categories*') ? 'active' : '' }}"
               href="{{ route('admin.categories.index') }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-folder-open me-2"></i> Danh mục
            </a>
        </li>

        <!-- Quản lý đánh giá -->
        <li class="nav-item mb-2">
            <a class="nav-link text-white {{ request()->is('admin/reviews*') ? 'active' : '' }}"
               href="{{ route('admin.reviews.index') }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-star me-2"></i> Đánh giá
            </a>
        </li>
        
        <!-- Dropdown Thanh toán -->
        <li class="nav-item mb-2">
            <a class="nav-link text-white d-flex justify-content-between align-items-center {{ request()->is('admin/payments*') || request()->is('admin/job-payments*') ? 'active' : '' }}"
               data-bs-toggle="collapse" 
               href="#paymentMenu" 
               role="button" 
               aria-expanded="{{ request()->is('admin/payments*') || request()->is('admin/job-payments*') ? 'true' : 'false' }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <span><i class="fas fa-bars me-2"></i> Thanh toán</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="collapse {{ request()->is('admin/payments*') || request()->is('admin/job-payments*') ? 'show' : '' }}" id="paymentMenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link text-white {{ request()->is('admin/payments') || request()->is('admin/payments/index') ? 'active' : '' }}"
                           href="{{ route('admin.payments.index') }}"
                           style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="fa-solid fa-wallet me-2"></i> Thanh toán tài khoản
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white {{ request()->is('admin/job-payments*') ? 'active' : '' }}"
                           href="{{ route('admin.job-payments.index') }}"
                           style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="fa-solid fa-money-bill-wave me-2"></i> Thanh toán việc làm
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item mb-2">
            <a class="nav-link text-white {{ request()->is('admin/logs*') ? 'active' : '' }}"
               href="{{ route('admin.logs.index') }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-history me-2"></i> Lịch sử hoạt động
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="#"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-id-card me-2"></i> Hồ sơ
            </a>
        </li>
        <li class="nav-item mt-auto">
            <a class="nav-link text-white {{ request()->is('admin/settings*') ? 'active' : '' }}"
               href="{{ route('admin.settings.index') }}"
               style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fa-solid fa-gear me-2"></i> Cài đặt
            </a>
        </li>
    </ul>
</nav>
