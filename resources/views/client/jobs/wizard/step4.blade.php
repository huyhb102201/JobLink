@extends('layouts.app')
@section('title','Tạo job · Bước 4')

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
<div class="container" style="max-width:780px;margin-top:50px;margin-bottom:200px;">
  @include('client.jobs.wizard._progress', ['n'=>$n,'total'=>$total])

  <form action="{{ route('client.jobs.wizard.store',4) }}" method="POST" class="p-4 border rounded-3">
    @csrf
    <label class="form-label fw-semibold">Deadline (tuỳ chọn)</label>
    <input type="date" name="deadline" class="form-control @error('deadline') is-invalid @enderror"
           value="{{ old('deadline', isset($d['deadline']) ? \Illuminate\Support\Str::of($d['deadline'])->substr(0,10) : '') }}">
    @error('deadline') <div class="invalid-feedback">{{ $message }}</div> @enderror

    <div class="d-flex justify-content-between mt-4">
      <a class="btn btn-link" href="{{ route('client.jobs.wizard.step',3) }}">← Quay lại</a>
      <button class="btn btn-primary">Tiếp tục</button>
    </div>
  </form>
</div>
</main>
@endsection
