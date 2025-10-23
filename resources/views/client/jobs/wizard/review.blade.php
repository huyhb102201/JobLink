@extends('layouts.app')
@section('title', 'Tạo job · Xem lại')

@section('content')
@php
  $d = isset($d) && is_array($d) ? $d : [];
  $title        = data_get($d,'title','');
  $categoryName = data_get($d,'category_name','Không có');
  $paymentType  = data_get($d,'payment_type','fixed');
  $budgetPer    = (float) data_get($d,'budget',0);         // mỗi freelancer
  $qty          = (int)   data_get($d,'quantity',1);
  $totalBudget  = (float) data_get($d,'total_budget', $budgetPer*$qty);
  $deadline     = data_get($d,'deadline','—');
  $desc         = (string) data_get($d,'description','');
@endphp

<div class="container" style="max-width:980px;margin-top:50px;margin-bottom:120px;">
  @include('client.jobs.wizard._progress', ['n' => $n, 'total' => $total])

  <div class="row g-4 mt-3">
    <div class="col-lg-7">
      <div class="p-4 border rounded-3 bg-white shadow-sm">
        <h4 class="mb-3">{{ $title }}</h4>

        <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
          <span><i class="bi bi-tag"></i> {{ $categoryName }}</span>

          <span>
            <i class="bi bi-wallet2"></i>
            {{ $paymentType === 'hourly' ? 'Theo giờ' : 'Trọn gói' }}
            · ₫{{ number_format($totalBudget, 0) }}
          </span>

          <span><i class="bi bi-people"></i> Số lượng: {{ $qty }}</span>
        </div>

        <hr>

        <article class="mt-3">{!! nl2br(e($desc)) !!}</article>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="p-4 border rounded-3 bg-white shadow-sm">
        <div class="mb-2"><strong>Deadline:</strong> {{ $deadline }}</div>

        <div class="mt-3 small">
          <div class="d-flex justify-content-between">
            <span>Hình thức:</span>
            <strong>{{ $paymentType === 'hourly' ? 'Theo giờ' : 'Trọn gói' }}</strong>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span>Ngân sách mỗi freelancer:</span>
            <strong>₫{{ number_format($budgetPer, 0) }}</strong>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span>Số lượng tuyển:</span>
            <strong>{{ $qty }}</strong>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span>Tổng ngân sách:</span>
            <strong>₫{{ number_format($totalBudget, 0) }}</strong>
          </div>
        </div>

        <div class="d-grid gap-2 mt-4">
          <a href="{{ route('client.jobs.wizard.step', 1) }}" class="btn btn-outline-secondary">Sửa lại</a>
          <form method="POST" action="{{ route('client.jobs.wizard.submit') }}">
            @csrf
            <button class="btn btn-success"><i class="bi bi-send-fill me-1"></i> Đăng job</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
