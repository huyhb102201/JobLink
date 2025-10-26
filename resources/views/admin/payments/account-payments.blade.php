@extends('admin.layouts.app')

@section('title', 'Lịch sử thanh toán')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Lịch sử thanh toán</h1>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Quay lại
    </a>
</div>

@if(isset($error))
    <div class="alert alert-danger">{{ $error }}</div>
@else
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Thông tin tài khoản</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-2"><strong>Tên tài khoản:</strong> {{ $account->profile->fullname ?? $account->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-2"><strong>Email:</strong> {{ $account->email }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-2"><strong>Tổng thanh toán (thành công):</strong> <span class="badge bg-success">{{ number_format($totalAmount) }} đ</span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách giao dịch</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Gói</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td><code>{{ $payment->order_code ?? 'N/A' }}</code></td>
                            <td>{{ $payment->plan->name ?? $payment->plan->tagline ?? 'N/A' }}</td>
                            <td><span class="badge bg-info">{{ number_format($payment->amount ?? 0) }} đ</span></td>
                            <td>
                                @if($payment->status === 'success')
                                    <span class="badge bg-success">Thành công</span>
                                @elseif($payment->status === 'failed')
                                    <span class="badge bg-danger">Thất bại</span>
                                @else
                                    <span class="badge bg-warning">Đang chờ</span>
                                @endif
                            </td>
                            <td>{{ $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Không có giao dịch nào</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($payments->hasPages())
            <div class="d-flex justify-content-center mt-3">
                <nav>
                    <ul class="pagination mb-0">
                        {{-- Previous Page Link --}}
                        @if ($payments->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link">Trước</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $payments->previousPageUrl() }}" rel="prev">Trước</a>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($payments->getUrlRange(1, $payments->lastPage()) as $page => $url)
                            @if ($page == $payments->currentPage())
                                <li class="page-item active">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($payments->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $payments->nextPageUrl() }}" rel="next">Tiếp</a>
                            </li>
                        @else
                            <li class="page-item disabled">
                                <span class="page-link">Tiếp</span>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
            @endif
        </div>
    </div>
@endif
@endsection
