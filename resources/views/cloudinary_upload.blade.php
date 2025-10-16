@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h5>Upload .zip/.rar lên Cloudinary (RAW file)</h5>

  @if(session('ok'))
    <div class="alert alert-success mt-3">
      ✅ {{ session('ok') }} <br>
      <strong>URL:</strong> <a href="{{ session('url') }}" target="_blank">{{ session('url') }}</a><br>
      <strong>Tải về:</strong> <a href="{{ session('download') }}" target="_blank">{{ session('download') }}</a>
    </div>
  @endif

  <form method="POST" action="{{ route('cloudinary.store') }}" enctype="multipart/form-data" class="mt-3">
    @csrf
    <input type="file" name="file" class="form-control" accept=".zip,.rar" required>
    <button class="btn btn-primary mt-3">Upload</button>
  </form>

  @error('file')
    <div class="alert alert-danger mt-3">{{ $message }}</div>
  @enderror
</div>
@endsection
