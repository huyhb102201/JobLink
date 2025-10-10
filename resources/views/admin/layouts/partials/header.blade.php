@php $user = auth()->user(); @endphp

<header class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
    <button id="sidebarToggle" class="btn btn-link text-dark p-0" style="font-size: 1.5rem;">
        <i class="fas fa-bars"></i>
    </button>
    <div class="d-flex align-items-center">
        <div class="text-end me-3">
            <div class="fw-bold">{{ $user->name ?? optional($user->profile)->fullname }}</div>
            <small class="text-muted">{{ $user->email }}</small>
        </div>
        <img src="{{ $user->avatar_url ?? asset('images/man.jpg') }}"
             alt="Avatar"
             class="rounded-circle"
             style="width:40px;height:40px;object-fit:cover;">
        <form action="{{ route('logout') }}" method="POST" class="ms-3">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </button>
        </form>
    </div>
</header>
