{{-- resources/views/settings/upgrade.blade.php --}}
@extends('layouts.app')
@section('title', 'Membership plans')

@section('content')
    <div class="container" style="max-width: 1100px;margin-top:50px;margin-bottom:200px;">
        <a href="{{ url()->previous() }}" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-3">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        {{-- DEBUG: xoá khi xong --}}
        <div class="alert alert-info small">currentCode = <strong>{{ $currentTypeCode ?? 'NULL' }}</strong></div>

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
                                <form method="POST" action="{{ route('create.payment.link') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->plan_id }}">
                                    <button class="btn btn-dark rounded-pill w-100 mb-3">Select plan</button>
                                </form>

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
@endsection