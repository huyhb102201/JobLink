@extends('layouts.app')

@section('content')
<div class="container">
  <h1 class="mb-4">Chào mừng đến với JobLink</h1>

  <p>Đây là sàn kết nối Freelancer và Nhà tuyển dụng.</p>

  @guest
    
  @endguest
</div>
@endsection
