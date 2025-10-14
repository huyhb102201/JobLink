@extends('layouts.app')
@section('title', 'AI tạo form đăng job')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
@endpush

@section('content')
 <main class="main">
        <!-- Page Title -->
        <div class="page-title">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Đăng công việc bằng AI</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li class="current">Đăng công việc AI</li>
                    </ol>
                </nav>
            </div>
        </div>
<div class="container" style="max-width: 980px; margin-top: 70px;">
  <div class="row g-4">
    {{-- Khung “mô tả thô” --}}
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white rounded-top-4">
          <strong><i class="bi bi-stars me-2"></i>Nhập yêu cầu / mô tả thô</strong>
        </div>
        <div class="card-body">
          <textarea id="ai_draft" rows="6" class="form-control"
            placeholder="VD: Cần làm landing page sản phẩm, 5 section, responsive, tích hợp form contact, thời gian 1 tuần, ngân sách khoảng $300, dùng Laravel/Bootstrap..."></textarea>
          <div class="text-end mt-3">
            <button id="btnBuild" class="btn btn-success px-4">
              <i class="bi bi-magic"></i> Tạo form bằng AI
            </button>
          </div>
          <div id="aiStatus" class="text-muted small mt-2" style="display:none"></div>
        </div>
      </div>
    </div>

    {{-- Form đăng job (được AI điền sẵn) --}}
    <div class="col-12">
      <form id="jobForm" method="POST" action="{{ route('client.jobs.ai.submit') }}" class="card border-0 shadow rounded-4">
  @csrf
  <div class="card-header bg-primary text-white rounded-top-4">
    <strong><i class="bi bi-briefcase-fill me-2"></i>Form đăng job</strong>
  </div>
  <div class="card-body p-4">

    <div class="mb-3">
      <label class="form-label fw-semibold">Tiêu đề *</label>
      <input name="title" id="f_title" class="form-control form-control-lg" placeholder="Tiêu đề ngắn gọn">
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Danh mục *</label>
        <input name="category" id="f_category" class="form-control" placeholder="VD: Web Development">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Ngân sách</label>
        <input name="budget" id="f_budget" class="form-control" placeholder="$300 hoặc $15-20/h">
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Hình thức thanh toán *</label>
        <select name="payment_type" id="f_payment" class="form-select">
          <option value="fixed">Trọn gói (Fixed)</option>
          <option value="hourly">Theo giờ (Hourly)</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Deadline</label>
        <input name="deadline" id="f_deadline" class="form-control" placeholder="VD: 2 tuần hoặc 2025-11-30">
      </div>
    </div>

    <div class="mt-3">
      <label class="form-label fw-semibold">Mô tả *</label>
      <textarea name="description" id="f_description" rows="8" class="form-control"
        placeholder="AI sẽ điền mô tả có cấu trúc. Bạn có thể chỉnh lại."></textarea>
    </div>

    <div class="d-flex justify-content-end mt-4">
      <button class="btn btn-primary px-4"><i class="bi bi-send-fill"></i> Đăng job</button>
    </div>

  </div>
</form>

    </div>
  </div>
</div>
</main>
@endsection

@push('scripts')
<script>
const btn = document.getElementById('btnBuild');
const statusEl = document.getElementById('aiStatus');

btn.addEventListener('click', async () => {
  const draft = document.getElementById('ai_draft').value.trim();
  if (!draft) { alert('Nhập mô tả/yêu cầu trước đã nhé.'); return; }

  btn.disabled = true;
  const old = btn.innerHTML;
  btn.innerHTML = 'Đang tạo...';
  statusEl.style.display = 'block';
  statusEl.textContent = 'AI đang phân tích & tạo form...';

  try {
    const r = await fetch("{{ route('client.jobs.ai_build') }}", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": "{{ csrf_token() }}"
      },
      body: JSON.stringify({ draft })
    });
    const data = await r.json();
    if (!data.ok) throw new Error(data.error || 'Lỗi AI');

    const d = data.data || {};
    // điền form an toàn
    document.getElementById('f_title').value       = d.title || '';
    document.getElementById('f_category').value    = d.category || '';
    document.getElementById('f_budget').value      = d.budget || '';
    document.getElementById('f_payment').value     = (d.payment_type === 'hourly' ? 'hourly' : 'fixed');
    document.getElementById('f_deadline').value    = d.deadline || '';
    document.getElementById('f_description').value = d.description || (d._raw ?? '');


    statusEl.textContent = 'Đã tạo xong! Kiểm tra lại và bấm Đăng job.';
  } catch (e) {
    console.error(e);
    alert('Không tạo được. Thử mô tả rõ hơn hoặc thử lại sau.');
    statusEl.textContent = 'AI lỗi.';
  } finally {
    btn.disabled = false;
    btn.innerHTML = old;
  }
});
</script>
@endpush
