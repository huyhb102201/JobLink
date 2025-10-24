@extends('layouts.app')
@section('title', 'Đăng Job')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* nền dịu + pattern */
        .choose-hero {
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(13, 110, 253, .08), transparent 60%),
                radial-gradient(900px 500px at 90% 0%, rgba(32, 201, 151, .08), transparent 60%);
        }

        .section-title {
            letter-spacing: .2px;
        }

        /* card hiện đại có viền gradient */
        .option-card {
            position: relative;
            border-radius: 18px;
            background: rgba(255, 255, 255, .86);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(16, 24, 40, .06);
            transition: transform .18s ease, box-shadow .18s ease;
            overflow: hidden;
        }

        .option-card::before {
            content: "";
            position: absolute;
            inset: 0;
            padding: 1px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(13, 110, 253, .6), rgba(32, 201, 151, .6));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
            opacity: .35;
        }

        .option-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 36px rgba(16, 24, 40, .12);
        }

        .option-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f1f5ff;
            color: #0d6efd;
            font-size: 20px;
        }

        .option-icon.success {
            background: #ecfbf5;
            color: #20c997;
        }

        .feature-list {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .feature-list li {
            display: flex;
            gap: .5rem;
            align-items: flex-start;
            margin-bottom: .35rem;
            color: #475467;
        }

        .feature-list i {
            color: #20c997;
            margin-top: .15rem;
        }

        .btn-soft {
            background: #f6f8ff;
            border: 1px solid #e7ecff;
            color: #0d6efd;
        }

        .btn-soft:hover {
            background: #eaf0ff;
            border-color: #dbe7ff;
            color: #0b5ed7;
        }

        .divider-or {
            position: relative;
            text-align: center;
            color: #98a2b3;
            margin: 12px 0 -8px;
        }

        .divider-or::before,
        .divider-or::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #e9ecef;
        }

        .divider-or::before {
            left: 0
        }

        .divider-or::after {
            right: 0
        }
    </style>
@endpush

@section('content')
    <div class="choose-hero py-4" style="margin-bottom:200px;">
        <div class="container" style="max-width: 1080px;">
            <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                <i class="bi bi-house"></i><a href="{{ url('/') }}" class="text-muted text-decoration-none">Trang chủ</a>
                <span>›</span><span>Đăng job</span>
            </div>

            <h1 class="section-title h3 fw-semibold mb-1">Bạn muốn tạo job như thế nào?</h1>
            <p class="text-secondary mb-4">Chọn cách phù hợp: tự điền biểu mẫu chi tiết hoặc để AI tạo bản nháp & điền sẵn
                giúp bạn.</p>

            <div class="row g-4">
                {{-- Tự điền biểu mẫu --}}
                <div class="col-md-6">
                    <div class="option-card h-100">
                        <div class="p-4 p-lg-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="option-icon"><i class="bi bi-pencil-square"></i></span>
                                <div class="ms-3">
                                    <h5 class="mb-1">Tự điền biểu mẫu</h5>
                                    <div class="text-muted small">Bạn tự nhập tiêu đề, mô tả, ngân sách, deadline…</div>
                                </div>
                            </div>

                            <ul class="feature-list mb-4">
                                <li><i class="bi bi-check2-circle"></i> Kiểm soát từng trường thông tin</li>
                                <li><i class="bi bi-check2-circle"></i> Phù hợp khi đã có yêu cầu rõ ràng</li>
                            </ul>

                            <a class="btn btn-outline-primary w-100" href="{{ route('client.jobs.wizard.step', 1) }}">
                                <i class="bi bi-pencil"></i> Bắt đầu 
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Nhờ AI viết hộ --}}
               {{-- Nhờ AI viết hộ --}}
<div class="col-md-6">
  <div class="option-card h-100">
    <div class="p-4 p-lg-4">
      <div class="d-flex align-items-center mb-3">
        <span class="option-icon success"><i class="bi bi-stars"></i></span>
        <div class="ms-3">
          <h5 class="mb-1">Nhờ AI viết hộ</h5>
          <div class="text-muted small">Dán mô tả thô, AI tự tạo bản nháp & điền sẵn form.</div>
        </div>
      </div>

      <ul class="feature-list mb-4">
        <li><i class="bi bi-check2-circle"></i> Nhanh, gợi ý cấu trúc chuẩn</li>
        <li><i class="bi bi-check2-circle"></i> Vẫn chỉnh sửa thoải mái trước khi đăng</li>
      </ul>

      @php
        $user = Auth::user();
        $isClient = $user && optional($user->type)->code === 'CLIENT';
      @endphp

      @if($isClient)
        {{-- Nếu là Client: đổi nút sang Nâng cấp --}}
        <a href="{{ url('/settings/upgrade') }}" class="btn btn-warning w-100 fw-semibold py-2 text-dark shadow-sm">
          <i class="bi bi-rocket-takeoff-fill me-1"></i> Nâng cấp ngay
        </a>
        <div class="small text-muted mt-2">Tính năng AI chỉ dành cho gói cao hơn.</div>
      @else
        {{-- Nếu không phải Client: nút dùng AI --}}
        <a class="btn btn-success w-100 py-2" href="{{ route('client.jobs.ai_form') }}">
          <i class="bi bi-magic me-1"></i> Dùng AI tạo form
        </a>
        <div class="small text-muted mt-2">
          Mẹo: nhập vài ràng buộc (tech stack, deadline, ngân sách) để AI điền chính xác hơn.
        </div>
      @endif
    </div>
  </div>
</div>

            </div>

        </div>
    </div>
    <style>
        /* Hiệu ứng overlay khoá tính năng */
        .locked-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(6px);
            border-radius: 18px;
            z-index: 5;
            transition: all .25s ease;
        }

        .locked-overlay:hover {
            background: rgba(255, 255, 255, 0.92);
        }

        .locked-overlay i {
            animation: float 1.8s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }
    </style>
@endsection