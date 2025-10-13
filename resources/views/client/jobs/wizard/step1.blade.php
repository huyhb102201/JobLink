@extends('layouts.app')
@section('title','Tạo job · Bước 1')

@push('styles')
<style>
  .wizard-wrap{max-width:1100px;margin-top:50px;margin-bottom:120px}
  .left h1{font-weight:800; letter-spacing:.2px}
  .help-card{background:#fbfcff;border:1px solid #eef2ff;border-radius:14px}
</style>
@endpush

@section('content')
 <main class="main">
        <!-- Page Title -->
        <div class="page-title">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Đăng công việc</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li class="current">Đăng công việc</li>
                    </ol>
                </nav>
            </div>
        </div>
<div class="container wizard-wrap" style="margin-bottom:200px;">
  @include('client.jobs.wizard._progress', ['n'=>$n,'total'=>$total])

  <div class="row mt-4 g-4">
    <div class="col-lg-6 left">
      <h1>Hãy bắt đầu với một <span class="text-primary">tiêu đề thật tốt</span>.</h1>
      <p class="text-secondary mt-2">Tiêu đề giúp bài đăng nổi bật và thu hút đúng freelancer. Ngắn gọn nhưng rõ ràng.</p>
    </div>

    <div class="col-lg-6">
      <form action="{{ route('client.jobs.wizard.store',1) }}" method="POST" class="help-card p-4">
        @csrf
        <label class="form-label fw-semibold">Tiêu đề cho job của bạn</label>
        <input name="title" class="form-control form-control-lg @error('title') is-invalid @enderror"
               value="{{ old('title', $d['title'] ?? '') }}" placeholder="">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror

        <div class="mt-4 d-flex justify-content-end gap-2">
          <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Hủy</a>
          <button class="btn btn-primary">Tiếp tục</button>
        </div>
      </form>

      <div class="mt-3 small">
        <div class="fw-semibold mb-1">Tiêu đề mẫu</div>
        <ul class="mb-0">
          <li>Xây website WordPress responsive có booking & thanh toán</li>
          <li>Thiết kế banner quảng cáo cho chiến dịch T10</li>
          <li>Chuyên gia Facebook Ads tối ưu CPA cho sản phẩm digital</li>
        </ul>
      </div>
    </div>
  </div>
</div>
</main>
@endsection
