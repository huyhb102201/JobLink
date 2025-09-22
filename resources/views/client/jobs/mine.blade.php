@extends('layouts.app')
@section('title','My Jobs')

@section('content')
<div class="container" style="max-width: 1100px; margin-top: 60px; margin-bottom: 200px;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Công việc đã đăng</h1>
    <a class="btn btn-primary" href="{{ route('client.jobs.choose') }}"><i class="bi bi-plus-circle"></i> Đăng job</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if($jobs->isEmpty())
    <div class="text-muted">Bạn chưa đăng công việc nào.</div>
  @else
    <div class="list-group shadow-sm">
      @foreach($jobs as $job)
        <div class="list-group-item py-3">
          <div class="d-flex justify-content-between gap-3">
            <div>
              <div class="d-flex align-items-center gap-2">
                <a class="fw-semibold text-decoration-none" href="{{ route('jobs.show', $job->job_id) }}">
                  {{ $job->title }}
                </a>
                <span class="badge rounded-pill
                  {{ $job->status === 'open' ? 'bg-success-subtle text-success border border-success-subtle'
                                             : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                  {{ strtoupper($job->status) }}
                </span>
              </div>
              <div class="small text-muted mt-1">
                <span class="me-3"><i class="bi bi-tag"></i> {{ $job->categoryRef->name ?? '—' }}</span>
                <span class="me-3"><i class="bi bi-wallet2"></i> {{ $job->payment_type }}@if($job->budget) · ${{ number_format($job->budget,2) }}@endif</span>
                @if($job->deadline)<span><i class="bi bi-calendar-event"></i> {{ \Illuminate\Support\Carbon::parse($job->deadline)->toDateString() }}</span>@endif
              </div>
              <div class="text-truncate mt-2" style="max-width: 800px;">
                {{ $job->description }}
              </div>
            </div>
            <div class="text-end">
              <a href="{{ route('jobs.show', $job->job_id) }}" class="btn btn-sm btn-outline-primary">Xem</a>
              {{-- Nếu có trang edit/close thì thêm ở đây --}}
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-3">
      {{ $jobs->links() }}
    </div>
  @endif
</div>
@endsection
