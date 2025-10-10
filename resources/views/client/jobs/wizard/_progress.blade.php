@php
  $pct = (int) round(($n/$total)*100);
@endphp
<div class="mb-3 text-muted small">{{ $n }}/{{ $total }} · Đăng công việc</div>
<div class="progress" style="height:6px;">
  <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%"></div>
</div>
