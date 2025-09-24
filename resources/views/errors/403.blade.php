@extends('layouts.app')

@section('title', 'Không có quyền truy cập')

@section('content')
<div class="container d-flex flex-column justify-content-center align-items-center text-center" style="min-height:70vh;">
    <h1 class="display-4 text-danger">
        <i class="bi bi-shield-lock"></i> 403
    </h1>
    <h3 class="mb-3">Bạn không có quyền truy cập trang này</h3>
    <p class="text-muted mb-4">
        Vui lòng đăng nhập bằng tài khoản có quyền <strong>Admin</strong> để tiếp tục.
    </p>

    <a href="{{ route('login') }}" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-right"></i> Đăng nhập lại
    </a>
</div>
@endsection
