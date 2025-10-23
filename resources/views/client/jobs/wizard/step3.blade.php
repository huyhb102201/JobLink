@extends('layouts.app')
@section('title','Tạo job · Bước 3')

@push('styles')
<style>.wizard-wrap{max-width:900px;margin-top:50px;margin-bottom:120px}</style>
@endpush

@section('content')
@php
  // an toàn khi $d chưa tồn tại
  $d = isset($d) && is_array($d) ? $d : [];
  $oldPayment = old('payment_type', data_get($d,'payment_type','fixed'));
  $oldTotal   = old('total_budget', data_get($d,'total_budget',''));
  $oldPer     = old('budget', data_get($d,'budget',''));
  $oldQty     = (int) old('quantity', (int) data_get($d,'quantity',1));
@endphp

<div class="container wizard-wrap" style="margin-bottom:200px;">
  @include('client.jobs.wizard._progress', ['n'=>$n,'total'=>$total])

  <form action="{{ route('client.jobs.wizard.store',3) }}" method="POST" class="p-4 border rounded-3 bg-white shadow-sm">
    @csrf

    {{-- Hình thức thanh toán --}}
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Hình thức thanh toán *</label>
        <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror">
          <option value="fixed"  @selected($oldPayment==='fixed')>Trọn gói (Fixed)</option>
          <option value="hourly" @selected($oldPayment==='hourly')>Theo giờ (Hourly)</option>
        </select>
        @error('payment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Tổng ngân sách --}}
      <div class="col-md-6">
        <label class="form-label fw-semibold">Tổng ngân sách (VND)</label>
        <input type="number" step="1" min="0"
               name="total_budget" id="f_total_budget"
               class="form-control @error('total_budget') is-invalid @enderror"
               value="{{ $oldTotal }}"
               placeholder="VD: 5000000">
        @error('total_budget') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- Ngân sách mỗi người & Số lượng --}}
    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Ngân sách mỗi freelancer (VND)</label>
        <input type="number" step="1" min="0"
               name="budget" id="f_budget_per"
               class="form-control @error('budget') is-invalid @enderror"
               value="{{ $oldPer }}"
               placeholder="Tự tính theo Tổng / Số lượng"
               readonly>
        @error('budget') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Ô này sẽ tự cập nhật = Tổng ngân sách ÷ Số lượng.</div>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Số lượng freelancer *</label>
        <input type="number" step="1" min="1"
               name="quantity" id="f_quantity"
               class="form-control @error('quantity') is-invalid @enderror"
               value="{{ $oldQty }}">
        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
      <a class="btn btn-link" href="{{ route('client.jobs.wizard.step',2) }}">← Quay lại</a>
      <button class="btn btn-primary">Tiếp tục</button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const totalEl = document.getElementById('f_total_budget');
  const perEl   = document.getElementById('f_budget_per');
  const qtyEl   = document.getElementById('f_quantity');

  let lock = false;
  const n = v => isFinite(+v) ? +v : 0;
  const clampQty = v => Math.max(1, Math.floor(n(v)));

  function recalcPer(){
    if (lock) return; lock = true;
    const total = n(totalEl.value);
    const qty   = clampQty(qtyEl.value);
    qtyEl.value = qty;
    const per   = qty > 0 ? total / qty : 0;
    perEl.value = per > 0 ? Math.round(per) : '';
    lock = false;
  }

  function recalcTotal(){
    if (lock) return; lock = true;
    const per = n(perEl.value);
    const qty = clampQty(qtyEl.value);
    qtyEl.value = qty;
    const total = per * qty;
    totalEl.value = total > 0 ? Math.round(total) : '';
    lock = false;
  }

  totalEl?.addEventListener('input', recalcPer);
  qtyEl?.addEventListener('input', () => { recalcPer(); recalcTotal(); });
  // perEl là readOnly nên người dùng không gõ, nhưng vẫn để phòng trường hợp bạn bỏ readonly
  perEl?.addEventListener('input', recalcTotal);

  // đồng bộ ban đầu
  if (n(totalEl?.value) && clampQty(qtyEl?.value)) recalcPer();
  else if (n(perEl?.value) && clampQty(qtyEl?.value)) recalcTotal();
})();
</script>
@endpush
