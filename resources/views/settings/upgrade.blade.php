{{-- resources/views/settings/upgrade.blade.php --}}
@extends('layouts.app')
@section('title', 'Membership plans')

@section('content')
    <div class="container" style="max-width: 1100px;margin-top:50px;margin-bottom:200px;">
        <a href="{{ url()->previous() }}" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-3">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        {{--   <div class="alert alert-info small">currentCode = <strong>{{ $currentTypeCode ?? 'NULL' }}</strong></div>  --}}
      

        <h1 class="fw-semibold mb-4">Membership plans</h1>

        <div class="row g-4">
            @foreach ($plans as $plan)
                @php
                    $isCurrent = $currentTypeId === $plan->account_type_id;
                    $isPro = ($plan->accountType?->code ?? '') === 'F_PRO';
                @endphp

                <div class="col-12 col-lg-4">
                    <div class="plan-card {{ $plan->is_popular ? 'plan-popular' : '' }} h-100">
                        @if ($plan->is_popular)
                            <div class="plan-badge">Popular</div>
                        @endif

                        <div class="plan-body">
                            <h3 class="plan-title">{{ $plan->accountType->name ?? '...' }}</h3>
                            <p class="text-muted mb-3">{{ $plan->tagline }}</p>
                            @php
                                $price = (float) $plan->price;
                                $discount = $plan->discount_percent > 0
                                    ? $price * (1 - $plan->discount_percent / 100)
                                    : $price;
                            @endphp
                            <p class="fw-semibold">
                                @if ($plan->discount_percent > 0)
                                    <span class="text-decoration-line-through text-muted me-2">
                                        {{ number_format($price, 0, ',', '.') }}đ
                                    </span>
                                    <span class="text-danger">
                                        {{ number_format($discount, 0, ',', '.') }}đ / tháng
                                    </span>
                                @else
                                    {{ number_format($price, 0, ',', '.') }}đ / tháng
                                @endif
                            </p>
                            @if ($isCurrent)
                                <button class="btn btn-light rounded-pill w-100 mb-3" disabled>Current plan</button>
                            @elseif ($isPro)
                                <a href="mailto:sales@yourdomain.com" class="btn btn-outline-dark rounded-pill w-100 mb-3">Contact
                                    sales</a>
                            @else
  <button type="button"
          class="btn btn-dark rounded-pill w-100 mb-3 btn-choose-method"
          data-plan-id="{{ $plan->plan_id }}"
          data-plan-name="{{ $plan->accountType->name ?? 'Plan' }}"
          data-price-vnd="{{ (int) ($plan->discount_percent > 0 ? $plan->price * (1 - $plan->discount_percent/100) : $plan->price) }}">
    Select plan
  </button>
@endif

                            <hr>
                            <p class="fw-semibold mt-3 mb-2">Bao gồm:</p>
                            <ul class="plan-ul">
                                @foreach (($plan->features ?? []) as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- CARD 3: Pro --}}
    </div>
    </div>
{{-- Modal chọn phương thức --}}
<div class="modal fade" id="payMethodModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title">Chọn hình thức thanh toán</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 small text-muted" id="chosenPlanText">Plan: —</div>

        <div class="list-group">
          <label class="list-group-item d-flex align-items-center gap-3">
            <input class="form-check-input me-2" type="radio" name="payMethod" value="bank" checked>
            <i class="bi bi-bank2 fs-5"></i>
            <div>
              <div class="fw-semibold">Chuyển khoản ngân hàng</div>
              <div class="text-muted small">Sử dụng luồng cũ tạo payment link/QR</div>
            </div>
          </label>

          <label class="list-group-item d-flex align-items-center gap-3">
            <input class="form-check-input me-2" type="radio" name="payMethod" value="card">
            <i class="bi bi-credit-card fs-5"></i>
            <div>
              <div class="fw-semibold">Thẻ Visa / MasterCard (Stripe)</div>
              <div class="text-muted small">Thanh toán ngay qua Stripe (sandbox)</div>
            </div>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <button id="btnConfirmPay" class="btn btn-primary">
          <span class="spinner-border spinner-border-sm me-2 d-none" id="pmSpin"></span>
          Tiếp tục
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Form ẩn: bank transfer (giữ nguyên route cũ) --}}
<form id="bankForm" class="d-none" method="POST" action="{{ route('create.payment.link') }}">
  @csrf
  <input type="hidden" name="plan_id" id="bankPlanId">
</form>

    {{-- Styles --}}
    <style>
        .plan-card {
            border: 1px solid #eaeaea;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.03);
            transition: box-shadow .2s, transform .2s, border-color .2s;
        }

        .plan-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .plan-popular {
            position: relative;
            border: 1px solid #e7f8c7;
            box-shadow: 0 0 0 3px rgba(210, 255, 140, 0.25) inset;
        }

        .plan-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 12px;
            padding: 4px 8px;
            background: #111;
            color: #fff;
            border-radius: 999px;
        }

        .plan-body {
            padding: 24px;
        }

        .plan-title {
            font-weight: 700;
        }

        .plan-ul {
            padding-left: 1rem;
            margin: 0;
            list-style: none;
        }

        .plan-ul li {
            position: relative;
            padding-left: 1.4rem;
            margin: .5rem 0;
            color: #333;
        }

        .plan-ul li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: #111;
            font-weight: 700;
        }
    </style>
    <script>
(function () {
  let currentPlan = { id: null, name: '', priceVnd: 0 };

  // mở modal khi nhấn "Select plan"
  document.querySelectorAll('.btn-choose-method').forEach(btn => {
    btn.addEventListener('click', () => {
      currentPlan.id = btn.dataset.planId;
      currentPlan.name = btn.dataset.planName || 'Membership';
      currentPlan.priceVnd = parseInt(btn.dataset.priceVnd || '0', 10);

      document.getElementById('chosenPlanText').textContent =
        `Plan: ${currentPlan.name} • ${new Intl.NumberFormat('vi-VN').format(currentPlan.priceVnd)}đ/tháng`;

      const modal = new bootstrap.Modal(document.getElementById('payMethodModal'));
      modal.show();
    });
  });

  // xác nhận phương thức
  document.getElementById('btnConfirmPay').addEventListener('click', async () => {
    const spin = document.getElementById('pmSpin');
    const method = document.querySelector('input[name="payMethod"]:checked')?.value;
    if (!currentPlan.id) return;

    spin.classList.remove('d-none');

    try {
      if (method === 'bank') {
        // submit form cũ (payment link/QR)
        document.getElementById('bankPlanId').value = currentPlan.id;
        document.getElementById('bankForm').submit();
      } else {
        // Stripe Checkout: gọi endpoint test đã tạo
       // THAY THẾ toàn bộ payload & headers
const fd = new FormData();
fd.append('plan_id', String(currentPlan.id)); // << bắt buộc

const res = await fetch('{{ route('stripe.checkout') }}', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': '{{ csrf_token() }}',
    'Accept': 'application/json'             // << để 422/500 trả JSON, không redirect HTML
  },
  body: fd
});

const data = await res.json();               // giờ server sẽ trả JSON
if (data.url) {
  window.location.href = data.url;
} else {
  alert(data.error || 'Không tạo được phiên Stripe');
}

      }
    } catch (err) {
      console.error(err);
      alert(err?.message || 'Lỗi không xác định');
    } finally {
      spin.classList.add('d-none');
    }
  });
})();
</script>

@endsection