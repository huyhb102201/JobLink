@extends('layouts.app')

@section('content')
<div class="container py-5">
  <h2>Xác minh email</h2>
  <p>Chúng tôi đã gửi link xác minh đến địa chỉ email của bạn. Vui lòng kiểm tra hộp thư.</p>

  @if (session('status') == 'verification-link-sent')
      <div class="alert alert-success small">
          Link xác minh mới đã được gửi!
      </div>
  @endif

  <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="btn btn-primary">Gửi lại email xác minh</button>
  </form>
</div>
@endsection
