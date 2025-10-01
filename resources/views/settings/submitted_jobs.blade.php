@extends('settings.layout')

@section('settings_content')
  <section class="card border-0 shadow-sm">
    <div class="card-body">
      <h5 class="mb-4 fw-bold">Lịch sử công việc</h5>

      <div class="table-responsive" style="padding: 15px;">
        <table id="jobs-table" class="table table-striped table-hover align-middle border">
          <thead class="table-light">
            <tr>
              <th>STT</th>
              <th>Công việc</th>
              <th>Danh mục</th>
              <th>Ngân sách</th>
              <th>Ngày nộp</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            @foreach($applies as $apply)
              @php
                $job = $apply->job;
              @endphp
              <tr>
                <td></td> {{-- STT sẽ do DataTables tự đánh --}}

                <td>
                  <a href="{{ route('jobs.show', $job->job_id) }}" class="fw-semibold text-dark text-decoration-none">
                    <i class="bi bi-briefcase-fill me-1 text-secondary"></i> {{ $job->title }}
                  </a>
                  <div class="small text-muted">{{ Str::limit($job->description, 80) }}</div>
                </td>

                <td>
                  <span class="badge bg-light text-dark border">
                    <i class="bi bi-folder-fill me-1"></i> {{ $job->jobCategory->name ?? '' }}
                  </span>
                </td>

                <td>
                  <span class="badge bg-light text-success border">
                    <i class="bi bi-cash-coin me-1"></i> {{ number_format($job->budget, 0) }} đ
                  </span>
                </td>

                <td>
                  <i class="bi bi-clock-history me-1 text-muted"></i>
                  {{ \Carbon\Carbon::parse($apply->created_at)->format('H:i d/m/Y') }}
                </td>

                <td>
                  @php
                    $statusLabel = '';
                    $statusClass = '';

                    switch ($apply->status) {
                      case 1:
                        $statusLabel = 'Chờ duyệt';
                        $statusClass = 'bg-light text-primary border';
                        $statusIcon = 'bi-hourglass-split';
                        break;
                      case 2:
                        $statusLabel = 'Đã duyệt';
                        $statusClass = 'bg-light text-success border';
                        $statusIcon = 'bi-check-circle';
                        break;
                      case 3:
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
                  @endphp

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
  {{-- DataTables + Buttons --}}
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
    $('#jobs-table').DataTable({
      pageLength: 10,
      responsive: true,
      autoWidth: false,
      lengthMenu: [
        [10, 25, 50, -1],
        [10, 25, 50, "Tất cả"]
      ],
      order: [[0, 'asc']],
      columnDefs: [
        { orderable: false, targets: [0, 5], className: 'text-center' } // Cột STT (0) và trạng thái (5) căn giữa
      ],
      drawCallback: function (settings) {
        var api = this.api();
        var startIndex = api.context[0]._iDisplayStart;
        api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
          cell.innerHTML = startIndex + i + 1;
        });
      },
      fixedHeader: true,
      dom: "<'row mb-3'<'col-md-4'l><'col-md-4 text-center'B><'col-md-4'f>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-5'i><'col-sm-7'p>>",
      buttons: [
        {
          extend: 'copy',
          text: '<i class="bi bi-clipboard-check me-1"></i> Copy',
          exportOptions: { columns: ':visible:not(:last-child)' }
        },
        {
          extend: 'excelHtml5',
          text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
          title: 'Lịch sử công việc',
          filename: 'lich_su_cong_viec',
          exportOptions: { columns: ':visible:not(:last-child)' }
        },
        {
          extend: 'pdf',
          text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
          title: 'Lịch sử công việc',
          filename: 'lich_su_cong_viec',
          exportOptions: { columns: ':visible:not(:last-child)' }
        }
      ],
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
      }
    });

  </script>
@endpush