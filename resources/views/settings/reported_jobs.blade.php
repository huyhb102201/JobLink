@extends('settings.layout')

@section('settings_content')
  <section class="card border-0 shadow-sm">
    <div class="card-body">
      <h5 class="mb-4 fw-bold">Danh sách công việc đã báo cáo</h5>

      <div class="table-responsive" style="padding: 15px;">
        <table id="reports-table" class="table table-striped table-hover align-middle border">
          <thead class="table-light">
            <tr>
              <th>STT</th>
              <th>Công việc</th>
              <th>Chủ công việc</th>
              <th>Lý do báo cáo</th>
              <th>Ngày báo cáo</th>
              <th>Hình ảnh minh họa</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            @foreach($reports as $report)
              @php
                $job = $report->job;
                $owner = $job?->account; // Chủ job

                // Xác định trạng thái
                switch ($report->status) {
                  case 1:
                    $statusLabel = 'Chờ xử lý';
                    $statusClass = 'bg-light text-warning border';
                    $statusIcon = 'bi-hourglass-split';
                    break;
                  case 2:
                    $statusLabel = 'Đã xử lý';
                    $statusClass = 'bg-light text-success border';
                    $statusIcon = 'bi-check-circle';
                    break;
                  case 0:
                    $statusLabel = 'Từ chối';
                    $statusClass = 'bg-light text-danger border';
                    $statusIcon = 'bi-x-circle';
                    break;
                  default:
                    $statusLabel = 'Không xác định';
                    $statusClass = 'bg-light text-secondary border';
                    $statusIcon = 'bi-question-circle';
                    break;
                }

                // Lấy danh sách ảnh (tối đa 5)
                $images = $report->images_array;
              @endphp

              <tr>
                <td></td>

                {{-- Công việc --}}
                <td>
                  @if($job)
                    <a href="{{ route('jobs.show', $job->job_id) }}" class="fw-semibold text-dark text-decoration-none">
                      <i class="bi bi-briefcase-fill me-1 text-secondary"></i> {{ $job->title }}
                    </a>
                    <div class="small text-muted">{{ Str::limit($job->description, 80) }}</div>
                  @else
                    <span class="text-muted fst-italic">Công việc đã bị xóa</span>
                  @endif
                </td>

                {{-- Chủ công việc --}}
                <td>
                  @if($owner)
                    <div class="d-flex align-items-center">
                      <div class="me-2">
                        @if(!empty($owner->avatar_url))
                          <img src="{{ $owner->avatar_url }}" alt="{{ $owner->name }}" class="rounded-circle border" width="42"
                            height="42" style="object-fit: cover;">
                        @else
                          <i class="bi bi-person-circle fs-3 text-secondary"></i>
                        @endif
                      </div>

                      <div>
                        @php
                          $profile = $job->account->profile ?? null;
                        @endphp

                        @if($profile)
                          <a href="{{ route('portfolios.show', $profile->username) }}"
                            class="fw-semibold text-dark text-decoration-none">
                            {{ $owner->name }}
                          </a>
                        @else
                          <span class="fw-semibold text-dark">{{ $owner->name }}</span>
                        @endif

                        <div class="small text-muted">
                          {{ $owner->email }}
                        </div>
                      </div>
                    </div>
                  @else
                    <span class="text-muted fst-italic">Không xác định</span>
                  @endif
                </td>

                {{-- Lý do --}}
                <td>
                  <div class="fw-semibold text-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i> {{ $report->reason }}
                  </div>
                  <div class="small text-muted">{{ Str::limit($report->message, 80) }}</div>
                </td>

                {{-- Ngày --}}
                <td>
                  <i class="bi bi-clock-history me-1 text-muted"></i>
                  {{ \Carbon\Carbon::parse($report->created_at)->format('H:i d/m/Y') }}
                </td>

                {{-- Hình ảnh --}}
                <td class="text-center">
                  @if(!empty($images))
                    <div class="d-flex flex-wrap justify-content-center" style="gap: 5px;">
                      @foreach($images as $img)
                        <a href="{{ $img }}" data-lightbox="report-{{ $report->id }}">
                          <img src="{{ $img }}" alt="img" width="55" height="55" class="rounded border object-fit-cover">
                        </a>
                      @endforeach
                    </div>
                  @else
                    <span class="text-muted">Không có</span>
                  @endif
                </td>

                {{-- Trạng thái --}}
                <td class="text-center">
                  <span class="badge {{ $statusClass }}">
                    <i class="bi {{ $statusIcon }} me-1"></i> {{ $statusLabel }}
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection

@push('scripts')
  {{-- Lightbox --}}
  <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

  {{-- DataTables --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

  <script>
    $('#reports-table').DataTable({
      pageLength: 10,
      responsive: true,
      autoWidth: false,
      order: [[0, 'asc']],
      columnDefs: [
        { orderable: false, targets: [0, 5, 6], className: 'text-center' }
      ],
      drawCallback: function (settings) {
        var api = this.api();
        var startIndex = api.context[0]._iDisplayStart;
        api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
          cell.innerHTML = startIndex + i + 1;
        });
      },
      dom: "<'row mb-3'<'col-md-4'l><'col-md-4 text-center'B><'col-md-4'f>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-5'i><'col-sm-7'p>>",
      buttons: [
        {
          extend: 'copy',
          text: '<i class="bi bi-clipboard-check me-1"></i> Copy'
        },
        {
          extend: 'excelHtml5',
          text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
          title: 'Danh sách công việc đã báo cáo',
          filename: 'danh_sach_bao_cao'
        },
        {
          extend: 'pdf',
          text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
          title: 'Danh sách công việc đã báo cáo',
          filename: 'danh_sach_bao_cao'
        }
      ],
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
      }
    });
  </script>
@endpush