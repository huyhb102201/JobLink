@php
    $status = $task->status;
@endphp

@if($status === 'completed' || $status === 1)
    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2">
        <i class="bi bi-check-circle me-1"></i> Hoàn thành
    </span>
@elseif($status === 'in_progress' || $status === 2)
    <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle px-3 py-2">
        <i class="bi bi-arrow-repeat me-1"></i> Đang làm
    </span>
@elseif($status === 'pending' || $status === 0)
    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
        <i class="bi bi-hourglass me-1"></i> Chờ
    </span>
@else
    <span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">
        {{ ucfirst($status) }}
    </span>
@endif
