@extends('layouts.app')
@section('title','Tạo job · Bước 3')

@push('styles')
<style>.wizard-wrap{max-width:900px;margin-top:50px;margin-bottom:120px}</style>
@endpush

@section('content')
<div class="container wizard-wrap" style="margin-bottom:200px;">
  @include('client.jobs.wizard._progress', ['n'=>$n,'total'=>$total])

  <form action="{{ route('client.jobs.wizard.store',3) }}" method="POST" class="p-4 border rounded-3 bg-white shadow-sm">
    @csrf
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Hình thức thanh toán *</label>
        <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror">
          <option value="fixed"  @selected(old('payment_type', $d['payment_type'] ?? 'fixed')==='fixed')>Trọn gói (Fixed)</option>
          <option value="hourly" @selected(old('payment_type', $d['payment_type'] ?? '')==='hourly')>Theo giờ (Hourly)</option>
        </select>
        @error('payment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Ngân sách</label>
        <input type="number" step="0.01" name="budget" class="form-control @error('budget') is-invalid @enderror"
               value="{{ old('budget', $d['budget'] ?? '') }}" placeholder="VD: 500">
        @error('budget') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
      <a class="btn btn-link" href="{{ route('client.jobs.wizard.step',2) }}">← Quay lại</a>
      <button class="btn btn-primary">Tiếp tục</button>
    </div>
  </form>
</div>
@endsection
