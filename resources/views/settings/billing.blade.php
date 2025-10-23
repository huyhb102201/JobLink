@extends('settings.layout')
@section('title', 'Thanh toán & Giao dịch')

@section('settings_content')
{{-- =========================== --}}
{{-- HEADER + ACTION             --}}
{{-- =========================== --}}
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-1">Thanh toán & Giao dịch</h5>
    <div class="text-muted">Quản lý thẻ ngân hàng và giao dịch rút/nhận tiền của bạn.</div>
  </div>

  <button class="btn btn-sm text-white d-flex align-items-center gap-2 px-3 py-2 rounded-pill border-0"
          style="background:linear-gradient(135deg,#4e73df,#1cc88a);box-shadow:0 4px 14px rgba(0,0,0,.12)"
          data-bs-toggle="modal" data-bs-target="#addCardModal">
    <i class="bi bi-plus-lg fs-6"></i>
    <span class="fw-semibold">Thêm thẻ ngân hàng</span>
  </button>
</div>

{{-- Alerts --}}
@if(session('success'))
  <div class="alert alert-success mb-3">{{ session('success') }}</div>
@elseif(session('error'))
  <div class="alert alert-danger mb-3">{{ session('error') }}</div>
@endif

<div class="row g-4">
  {{-- =========================== --}}
  {{-- LEFT: CARDS                 --}}
  {{-- =========================== --}}
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="fw-semibold"><i class="bi bi-bank me-1"></i> Thẻ ngân hàng của bạn</div>
        </div>

        @if(empty($cards) || count($cards) === 0)
          <div class="text-muted small" id="cardEmpty">Chưa có thẻ nào được thêm.</div>
        @else
          <div class="list-group list-group-flush" id="cardList">
            @foreach($cards as $card)
              <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:40px;height:40px;background:#eef2ff">
                    <i class="bi bi-credit-card-2-front text-primary"></i>
                  </span>
                  <div>
                    <div class="fw-semibold">{{ $card['bank'] ?? $card['bank_name'] }}</div>
                    <div class="small text-muted">
                      {{ $card['masked_account'] ?? $card['masked'] ?? ($card['card_number'] ?? '') }}
                    </div>
                    @if(!empty($card['is_default']))
                      <span class="badge bg-primary-subtle text-primary border mt-1">Mặc định</span>
                    @endif
                  </div>
                </div>

                <button type="button"
                        class="btn btn-light btn-sm text-danger border-0 p-1 delete-card"
                        title="Xóa thẻ này"
                        data-account-number="{{ $card['card_number'] ?? $card['account_number'] }}">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- =========================== --}}
  {{-- RIGHT: BALANCE + TABLES     --}}
  {{-- =========================== --}}
  <div class="col-lg-7">
    @php
      $accObj       = $account ?? null;
      $accId        = $accObj->id ?? auth()->id();
      $balanceCents = (int) ($accObj->balance_cents ?? ($balance_cents ?? 0)); // VND
      $balanceVnd   = number_format($balanceCents) . 'đ';
      $hasCards     = !empty($cards) && count($cards) > 0;
    @endphp

    {{-- Balance card --}}
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center justify-content-between"
           style="background:linear-gradient(180deg,#f8fafc,#ffffff)">
        <div>
          <div class="text-muted small">Hiện có</div>
          <div class="display-6 fw-bold" id="balanceDisplay">{{ $balanceVnd }}</div>
          <div class="text-muted small">Nguồn: Tài khoản #{{ $accId }}</div>
        </div>
        <div class="text-end">
          <button id="btnOpenWithdraw"
                  class="btn btn-primary px-4 py-2"
                  data-balance-cents="{{ $balanceCents }}"
                  data-bs-toggle="modal"
                  data-bs-target="#withdrawModal"
                  @if(!$hasCards || $balanceCents <= 0) disabled @endif>
            <i class="bi bi-cash-coin me-1"></i> Rút tiền
          </button>
          <div id="withdrawHint" class="small text-muted mt-2">
            @if(!$hasCards)
              Hãy thêm thẻ ngân hàng trước khi rút.
            @elseif($balanceCents <= 0)
              Số dư hiện tại không đủ để rút.
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Credit logs --}}
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-body">
        <div class="fw-semibold mb-2">
          <i class="bi bi-arrow-down-circle me-1"></i> Lịch sử cộng tiền
        </div>
        <div class="table-responsive border rounded-3">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:72px;">#</th>
                <th style="width:100px;">Job</th>
                <th>Ghi chú</th>
                <th class="text-end" style="width:160px;">Số tiền</th>
                <th style="width:170px;">Thời gian</th>
              </tr>
            </thead>
            <tbody>
              @php $logs = $creditLogs ?? collect(); @endphp
              @forelse($logs as $log)
                @php
                  $amountVnd = '+' . number_format((int) ($log->amount_cents ?? 0)) . 'đ';
                  $note = $log->note ?? '';
                  $type = $log->type ?? '';
                  $time = \Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d H:i');
                @endphp
                <tr>
                  <td>
                    <span class="text-muted">#{{ $log->id }}</span>
                    @if($type)
                      <div><span class="badge bg-success-subtle text-success border">{{ $type }}</span></div>
                    @endif
                  </td>
                  <td>
                    @if(!empty($log->job_id))
                      <span class="badge bg-primary-subtle text-primary border">#{{ $log->job_id }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>@if($note) {{ $note }} @else <span class="text-muted">—</span> @endif</td>
                  <td class="text-end fw-semibold">{{ $amountVnd }}</td>
                  <td>{{ $time }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">Chưa có lịch sử cộng tiền.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Withdrawal logs --}}
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-body">
        <div class="fw-semibold mb-2">
          <i class="bi bi-arrow-up-circle me-1"></i> Lịch sử rút tiền
        </div>
        @php
          $maskAcc = function ($n) {
            $d = preg_replace('/\D+/', '', (string)$n);
            $len = strlen($d);
            if ($len <= 4) return $d;
            return substr($d, 0, 2) . str_repeat('*', max(0, $len - 4)) . substr($d, -2);
          };
          $wlogs = $withdrawLogs ?? collect();
        @endphp

        <div class="table-responsive border rounded-3">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:72px;">#</th>
                <th>Thẻ nhận</th>
                <th class="text-end" style="width:160px;">Thực nhận</th>
                <th class="text-end" style="width:140px;">Phí</th>
                <th class="text-end" style="width:160px;">Tổng trừ</th>
                <th style="width:140px;">Trạng thái</th>
                <th style="width:170px;">Thời gian</th>
              </tr>
            </thead>
            <tbody id="withdrawTbody">
              @forelse($wlogs as $w)
                @php
                  $receive = (int)($w->amount_cents ?? 0);
                  $fee     = (int)($w->fee_cents ?? 0);
                  $total   = $receive; // phí đã trừ vào tiền nhận
                  $accMask = $maskAcc($w->bank_account_number ?? '');
                  $status  = (string)($w->status ?? 'processing');
                  $badgeCls = match ($status) {
                    'completed', 'done', 'success' => 'bg-success',
                    'failed', 'canceled'           => 'bg-danger',
                    'processing', 'pending'        => 'bg-warning text-dark',
                    default                        => 'bg-secondary'
                  };
                  $time = \Illuminate\Support\Carbon::parse($w->created_at)->format('Y-m-d H:i');
                @endphp
                <tr>
                  <td><span class="text-muted">#{{ $w->id }}</span></td>
                  <td>
                    @if(!empty($w->bank_account_number))
                      <i class="bi bi-credit-card-2-front me-1"></i> {{ $accMask }}
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-end fw-semibold">{{ number_format($receive - $fee) }}đ</td>
                  <td class="text-end">{{ number_format($fee) }}đ</td>
                  <td class="text-end">{{ number_format($total) }}đ</td>
                  <td><span class="badge {{ $badgeCls }}">{{ $status }}</span></td>
                  <td>{{ $time }}</td>
                </tr>
              @empty
                <tr id="withdrawEmptyRow" data-empty-row="1">
                  <td colspan="7" class="text-center text-muted py-4">Chưa có lịch sử rút tiền.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Hidden meta for JS --}}
