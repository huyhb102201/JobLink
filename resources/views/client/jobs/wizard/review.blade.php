@extends('layouts.app')
@section('title', 'Tạo job · Xem lại')

@section('content')
    <div class="container" style="max-width:980px;margin-top:50px;margin-bottom:120px;">
        @include('client.jobs.wizard._progress', ['n' => $n, 'total' => $total])

        <div class="row g-4 mt-3">
            <div class="col-lg-7">
                <div class="p-4 border rounded-3 bg-white shadow-sm">
                    <h4 class="mb-3">{{ $d['title'] }}</h4>
                    <div class="text-muted small mb-2">
                        <span class="me-3"><i class="bi bi-tag"></i> {{ $d['category_name'] ?? 'Không có' }}</span>
                        {{-- Nếu muốn xem nhanh nội dung chi tiết:
                        @if(!empty($d['content']))
                        <hr>
                        <div class="mt-2">{!! $d['content'] !!}</div>
                        @endif
                        --}}

                        <span><i class="bi bi-wallet2"></i>
                            {{ ($d['payment_type'] ?? 'fixed') === 'hourly' ? 'Theo giờ' : 'Trọn gói' }}
                            @if(isset($d['budget'])) · ${{ $d['budget'] }} @endif
                        </span>
                    </div>
                    <hr>
                    <article class="mt-3">{!! nl2br(e($d['description'])) !!}</article>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="p-4 border rounded-3 bg-white shadow-sm">
                    <div class="mb-2"><strong>Deadline:</strong> {{ $d['deadline'] ?? '—' }}</div>
                    <div class="d-grid gap-2 mt-3">
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