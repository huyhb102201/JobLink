@extends('layouts.app')
@section('title','Quên mật khẩu')
@section('content')
<div class="container" style="max-width:500px; margin-top:60px; margin-bottom:200px; ">
  <h3 class="mb-3">Quên mật khẩu</h3>
  @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
             value="{{ old('email') }}" required autofocus>
      @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <button class="btn btn-primary w-100">Gửi liên kết đặt lại</button>
  </form>
  <div class="mt-3"><a href="{{ route('login') }}" class="small-link">Quay lại đăng nhập</a></div>
</div>
@endsection