<button id="openWithdrawBtn" class="d-none" data-balance-cents="{{ $balanceCents }}"></button>

{{-- =========================== --}}
{{-- MODALS                      --}}
{{-- =========================== --}}

{{-- Add card modal --}}
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addCardForm" action="{{ route('settings.billing.addCard') }}" method="POST" class="modal-content border-0 shadow">
      @csrf
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addCardModalLabel">
          <i class="bi bi-credit-card me-1"></i> Thêm thẻ ngân hàng
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Ngân hàng</label>
          <div class="mb-2">
            <input id="bankSearch" type="text" class="form-control form-control-sm" placeholder="Tìm ngân hàng (VD: vietcombank, vcb, agribank)...">
          </div>
          <div class="d-flex align-items-center gap-2">
            <img id="bankLogoPreview" src="" alt="" class="rounded border d-none" style="width:32px;height:32px;object-fit:contain;background:#fff;">
            <select id="bankSelect" class="form-select" required>
              <option value="">— Chọn ngân hàng —</option>
            </select>
          </div>
          <input type="hidden" name="bank_name"  id="bankNameHidden">
          <input type="hidden" name="bank_short" id="bankShortHidden">
          <input type="hidden" name="bank_code"  id="bankCodeHidden">
          <input type="hidden" name="bank_bin"   id="bankBinHidden">
          @error('bank_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Số thẻ / Tài khoản</label>
          <input type="text" name="card_number" value="{{ old('card_number') }}" class="form-control"
                 placeholder="Nhập số thẻ / tài khoản" inputmode="numeric" pattern="[0-9\s\-]{6,30}" required>
          @error('card_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button class="btn btn-primary" id="btnSaveCard">Lưu thẻ</button>
      </div>
    </form>
  </div>
</div>

{{-- Withdraw modal --}}
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="withdrawForm" action="{{ route('settings.billing.withdraw') }}" method="POST" class="modal-content border-0 shadow">
      @csrf
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Rút tiền</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <label class="form-label">Số dư hiện có</label>
        <input id="withdrawBalanceReadonly" type="text" class="form-control" value="{{ number_format($balanceCents) }}đ" readonly>

        <label class="form-label mt-3">Số tiền muốn rút (VND)</label>
        <input id="withdrawAmount" name="amount" type="text" class="form-control" placeholder="Ví dụ: 100000" inputmode="numeric" autocomplete="off">
        <div class="form-text">Tối thiểu 10,000đ. Phí (10%) sẽ trừ vào số nhận.</div>

        <label class="form-label mt-3">Rút về thẻ</label>
        <select id="withdrawCardSelect" name="to_account_number" class="form-select" @if(!$hasCards) disabled @endif>
          @if(!$hasCards)
            <option value="">Chưa có thẻ</option>
          @else
            @foreach($cards as $c)
              <option value="{{ $c['card_number'] }}">{{ $c['bank'] ?? $c['bank_name'] }} — {{ $c['masked'] }}</option>
            @endforeach
          @endif
        </select>

        <div class="mt-3 p-3 rounded border bg-light">
          <div class="d-flex justify-content-between"><span>Phí (10%)</span>      <strong id="withdrawFee">0đ</strong></div>
          <div class="d-flex justify-content-between mt-1"><span>Thực nhận</span> <strong id="withdrawReceive">0đ</strong></div>
          <div class="d-flex justify-content-between mt-1"><span>Tổng trừ vào số dư</span> <strong id="withdrawTotal">0đ</strong></div>
          <div class="text-danger small mt-2 d-none" id="withdrawError"></div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <button id="withdrawSubmit" class="btn btn-primary" type="submit" disabled>Xác nhận rút</button>
      </div>
    </form>
  </div>
</div>

{{-- Confirm delete modal --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title d-flex align-items-center gap-2">
          <i class="bi bi-trash"></i> Xóa thẻ ngân hàng
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-start gap-3">
          <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
          <div>
            <p class="mb-1 fw-semibold" id="confirmDeleteTitle">Bạn có chắc chắn muốn xóa?</p>
            <div class="small text-muted" id="confirmDeleteDesc"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" id="confirmDeleteCancel">Hủy</button>
        <button class="btn btn-danger" id="confirmDeleteOk">Xóa</button>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1090;"></div>
@endsection

@push('scripts')
<script>
  const DELETE_CARD_URL = "{{ route('settings.billing.deleteCard') }}";
  const WITHDRAW_URL    = "{{ route('settings.billing.withdraw') }}";

  // Toast helper
  function showToast(message, type = 'success') {
    const map = {success:'bg-success text-white', error:'bg-danger text-white', warning:'bg-warning text-dark', info:'bg-info text-dark'};
    const cls = map[type] || map.success;
    const container = document.getElementById('toastContainer');
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center border-0 ${cls}`;
    toastEl.setAttribute('role','alert'); toastEl.setAttribute('aria-live','assertive'); toastEl.setAttribute('aria-atomic','true');
    toastEl.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
    container.appendChild(toastEl);
    const t = new bootstrap.Toast(toastEl,{delay:3000,autohide:true}); t.show();
    toastEl.addEventListener('hidden.bs.toast',()=>toastEl.remove());
  }

  // Enable/disable Withdraw button
  function updateWithdrawButtonState() {
    const btn   = document.getElementById('btnOpenWithdraw');
    const hint  = document.getElementById('withdrawHint');
    const select= document.getElementById('withdrawCardSelect');
    if (!btn) return;

    const hasAnyCard = !!document.querySelector('#cardList .list-group-item');
    const balance = parseInt(btn.getAttribute('data-balance-cents') || '0', 10);
    const enough  = balance > 0;

    btn.disabled = !(hasAnyCard && enough);
    if (!hasAnyCard)      hint && (hint.textContent = 'Hãy thêm thẻ ngân hàng trước khi rút.');
    else if (!enough)     hint && (hint.textContent = 'Số dư hiện tại không đủ để rút.');
    else                  hint && (hint.textContent = '');
    if (select) select.disabled = !hasAnyCard;
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateWithdrawButtonState();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ========= Delete card =========
    document.addEventListener('click', async function (e) {
      const target = e.target.closest('.delete-card');
      if (!target) return;
      const accountNumber = target.getAttribute('data-account-number');
      const item = target.closest('.list-group-item');
      if (!accountNumber || !item) return;

      const bankName = item.querySelector('.fw-semibold')?.textContent?.trim() || 'thẻ ngân hàng';
      const masked   = item.querySelector('.small.text-muted')?.textContent?.trim() || '';
      const ok = await (function confirmDialogInline({ title, description }) {
        return new Promise((resolve) => {
          const el = document.getElementById('confirmDeleteModal');
          const okBtn = document.getElementById('confirmDeleteOk');
          const cancelBtn = document.getElementById('confirmDeleteCancel');
          el.querySelector('#confirmDeleteTitle').textContent = title || 'Bạn có chắc chắn muốn xóa?';
          el.querySelector('#confirmDeleteDesc').textContent = description || '';
          const modal = bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: true });
          const onOk = () => { okBtn.removeEventListener('click', onOk); el.removeEventListener('hidden.bs.modal', onCancel); modal.hide(); resolve(true); };
          const onCancel = () => { okBtn.removeEventListener('click', onOk); el.removeEventListener('hidden.bs.modal', onCancel); resolve(false); };
          okBtn.addEventListener('click', onOk, { once: true });
          el.addEventListener('hidden.bs.modal', onCancel, { once: true });
          modal.show();
        });
      })({ title: 'Bạn có chắc chắn muốn xóa?', description: `${bankName}${masked ? ' • ' + masked : ''}` });

      if (!ok) return;

      const originalHTML = target.innerHTML;
      target.disabled = true; target.setAttribute('aria-disabled','true');
      target.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
      item.classList.add('opacity-50');

      try {
        const formData = new FormData();
        formData.append('account_number', accountNumber);
        formData.append('_method', 'DELETE');
        const res = await fetch(DELETE_CARD_URL, {
          method:'POST',
          headers:{ 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json', ...(csrf?{'X-CSRF-TOKEN':csrf}:{}) },
          body: formData
        });
        const data = await res.json().catch(()=>null);
        if (!res.ok) throw new Error(data?.message || 'Không thể xóa thẻ.');

        item.remove();
        if (!document.querySelector('#cardList .list-group-item')) {
          document.getElementById('cardEmpty')?.classList.remove('d-none');
        }
        showToast(data?.message || 'Đã xóa thẻ ngân hàng.', 'success');
        updateWithdrawButtonState();
      } catch (err) {
        target.disabled = false; target.removeAttribute('aria-disabled'); target.innerHTML = originalHTML;
        item.classList.remove('opacity-50');
        showToast(err.message || 'Lỗi mạng, vui lòng thử lại.', 'error');
      }
    });

    // ========= Add card =========
    const addForm = document.getElementById('addCardForm');
    const addBtn  = document.getElementById('btnSaveCard');
    const addModalEl = document.getElementById('addCardModal');
    let cardListEl = document.getElementById('cardList');
    const emptyEl = document.getElementById('cardEmpty');

    if (addForm) addForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      let original = '';
      if (addBtn) { addBtn.disabled = true; original = addBtn.innerHTML; addBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...`; }
      try {
        const res = await fetch(addForm.action, {
          method:'POST',
          headers:{ 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json', ...(csrf?{'X-CSRF-TOKEN':csrf}:{}) },
          body:new FormData(addForm)
        });
        const data = await res.json().catch(()=>null);
        if (!res.ok) {
          let msg = 'Không thể thêm thẻ.'; if (data?.errors) msg = Object.values(data.errors).map(arr=>arr.join(' ')).join(' ');
          else if (data?.message) msg = data.message;
          showToast(msg,'error'); return;
        }

        addForm.reset();
        if (emptyEl) emptyEl.classList.add('d-none');
        if (!cardListEl) {
          const container = document.createElement('div');
          container.id = 'cardList'; container.className = 'list-group list-group-flush mt-2';
          (document.querySelector('.col-lg-5')||document.body).appendChild(container);
          cardListEl = document.getElementById('cardList');
        }

        const bank   = data?.card?.bank_name ?? data?.card?.bank ?? '';
        const masked = data?.card?.masked_account ?? data?.card?.masked ?? '';
        const rawAcc = data?.card?.account_number ?? data?.card?.card_number ?? '';
        const badge  = data?.card?.is_default ? `<span class="badge bg-primary-subtle text-primary border mt-1">Mặc định</span>` : '';

        const itemHtml = `
          <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#eef2ff">
                <i class="bi bi-credit-card-2-front text-primary"></i>
              </span>
              <div>
                <div class="fw-semibold">${bank}</div>
                <div class="small text-muted">${masked}</div>
                ${badge}
              </div>
            </div>
            <button type="button" class="btn btn-light btn-sm text-danger border-0 p-1 delete-card" title="Xóa thẻ này" data-account-number="${rawAcc}">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>`;
        cardListEl.insertAdjacentHTML('afterbegin', itemHtml);

        // update withdraw select
        const sel = document.getElementById('withdrawCardSelect');
        if (sel) {
          if (sel.options.length && sel.options[0].value === '') sel.options.remove(0);
          let existed = false; for (let i=0;i<sel.options.length;i++) if (sel.options[i].value===rawAcc){existed=true;break;}
          const label = `${bank} — ${masked}`;
          if (!existed) sel.add(new Option(label, rawAcc, true, true)); else sel.value = rawAcc;
          sel.disabled = false;
        }

        updateWithdrawButtonState();

        if (window.bootstrap && addModalEl) {
          const instance = bootstrap.Modal.getOrCreateInstance(addModalEl);
          addModalEl.addEventListener('hidden.bs.modal', function onH(){ addModalEl.removeEventListener('hidden.bs.modal', onH); showToast(data?.message || 'Đã thêm thẻ ngân hàng thành công!','success'); instance.dispose(); }, {once:true});
          instance.hide();
        } else showToast(data?.message || 'Đã thêm thẻ ngân hàng thành công!','success');

      } catch (err) {
        showToast('Lỗi mạng, vui lòng thử lại.','error');
      } finally {
        if (addBtn) { addBtn.disabled = false; addBtn.innerHTML = original; }
      }
    });

    // ========= Withdraw modal: calc + submit =========
    (function(){
      const FEE_RATE = 0.10, MIN_WITHDRAW = 10000;
      const parseVND = s => parseInt(String(s).replace(/[^\d]/g,''),10)||0;
      const fmtVND   = n => new Intl.NumberFormat('vi-VN').format(n)+'đ';

      function updateWithdrawCalc() {
        const metaBtn = document.getElementById('openWithdrawBtn');
        const balance = parseInt(metaBtn?.dataset.balanceCents || '0', 10);
        const amountEl = document.getElementById('withdrawAmount');
        const cardSel  = document.getElementById('withdrawCardSelect');
        const feeEl    = document.getElementById('withdrawFee');
        const recvEl   = document.getElementById('withdrawReceive');
        const totalEl  = document.getElementById('withdrawTotal');
        const errEl    = document.getElementById('withdrawError');
        const submit   = document.getElementById('withdrawSubmit');

        const amount = parseVND(amountEl.value);
        const fee    = Math.round(amount * FEE_RATE);
        const recv   = Math.max(0, amount - fee);
        const total  = amount; // phí trừ vào tiền nhận

        feeEl.textContent = fmtVND(fee);
        recvEl.textContent= fmtVND(recv);
        totalEl.textContent=fmtVND(total);

        let error = '';
        if (amount < MIN_WITHDRAW) error = `Số tiền tối thiểu là ${fmtVND(MIN_WITHDRAW)}.`;
        else if (!cardSel.value)  error = 'Vui lòng chọn thẻ nhận tiền.';
        else if (total > balance) error = `Số dư không đủ (cần ${fmtVND(total)}, đang có ${fmtVND(balance)}).`;

        if (error){ errEl.classList.remove('d-none'); errEl.textContent = error; submit.disabled = true; }
        else      { errEl.classList.add('d-none');   errEl.textContent = '';   submit.disabled = false; }
      }

      function prependWithdrawRow({bankLabel, receive, fee, total, status, createdAt}) {
        const tbody = document.getElementById('withdrawTbody'); if (!tbody) return;
        const empty = tbody.querySelector('tr[data-empty-row]'); if (empty) empty.remove();
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><span class="text-muted">—</span></td>
          <td>${bankLabel}</td>
          <td class="text-end fw-semibold">${fmtVND(receive)}</td>
          <td class="text-end">${fmtVND(fee)}</td>
          <td class="text-end">${fmtVND(total)}</td>
          <td><span class="badge ${status==='processing' ? 'bg-warning text-dark':'bg-success'}">${status}</span></td>
          <td>${createdAt}</td>`;
        tbody.prepend(tr);
      }

      const amountEl = document.getElementById('withdrawAmount');
      const cardSel  = document.getElementById('withdrawCardSelect');
      const modalEl  = document.getElementById('withdrawModal');
      const metaBtn  = document.getElementById('openWithdrawBtn');

      amountEl?.addEventListener('input', updateWithdrawCalc);
      cardSel?.addEventListener('change', updateWithdrawCalc);

      if (modalEl) modalEl.addEventListener('shown.bs.modal', () => {
        amountEl.value = '';
        const bal = parseInt(metaBtn?.dataset.balanceCents || '0', 10);
        const balanceInput = document.getElementById('withdrawBalanceReadonly');
        if (balanceInput) balanceInput.value = fmtVND(bal);
        updateWithdrawCalc();
        setTimeout(()=>amountEl.focus(),50);
      });

      const form = document.getElementById('withdrawForm');
      form?.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const submit = document.getElementById('withdrawSubmit');
        const original = submit.innerHTML;
        submit.disabled = true; submit.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Đang xử lý...`;

        const amount = parseInt(String(document.getElementById('withdrawAmount').value).replace(/[^\d]/g,''),10)||0;
        const fee    = Math.round(amount * FEE_RATE);
        const receive= Math.max(0, amount - fee);
        const total  = amount;
        const acct   = document.getElementById('withdrawCardSelect').value;
        const bankLabel = document.getElementById('withdrawCardSelect')?.selectedOptions?.[0]?.textContent?.trim() || '—';

        try{
          const res = await fetch(form.action, {
            method:'POST',
            headers:{ 'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'' },
            body:new URLSearchParams({ amount: amount, to_account_number: acct })
          });
          const data = await res.json().catch(()=>null);
          if (!res.ok) throw new Error(data?.message || 'Không thể rút tiền.');

          // Update balance everywhere
          const newBal = parseInt(data?.new_balance_cents||0,10);
          document.getElementById('balanceDisplay')?.replaceChildren(document.createTextNode(new Intl.NumberFormat('vi-VN').format(newBal)+'đ'));
          document.getElementById('withdrawBalanceReadonly')?.setAttribute('value', new Intl.NumberFormat('vi-VN').format(newBal)+'đ');
          metaBtn && (metaBtn.dataset.balanceCents = String(newBal));
          updateWithdrawButtonState?.();

          // add row to withdrawal table
          prependWithdrawRow({bankLabel, receive, fee, total, status:'processing', createdAt:new Date().toLocaleString('vi-VN')});

          if (window.bootstrap && modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
          form.reset();
          showToast(data?.message || 'Đã tạo yêu cầu rút tiền.','success');
        }catch(err){
          showToast(err.message || 'Có lỗi khi rút tiền.','error');
        }finally{
          submit.disabled = false; submit.innerHTML = original;
        }
      });
    })();
  });

  // ========= Bankcodes UI (MoMo list) =========
  (function () {
    const BANKCODES_URL = '{{ route('momo.bankcodes') }}';
    const CACHE_KEY = 'momo_bankcodes_v2_cache';
    const CACHE_TTL_MS = 24*60*60*1000;

    let loaded=false, bankData=null, items=[];
    const modalEl = document.getElementById('addCardModal');
    const selectEl= document.getElementById('bankSelect');
    const logoEl  = document.getElementById('bankLogoPreview');
    const searchEl= document.getElementById('bankSearch');
    const hiddenName = document.getElementById('bankNameHidden');
    const hiddenCode = document.getElementById('bankCodeHidden');
    const hiddenBin  = document.getElementById('bankBinHidden');

    const fold = (s='')=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/gi,'d').toLowerCase().trim();

    function readCache(){ try{const raw=localStorage.getItem(CACHE_KEY); if(!raw) return null; const {ts,data}=JSON.parse(raw); if(!ts||!data) return null; if(Date.now()-ts>CACHE_TTL_MS) return null; return data;}catch{return null;} }
    function writeCache(data){ try{ localStorage.setItem(CACHE_KEY, JSON.stringify({ts:Date.now(), data})); }catch{} }

    async function fetchBankcodes(){
      const cached=readCache(); if(cached) return cached;
      const res=await fetch(BANKCODES_URL,{method:'GET'}); if(!res.ok) throw new Error('Không tải được danh sách ngân hàng (MoMo).');
      const data=await res.json(); writeCache(data); return data;
    }

    function normalizeItems(data){
      return Object.entries(data).map(([code,v])=>{
        const name=v?.name||v?.shortName||code;
        const short=v?.shortName||''; const bin=v?.bin||v?.napasCode||''; const logo=v?.bankLogoUrl||'';
        const label=short?`${name} (${short} / ${code})`:`${name} (${code})`;
        const keywords=fold([name,short,code,bin].filter(Boolean).join(' '));
        return {code,name,short,bin,logo,label,keywords};
      }).sort((a,b)=>a.name.localeCompare(b.name,'vi'));
    }

    function renderOptions(list){
      selectEl.length = 1;
      for(const it of list){
        const opt=document.createElement('option');
        opt.value=it.code; opt.textContent=it.label;
        opt.setAttribute('data-label', it.label);
        opt.setAttribute('data-name', it.name);
        opt.setAttribute('data-short', it.short||'');
        opt.setAttribute('data-bin', it.bin||'');
        opt.setAttribute('data-logo', it.logo||'');
        opt.setAttribute('data-kw', it.keywords||'');
        selectEl.appendChild(opt);
      }
    }

    function filterOptions(q){
      const query=fold(q);
      for(let i=1;i<selectEl.options.length;i++){
        const opt=selectEl.options[i]; const kw=opt.getAttribute('data-kw')||'';
        opt.hidden = query && !kw.includes(query);
      }
      if (query){
        const visible=[...selectEl.options].slice(1).find(o=>!o.hidden);
        if (visible){ selectEl.value = visible.value; onChangeBank(); }
      }
    }

    function onChangeBank(){
      const opt=selectEl.options[selectEl.selectedIndex];
      if (!opt || !opt.value){
        hiddenName.value=''; hiddenCode.value=''; hiddenBin.value=''; document.getElementById('bankShortHidden').value='';
        if (logoEl){ logoEl.classList.add('d-none'); logoEl.removeAttribute('src'); }
        return;
      }
      const label=opt.getAttribute('data-label')||opt.textContent||opt.value;
      const short=opt.getAttribute('data-short')||'';
      const bin  =opt.getAttribute('data-bin')||'';
      const logo =opt.getAttribute('data-logo')||'';
      hiddenName.value=label; hiddenCode.value=opt.value; hiddenBin.value=bin; document.getElementById('bankShortHidden').value=short;
      if (logo){ logoEl.src=logo; logoEl.classList.remove('d-none'); } else { logoEl.classList.add('d-none'); logoEl.removeAttribute('src'); }
    }

    if (modalEl){
      modalEl.addEventListener('shown.bs.modal', async ()=>{
        if (loaded){ setTimeout(()=>searchEl?.focus(),50); return; }
        try{
          selectEl.disabled=true; selectEl.innerHTML=`<option value="">Đang tải danh sách ngân hàng...</option>`;
          bankData = await fetchBankcodes(); items = normalizeItems(bankData);
          selectEl.innerHTML=`<option value="">— Chọn ngân hàng —</option>`; renderOptions(items); loaded=true;
        }catch(e){
          selectEl.innerHTML=`<option value="">Không tải được danh sách ngân hàng (MoMo)</option>`;
        }finally{
          selectEl.disabled=false; setTimeout(()=>searchEl?.focus(),50);
        }
      });
    }
    if (selectEl) selectEl.addEventListener('change', onChangeBank);
    if (searchEl) searchEl.addEventListener('input', e=>filterOptions(e.target.value));
  })();
</script>
@endpush
