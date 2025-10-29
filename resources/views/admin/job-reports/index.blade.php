@extends('admin.layouts.app')
@section('title', 'Job bị báo cáo')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.1.4/r-3.0.3/datatables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
  :root{
    --jl-primary:#6366f1; /* indigo-500 */
    --jl-primary-2:#8b5cf6; /* violet-500 */
    --jl-surface:#f8fafc;
  }
  .page-hero{
    border:0;border-radius:18px;
    background:linear-gradient(135deg, rgba(99,102,241,.12), rgba(139,92,246,.12));
    box-shadow:0 8px 24px rgba(2,6,23,.06);
  }
  .page-hero .chip{
    background:linear-gradient(135deg,var(--jl-primary),var(--jl-primary-2));
    color:#fff;border-radius:12px;padding:.4rem .7rem;font-weight:600
  }
  /* DataTables tinh chỉnh */
  table.dataTable tbody td{vertical-align:middle;}
  .dt-search input{border-radius:999px;padding:.5rem .9rem}
  .dt-length select{border-radius:10px}
  .badge{font-weight:600}
  /* Skeleton trong modal */
  .skeleton{position:relative;overflow:hidden;background:#eef2ff;border-radius:10px;height:90px}
  .skeleton::after{content:"";position:absolute;inset:0;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
    animation: shimmer 1.1s infinite;
  }
  @keyframes shimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
</style>
@endpush

@section('content')
<div class="container-fluid">

  <!-- Hero / Header -->
  <div class="page-hero p-3 p-md-4 mb-4 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <div class="chip"><i class="fa-solid fa-flag"></i></div>
      <div>
        <h1 class="h4 fw-bold mb-1">Job bị báo cáo</h1>
        <div class="text-muted small">Quản lý các job bị report, xem người báo cáo và thao tác nhanh.</div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Tổng số báo cáo</div>
    <div class="h4 mb-0 fw-bold">{{ $totalReports }}</div>
  </div></div></div>

  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Số job bị báo cáo</div>
    <div class="h4 mb-0 fw-bold">{{ $totalJobsReported }}</div>
  </div></div></div>

  {{--<div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Bị báo cáo (tuần này)</div>
    <div class="h4 mb-0 fw-bold">{{ $jobsReportedThisWeek }}</div>
  </div></div></div>

  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Bị báo cáo (tháng này)</div>
    <div class="h4 mb-0 fw-bold">{{ $jobsReportedThisMonth }}</div>
  </div></div></div>--}}

  {{-- 🔸 Thêm 2 ô mới --}}
  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Báo cáo chờ xử lí</div>
    <div class="h4 mb-0 fw-bold">{{ $reportsPending }}</div>
  </div></div></div>

  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Báo cáo đã xử lí</div>
    <div class="h4 mb-0 fw-bold">{{ $reportsResolved }}</div>
  </div></div></div>
</div>


  <!-- Table -->
  <div class="card shadow-sm">
    <div class="table-responsive p-2">
      <table id="reportedJobsTable" class="table table-striped table-hover align-middle w-100">
        <thead>
          <tr>
            <th style="width:90px">Job ID</th>
            <th>Tên Job</th>
            <th>Chủ Job</th>
            <th>Email</th>
            <th class="text-center" style="width:130px">Số báo cáo</th>
            <th class="text-center" style="width:140px">Trạng thái</th>
            <th class="text-center" style="width:110px">Xem</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td>#{{ $r->job_id }}</td>
              <td class="fw-semibold">{{ $r->job_title ?? '[Job đã xóa]' }}</td>
              <td>{{ $r->owner_name }}</td>
              <td class="text-muted small">{{ $r->owner_email }}</td>
              <td class="text-center">
                <span class="badge text-bg-danger">{{ (int)$r->report_count }}</span>
              </td>
              <td class="text-center">
                @if((int)$r->status === 2)
                  <span class="badge text-bg-secondary"><i class="fas fa-lock me-1"></i>Đã khóa</span>
                @else
                  <span class="badge text-bg-success"><i class="fas fa-clock me-1"></i>Chờ xử lý</span>
                @endif
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-primary btn-view"
                  data-job-id="{{ $r->job_id }}"
                  data-job-title="{{ $r->job_title ?? '' }}"
                  data-owner-name="{{ $r->owner_name }}"
                  data-owner-email="{{ $r->owner_email }}"
                  data-owner-id="{{ $r->owner_id ?? '' }}"
                >
                  <i class="fas fa-eye me-1"></i> Xem
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Không có job nào bị báo cáo.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- Modal chi tiết --}}
<div class="modal fade" id="reportersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header text-white" style="background:linear-gradient(135deg,var(--jl-primary),var(--jl-primary-2));">
        <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Chi tiết báo cáo Job</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <div class="small text-muted">ID Job</div>
            <div id="md_job_id" class="fw-bold"></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Tên Job</div>
            <div id="md_job_title" class="fw-bold"></div>
          </div>
          <div class="col-md-3">
            <div class="small text-muted">Chủ Job</div>
            <div id="md_owner" class="fw-bold"></div>
          </div>
          <div class="col-md-2">
            <div class="small text-muted">Email</div>
            <div id="md_email" class="text-muted"></div>
          </div>
        </div>

        <!-- Quick actions -->
        <div class="d-flex flex-wrap gap-2 mb-3">
          <button id="btnDeleteJob" class="btn btn-outline-danger">
            <i class="fa-solid fa-trash me-1"></i> Xóa Job này
          </button>
          <button id="btnLockAccount" class="btn btn-outline-warning">
            <i class="fa-solid fa-user-lock me-1"></i> Khóa tài khoản chủ job
          </button>
          <span id="actionHint" class="text-muted small ms-auto d-none">
            <i class="fa-solid fa-circle-notch fa-spin me-1"></i>Đang xử lý...
          </span>
        </div>

        <div id="md_list">
          <div class="skeleton mb-2"></div>
          <div class="skeleton mb-2"></div>
          <div class="skeleton"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Đóng</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.4/r-3.0.3/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function(){
  // ===== DataTables =====
  const dt = $('#reportedJobsTable').DataTable({
    responsive:true,
    deferRender:true,
    pageLength:10,
    order:[[4,'desc']],
    columnDefs:[
      {targets:[0,4,5,6], className:'text-center'},
      {targets:4, type:'num'}
    ],
    language:{
      processing:"Đang xử lý...", search:"Tìm:",
      lengthMenu:"Hiển thị _MENU_ dòng",
      info:"Hiển thị _START_ đến _END_ trong _TOTAL_ kết quả",
      infoEmpty:"Không có dữ liệu", infoFiltered:"(lọc từ _MAX_ tổng số)",
      loadingRecords:"Đang tải...", zeroRecords:"Không tìm thấy kết quả phù hợp",
      emptyTable:"Không có dữ liệu trong bảng",
      paginate:{first:"Đầu", previous:"Trước", next:"Tiếp", last:"Cuối"},
      aria:{sortAscending:": sắp xếp tăng dần", sortDescending:": sắp xếp giảm dần"}
    }
  });

  $('.dt-search label').addClass('w-100');
  $('.dt-search input').attr('placeholder','Tìm nhanh trong bảng...');

  // ===== Modal chi tiết: cache + hủy request cũ =====
  const jobReportCache = new Map();
  let currentFetch = null;
  let currentJobId = null;
  let currentOwnerId = null;

  $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  $(document).on('click','.btn-view', async function(e){
    e.preventDefault();
    const jobId   = $(this).data('job-id');
    const title   = $(this).data('job-title') || '';
    const owner   = $(this).data('owner-name') || '';
    const email   = $(this).data('owner-email') || '';
    const ownerId = $(this).data('owner-id') || '';

    currentJobId   = jobId;
    currentOwnerId = ownerId;

    // Fill header
    $('#md_job_id').text(jobId);
    $('#md_job_title').text(title);
    $('#md_owner').text(owner);
    $('#md_email').text(email);
    $('#md_list').html('<div class="skeleton mb-2"></div><div class="skeleton mb-2"></div><div class="skeleton"></div>');

    // Show modal immediately
    bootstrap.Modal.getOrCreateInstance(document.getElementById('reportersModal')).show();

    // From cache
    if(jobReportCache.has(jobId)){ renderReporters(jobReportCache.get(jobId)); }

    // Cancel previous
    if(currentFetch && currentFetch.abort) currentFetch.abort();
    const controller = new AbortController(); currentFetch = controller;

    const url = `{{ route('admin.reports.fetchReporters', ['jobId' => '___ID___']) }}`.replace('___ID___', jobId);
    try{
      const resp = await fetch(url, { signal:controller.signal, cache:'no-store' });
      if(!resp.ok) throw new Error('Network');
      const res = await resp.json();
      if(!res.success) throw new Error('API');
      const reporters = res.reporters || [];
      jobReportCache.set(jobId, reporters);
      renderReporters(reporters);
    }catch(err){
      if(err.name==='AbortError') return;
      $('#md_list').html('<div class="text-danger text-center py-4">Không tải được dữ liệu.</div>');
    }finally{
      if(currentFetch===controller) currentFetch=null;
    }
  });

  function renderReporters(reporters){
    if(!reporters.length){
      $('#md_list').html('<div class="text-center text-muted py-4">Không có báo cáo nào.</div>');
      return;
    }
    const MAX_FIRST=10;
    const first=reporters.slice(0,MAX_FIRST);
    let html = first.map(r => cardReporter(r)).join('');
    if(reporters.length>MAX_FIRST){
      html += `
        <div class="text-center">
          <button id="md_load_more" class="btn btn-outline-primary btn-sm">Tải thêm ${reporters.length-MAX_FIRST} mục</button>
        </div>`;
    }
    $('#md_list').html(html);
    $('#md_load_more').one('click', function(){
      $(this).prop('disabled',true).text('Đang tải...');
      const more = reporters.slice(MAX_FIRST).map(r=>cardReporter(r)).join('');
      $('#md_load_more').parent().replaceWith(more);
    });
  }

  function cardReporter(r){
    const displayName = (r.fullname && r.fullname.trim()) ? r.fullname
                        : ((r.username && r.username.trim()) ? r.username : 'Ẩn danh');
    const email = r.email || 'N/A';
    const items = (r.reports||[]).map(rep=>{
      const imgs = (rep.images||[]).map(u=>`<img src="${u}" alt="Ảnh báo cáo" width="110" height="110"
        style="object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;margin:4px" loading="lazy"
        onerror="this.style.opacity=0.3">`).join('');
      return `
        <div class="p-2 rounded" style="background:#f8f9fa;border-left:4px solid #6366f1;margin-bottom:.5rem">
          <div class="mb-1"><b>Lý do:</b> ${rep.reason || '—'}</div>
          <div class="mb-1"><b>Nội dung:</b> ${rep.message || 'Không có'}</div>
          <div class="mb-2"><b>Thời gian:</b> ${rep.created_at || ''}</div>
          ${imgs ? `<div class="d-flex flex-wrap">${imgs}</div>` : ''}
        </div>`;
    }).join('');

    return `
      <div class="card border-0 shadow-sm mb-3 animate__animated animate__fadeIn">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="fw-bold">${displayName}</div>
              <div class="small text-muted"><i class="fas fa-envelope me-1"></i>${email}</div>
            </div>
            <span class="badge text-bg-primary">${r.report_count || 1} báo cáo</span>
          </div>
          ${items}
        </div>
      </div>`;
  }

  // ====== ACTIONS in modal ======
  $('#btnDeleteJob').on('click', async function(){
    if(!currentJobId) return;
    const ok = await Swal.fire({
      icon:'warning', title:'Xác nhận xóa Job',
      html:`Bạn chắc chắn muốn <b>xóa vĩnh viễn</b> job #${currentJobId}?<br><small>Hành động này không thể khôi phục.</small>`,
      showCancelButton:true, confirmButtonText:'Xóa', confirmButtonColor:'#d33'
    }).then(r=>r.isConfirmed);
    if(!ok) return;

    toggleActionHint(true);
    try{
      const url = `/admin/reported-jobs/${currentJobId}/delete`;
      const resp = await fetch(url, { method:'DELETE', headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
      const data = await resp.json().catch(()=>({}));
      if(!resp.ok || data.success===false){
        throw new Error(data.message || 'Xóa job thất bại');
      }
      await Swal.fire({icon:'success', title:'Đã xóa!', timer:1400, showConfirmButton:false});
      // Remove row from table
      $('#reportedJobsTable button[data-job-id="'+currentJobId+'"]').closest('tr').fadeOut(200, function(){
        dt.row($(this)).remove().draw();
      });
      bootstrap.Modal.getInstance(document.getElementById('reportersModal')).hide();
    }catch(e){
      Swal.fire({icon:'error', title:'Lỗi', text:e.message || 'Không thể xóa job.'});
    }finally{ toggleActionHint(false); }
  });

  $('#btnLockAccount').on('click', async function(){
    if(!currentOwnerId){
      return Swal.fire({icon:'info', title:'Thiếu thông tin', text:'Không tìm thấy ID tài khoản chủ job.'});
    }
    const ok = await Swal.fire({
      icon:'warning', title:'Khóa tài khoản chủ job?',
      html:`Tài khoản ID: <b>#${currentOwnerId}</b> sẽ bị khóa.`,
      showCancelButton:true, confirmButtonText:'Khóa'
    }).then(r=>r.isConfirmed);
    if(!ok) return;

    toggleActionHint(true);
    try{
      const url = `/admin/accounts/${currentOwnerId}/lock`;
      const resp = await fetch(url, { method:'POST', headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
      const data = await resp.json().catch(()=>({}));
      if(!resp.ok || data.success===false){
        throw new Error(data.message || 'Khóa tài khoản thất bại');
      }
      await Swal.fire({icon:'success', title:'Đã khóa tài khoản!', timer:1400, showConfirmButton:false});
    }catch(e){
      Swal.fire({icon:'error', title:'Lỗi', text:e.message || 'Không thể khóa tài khoản.'});
    }finally{ toggleActionHint(false); }
  });

  function toggleActionHint(show){
    $('#actionHint').toggleClass('d-none', !show);
  }
});
</script>
@endpush
