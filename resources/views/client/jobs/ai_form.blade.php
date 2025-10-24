@extends('layouts.app')
@section('title', 'AI tạo form đăng job')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* ===== Layout & Cards ===== */
.page-wrap { max-width: 1140px; margin-top: 50px; }
.card-soft { border: 1px solid rgba(0,0,0,.05); border-radius: 18px; overflow: hidden; }
.card-soft .card-header { padding: .9rem 1.25rem; border-bottom: 1px solid rgba(0,0,0,.05); }
.header-gradient { background: linear-gradient(135deg,#0f172a 0%, #1e293b 60%, #0b1730 100%) !important; color:#e5f0ff !important; }
.header-primary  { background: linear-gradient(135deg,#2563eb 0%, #3b82f6 45%, #06b6d4 100%) !important; color:#fff !important; }

/* 2 cột: trái sticky để tiện kéo xuống xem form */
@media (min-width: 992px){
  .left-sticky { position: sticky; top: 88px; }
}

/* Buttons */
.btn-gradient{ border:none; border-radius:12px; padding:.6rem 1.1rem;
  background:linear-gradient(135deg,#16a34a 0%, #22c55e 50%, #34d399 100%);
  box-shadow:0 8px 18px rgba(34,197,94,.25); transition:transform .18s, box-shadow .18s; }
.btn-gradient:hover{ transform:translateY(-1px); box-shadow:0 12px 24px rgba(34,197,94,.3); }
.btn-primary-modern{ border:none; border-radius:12px; padding:.6rem 1.1rem;
  background:linear-gradient(135deg,#2563eb 0%, #3b82f6 45%, #06b6d4 100%);
  box-shadow:0 8px 18px rgba(37,99,235,.28); transition:transform .18s, box-shadow .18s; }
.btn-primary-modern:hover{ transform:translateY(-1px); box-shadow:0 12px 24px rgba(37,99,235,.34); }

/* Status pill */
.status-pill{ display:inline-flex; align-items:center; gap:.5rem; padding:.35rem .6rem; border-radius:999px; font-size:.85rem;
  background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
.status-pill.success{ background:#ecfdf5; color:#065f46; border-color:#d1fae5; }
.status-pill.error{ background:#fef2f2; color:#7f1d1d; border-color:#fee2e2; }

/* Inputs */
.form-control, .form-select { border-radius: 12px; }
.input-group .input-group-text { border-radius: 12px 0 0 12px; }
.input-group .form-control { border-radius: 0 12px 12px 0; }
.form-control:focus, .form-select:focus { box-shadow:none; border-color:#d0d7de; }
.prefix-box{ background:#f8fafc; }

/* Sticky action bar trong card form */
.sticky-actions{ position: sticky; bottom: 0; background: #fff; padding: .75rem 1rem;
  border-top: 1px solid rgba(0,0,0,.06); border-bottom-left-radius:18px; border-bottom-right-radius:18px;
  display:flex; justify-content:flex-end; }

/* Skeleton khi AI chạy */
.skel { position:relative; }
.skel * { color:transparent !important; }
.skel input::placeholder,.skel textarea::placeholder{ color:transparent !important; }
.skel::after{ content:''; position:absolute; inset:0; background:linear-gradient(90deg,#f3f4f6 25%,#eceff3 37%,#f3f4f6 63%);
  animation:shimmer 1.2s infinite; transform:translateX(-100%); }
@keyframes shimmer{ to{ transform:translateX(100%);} }
</style>
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
<div class="container page-wrap">
  <div class="row g-4">

    {{-- CỘT TRÁI: mô tả thô (lg=5/12) --}}
    <div class="col-12 col-lg-5">
      <div class="card card-soft shadow-sm left-sticky h-100">
        <div class="card-header header-gradient">
          <strong><i class="bi bi-stars me-2"></i>Nhập yêu cầu / mô tả thô</strong>
        </div>
        <div class="card-body">
          <textarea id="ai_draft" rows="10" class="form-control"
            placeholder="VD: Cần làm landing page sản phẩm, 5 section, responsive, form contact, 1 tuần, ngân sách ~$300, dùng Laravel/Bootstrap..."></textarea>

          <div class="d-flex align-items-center justify-content-between mt-3">
            <div id="aiStatus" class="status-pill" style="display:none">
              <i class="bi bi-cpu"></i> <span>AI đang phân tích & tạo form...</span>
            </div>
            <button id="btnBuild" class="btn btn-gradient px-4">
              <span class="me-1"><i class="bi bi-magic"></i></span> Tạo form bằng AI
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- CỘT PHẢI: form đăng job (lg=7/12) --}}
    <div class="col-12 col-lg-7">
      <form id="jobForm" method="POST" action="{{ route('client.jobs.ai.submit') }}" class="card card-soft shadow h-100">
        @csrf
        <div class="card-header header-primary">
          <strong><i class="bi bi-briefcase-fill me-2"></i>Form đăng job</strong>
        </div>

        <div class="card-body p-4" id="formBody">
          <div class="mb-3">
            <label class="form-label fw-semibold">Tiêu đề *</label>
            <input name="title" id="f_title" class="form-control form-control-lg" placeholder="Tiêu đề ngắn gọn">
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Danh mục *</label>
              <input type="hidden" name="category_id" id="f_category_id">
              <div class="input-group">
                <span class="input-group-text prefix-box"><i class="bi bi-grid"></i></span>
                <input name="category" id="f_category" class="form-control" placeholder="VD: Web Development" readonly>
              </div>
              <div class="text-muted mt-1" style="font-size:.85rem">AI sẽ tự gợi ý (có thể chỉnh lại).</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Tổng ngân sách (USD)</label>
              <div class="input-group">
                <span class="input-group-text prefix-box"><i class="bi bi-currency-dollar"></i></span>
                <input name="total_budget" id="f_budget_total" class="form-control" placeholder="Tự động tính toán">
              </div>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Ngân sách mỗi freelancer (VND) </label>
              <div class="input-group">
                <span class="input-group-text prefix-box"><i class="bi bi-cash-coin"></i></span>
                <input name="budget" id="f_budget" class="form-control" placeholder="VD: 500" readonly>
              </div>
              <div class="text-muted mt-1" style="font-size:.85rem">Tự tính = Tổng ngân sách / Số lượng.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Số lượng tuyển *</label>
              <div class="input-group">
                <span class="input-group-text prefix-box"><i class="bi bi-people"></i></span>
                <input name="quantity" id="f_quantity" type="number" class="form-control" min="1" value="1">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Hình thức thanh toán *</label>
              <select name="payment_type" id="f_payment" class="form-select">
                <option value="fixed">Trọn gói (Fixed)</option>
                <option value="hourly">Theo giờ (Hourly)</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Deadline</label>
              <div class="input-group">
                <span class="input-group-text prefix-box"><i class="bi bi-calendar-event"></i></span>
                <input name="deadline" id="f_deadline" class="form-control" placeholder="VD: 2025-11-30 hoặc +2 tuần">
              </div>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Mô tả *</label>
            <textarea name="description" id="f_description" rows="8" class="form-control"
              placeholder="AI sẽ điền mô tả có cấu trúc. Bạn có thể chỉnh lại."></textarea>
          </div>
        </div>

        <div class="sticky-actions">
          <button class="btn btn-primary-modern px-4"><i class="bi bi-send-fill me-1"></i> Đăng job</button>
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
const formBody = document.getElementById('formBody');

btn.addEventListener('click', async () => {
  const draft = document.getElementById('ai_draft').value.trim();
  if (!draft) { alert('Nhập mô tả/yêu cầu trước đã nhé.'); return; }

  const old = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang tạo...';

  statusEl.style.display = 'inline-flex';
  statusEl.classList.remove('success','error');
  statusEl.innerHTML = '<i class="bi bi-cpu"></i><span>AI đang phân tích & tạo form...</span>';

  toggleSkeleton(true);

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
    // --- Điền form ---
    setVal('f_title', d.title || '');
    setVal('f_category_id', d.category_id || '');
    setVal('f_category', d.category_name || d.category || '');
    setVal('f_payment', (d.payment_type === 'hourly' ? 'hourly' : 'fixed'));
    setVal('f_quantity', d.quantity || 1);
    setVal('f_budget_total', d.total_budget ?? '');
    setVal('f_budget', d.budget ?? '');
    setVal('f_deadline', d.deadline || '');
    setVal('f_description', d.description || (d._raw ?? ''));

    normalizeBudgetFromTotal();
    hookupRecalcPerFromTotal();

    statusEl.classList.add('success');
    statusEl.innerHTML = '<i class="bi bi-check2-circle"></i><span>Đã tạo xong! Kiểm tra lại và bấm Đăng job.</span>';
  } catch (e) {
    console.error(e);
    statusEl.classList.add('error');
    statusEl.innerHTML = '<i class="bi bi-exclamation-triangle"></i><span>Không tạo được. Thử mô tả rõ hơn hoặc thử lại sau.</span>';
  } finally {
    toggleSkeleton(false);
    btn.disabled = false;
    btn.innerHTML = old;
  }
});

/* ===== Helpers ===== */
function toggleSkeleton(on){ if(on) formBody.classList.add('skel'); else formBody.classList.remove('skel'); }
function toNum(v){ const n = parseFloat(String(v).replace(/[^\d.-]/g,'')); return isFinite(n) ? n : 0; }
function setVal(id, v){ const el = document.getElementById(id); if (el) el.value = v; }
function getEl(id){ return document.getElementById(id); }
function normalizeBudgetFromTotal() {
  const total = toNum(getEl('f_budget_total').value);
  const qty   = Math.max(1, parseInt(getEl('f_quantity').value || 1));
  const per   = qty > 0 ? total / qty : 0;

  // ✅ Làm tròn 2 chữ số nhưng bỏ .00 nếu không cần
  const formatted = (per % 1 === 0)
    ? per.toString()              // nếu là số nguyên thì giữ nguyên
    : per.toFixed(2).replace(/\.?0+$/, '');  // bỏ .00 hoặc .0

  setVal('f_budget', per > 0 ? formatted : '');
}
function hookupRecalcPerFromTotal(){
  const totalEl = getEl('f_budget_total'); const qtyEl = getEl('f_quantity'); const recalc = ()=>normalizeBudgetFromTotal();
  totalEl.removeEventListener?.('input', recalc); qtyEl.removeEventListener?.('input', recalc);
  totalEl.addEventListener('input', recalc); qtyEl.addEventListener('input', recalc);
}
</script>
@endpush
