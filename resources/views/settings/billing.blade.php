@extends('settings.layout')
@section('title', 'Thanh toán & Giao dịch')

@section('settings_content')
  <div class="card border-0 shadow-sm" style="margin-bottom:200px;">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Thanh toán & Giao dịch</h5>
          <div class="text-muted">Quản lý thẻ ngân hàng rút tiền và xem lịch sử giao dịch.</div>
        </div>

        <!-- Nút mở modal thêm thẻ -->
        <button class="btn btn-sm text-white d-flex align-items-center gap-2 px-3 py-1 rounded-pill border-0"
          style="background: linear-gradient(135deg, #4e73df, #1cc88a); box-shadow: 0 2px 6px rgba(0,0,0,0.15);"
          data-bs-toggle="modal" data-bs-target="#addCardModal">
          <i class="bi bi-plus-lg fs-6"></i>
          <span class="fw-semibold">Thêm thẻ ngân hàng</span>
        </button>


      </div>

      {{-- Alerts (giống page liên kết dịch vụ) --}}
      @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
      @elseif(session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
      @endif

      <div class="row g-4 mt-2">
        {{-- Thẻ ngân hàng --}}
        <div class="col-lg-5">
          <div class="fw-semibold mb-2"><i class="bi bi-bank me-1"></i> Thẻ ngân hàng của bạn</div>

          @if(empty($cards) || count($cards) === 0)
            <div class="text-muted small" id="cardEmpty">Chưa có thẻ nào được thêm.</div>
          @else
            <div class="list-group list-group-flush" id="cardList">
              @foreach($cards as $card)
                <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-credit-card-2-front fs-4"></i>
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
                  <div class="d-flex align-items-center gap-2">
                    {{-- chỗ đặt nút sau này --}}
                    <button type="button" class="btn btn-light btn-sm text-danger border-0 p-1 delete-card"
                      title="Xóa thẻ này" data-account-number="{{ $card['card_number'] ?? $card['account_number'] }}">
                      <i class="bi bi-x-lg"></i>
                    </button>

                  </div>
                </div>
              @endforeach
            </div>
          @endif

        </div>

        {{-- Lịch sử giao dịch --}}
        <div class="col-lg-7">
          <div class="fw-semibold mb-2"><i class="bi bi-receipt me-1"></i> Lịch sử giao dịch</div>

          <div class="table-responsive border rounded-3">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:60px;">#</th>
                  <th>Loại</th>
                  <th class="text-end" style="width:160px;">Số tiền</th>
                  <th style="width:140px;">Trạng thái</th>
                  <th style="width:140px;">Ngày</th>
                </tr>
              </thead>
              <tbody>
                @forelse($transactions as $t)
                  <tr>
                    <td>{{ $t['id'] }}</td>
                    <td>{{ $t['type'] }}</td>
                    <td class="text-end">{{ number_format($t['amount']) }}đ</td>
                    <td>
                      @php $st = $t['status']; @endphp
                      @if($st === 'Thành công')
                        <span class="badge bg-success">{{ $st }}</span>
                      @elseif($st === 'Đang xử lý')
                        <span class="badge bg-warning text-dark">{{ $st }}</span>
                      @else
                        <span class="badge bg-secondary">{{ $st }}</span>
                      @endif
                    </td>
                    <td>{{ $t['date'] }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Chưa có giao dịch.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Gợi ý phân trang (nếu có) --}}
          {{-- <div class="mt-3">{{ $transactions->links() }}</div> --}}
        </div>
      </div>
    </div>
  </div>

  {{-- Modal Thêm thẻ (giữ phong cách page settings) --}}
  <div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="addCardForm" action="{{ route('settings.billing.addCard') }}" method="POST"
        class="modal-content border-0 shadow">
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
              <input id="bankSearch" type="text" class="form-control form-control-sm"
                placeholder="Tìm ngân hàng (VD: vietcombank, vcb, agribank)...">
            </div>

            <div class="d-flex align-items-center gap-2">
              <img id="bankLogoPreview" src="" alt="" class="rounded border d-none"
                style="width:32px;height:32px;object-fit:contain;background:#fff;">
              <select id="bankSelect" class="form-select" required>
                <option value="">— Chọn ngân hàng —</option>
              </select>
            </div>

            {{-- Ẩn: gửi về server --}}
            <input type="hidden" name="bank_name" id="bankNameHidden">
            <input type="hidden" name="bank_short" id="bankShortHidden">
            <input type="hidden" name="bank_code" id="bankCodeHidden">
            <input type="hidden" name="bank_bin" id="bankBinHidden">
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
  <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>
  <!-- Confirm Delete (Bootstrap 5) -->
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

  @push('scripts')
    <script>
      const DELETE_CARD_URL = "{{ route('settings.billing.deleteCard') }}";

      // Helper: tạo & hiển thị toast
      function showToast(message, type = 'success') {
        // type: 'success' | 'error' | 'warning' | 'info'
        const map = {
          success: 'bg-success text-white',
          error: 'bg-danger text-white',
          warning: 'bg-warning text-dark',
          info: 'bg-info text-dark'
        };
        const cls = map[type] || map.success;

        const container = document.getElementById('toastContainer');
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center border-0 ${cls}`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
                                                            <div class="d-flex">
                                                              <div class="toast-body">${message}</div>
                                                              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                                            </div>
                                                          `;
        container.appendChild(toastEl);

        const t = new bootstrap.Toast(toastEl, { delay: 3000, autohide: true });
        t.show();

        // auto remove khỏi DOM khi ẩn
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
      }
    </script>

    <script>
      function confirmDialog({ title, description, okText = 'Xóa', cancelText = 'Hủy' } = {}) {
        return new Promise((resolve) => {
          const el = document.getElementById('confirmDeleteModal');
          const okBtn = document.getElementById('confirmDeleteOk');
          const cancelBtn = document.getElementById('confirmDeleteCancel');

          // Gán nội dung
          el.querySelector('#confirmDeleteTitle').textContent = title || 'Bạn có chắc chắn muốn xóa?';
          el.querySelector('#confirmDeleteDesc').textContent = description || '';

          okBtn.textContent = okText;
          cancelBtn.textContent = cancelText;

          const modal = bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: true });

          const onOk = () => {
            okBtn.removeEventListener('click', onOk);
            el.removeEventListener('hidden.bs.modal', onCancel);
            modal.hide();
            resolve(true);
          };
          const onCancel = () => {
            okBtn.removeEventListener('click', onOk);
            el.removeEventListener('hidden.bs.modal', onCancel);
            resolve(false);
          };

          okBtn.addEventListener('click', onOk, { once: true });
          el.addEventListener('hidden.bs.modal', onCancel, { once: true });

          modal.show();
        });
      }

      document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('addCardForm');
        const btn = document.getElementById('btnSaveCard');
        const modalEl = document.getElementById('addCardModal');
        let cardListEl = document.getElementById('cardList');
        const emptyEl = document.getElementById('cardEmpty');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!form) return;

        // DELETE: Event delegation với spinner + confirm()
        document.addEventListener('click', async function (e) {
          const target = e.target.closest('.delete-card');
          if (!target) return;

          const accountNumber = target.getAttribute('data-account-number');
          const item = target.closest('.list-group-item');
          if (!accountNumber || !item) return;

          // Xác nhận xóa (native)
          // Xác nhận xóa (Bootstrap modal đẹp)
          const bankName = item.querySelector('.fw-semibold')?.textContent?.trim() || 'thẻ ngân hàng';
          const masked = item.querySelector('.small.text-muted')?.textContent?.trim() || '';
          const ok = await confirmDialog({
            title: 'Bạn có chắc chắn muốn xóa?',
            description: `${bankName}${masked ? ' • ' + masked : ''}`
          });
          if (!ok) return;

          // UI: spinner + disable nút + làm mờ item
          const originalHTML = target.innerHTML;
          target.disabled = true;
          target.setAttribute('aria-disabled', 'true');
          target.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
          item.classList.add('opacity-50');

          try {
            const formData = new FormData();
            formData.append('account_number', accountNumber);
            formData.append('_method', 'DELETE');

            const res = await fetch(DELETE_CARD_URL, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
              },
              body: formData
            });

            const data = await res.json().catch(() => null);

            if (!res.ok) {
              const msg = data?.message || 'Không thể xóa thẻ.';
              // Khôi phục UI vì lỗi
              target.disabled = false;
              target.removeAttribute('aria-disabled');
              target.innerHTML = originalHTML;
              item.classList.remove('opacity-50');
              showToast(msg, 'error');
              return;
            }

            // Thành công
            item.remove();
            if (!document.querySelector('#cardList .list-group-item')) {
              if (emptyEl) emptyEl.classList.remove('d-none');
            }
            showToast(data?.message || 'Đã xóa thẻ ngân hàng.', 'success');

          } catch (err) {
            target.disabled = false;
            target.removeAttribute('aria-disabled');
            target.innerHTML = originalHTML;
            item.classList.remove('opacity-50');
            showToast('Lỗi mạng, vui lòng thử lại.', 'error');
          }
        });

        // ADD: Submit form thêm thẻ
        form.addEventListener('submit', async function (e) {
          e.preventDefault();

          let original = '';
          if (btn) {
            btn.disabled = true;
            original = btn.innerHTML;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...`;
          }

          try {
            const res = await fetch(form.action, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
              },
              body: new FormData(form)
            });

            const data = await res.json().catch(() => null);

            if (!res.ok) {
              let msg = 'Không thể thêm thẻ.';
              if (data?.errors) {
                msg = Object.values(data.errors).map(arr => arr.join(' ')).join(' ');
              } else if (data?.message) {
                msg = data.message;
              }
              showToast(msg, 'error');
              return;
            }

            // Reset form
            form.reset();

            // Tạo list nếu chưa có
            if (emptyEl) emptyEl.classList.add('d-none');
            if (!cardListEl) {
              const container = document.createElement('div');
              container.id = 'cardList';
              container.className = 'list-group list-group-flush mt-2';
              const leftCol = document.querySelector('.row.g-4.mt-2 .col-lg-5') || document.querySelector('.col-lg-5');
              if (leftCol) leftCol.appendChild(container);
              cardListEl = document.getElementById('cardList');
            }

            // Chèn thẻ mới
            const badge = data?.card?.is_default
              ? `<span class="badge bg-primary-subtle text-primary border mt-1">Mặc định</span>`
              : '';
            const bank = data?.card?.bank_name ?? data?.card?.bank ?? '';
            const masked = data?.card?.masked_account ?? data?.card?.masked ?? '';
            const rawAcc = data?.card?.account_number ?? data?.card?.card_number ?? '';

            const itemHtml = `
                                                              <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
                                                                <div class="d-flex align-items-center gap-3">
                                                                  <i class="bi bi-credit-card-2-front fs-4"></i>
                                                                  <div>
                                                                    <div class="fw-semibold">${bank}</div>
                                                                    <div class="small text-muted">${masked}</div>
                                                                    ${badge}
                                                                  </div>
                                                                </div>
                                                                <div class="d-flex align-items-center gap-2">
                                                                  <button type="button"
                                                                          class="btn btn-light btn-sm text-danger border-0 p-1 delete-card"
                                                                          title="Xóa thẻ này"
                                                                          data-account-number="${rawAcc}">
                                                                    <i class="bi bi-x-lg"></i>
                                                                  </button>
                                                                </div>
                                                              </div>`;
            cardListEl?.insertAdjacentHTML('afterbegin', itemHtml);

            // Đóng modal chuẩn Bootstrap rồi show toast
            // Đóng modal chuẩn Bootstrap rồi show toast
            // Đóng modal chuẩn Bootstrap rồi show toast
            if (window.bootstrap && modalEl) {
              const instance = bootstrap.Modal.getOrCreateInstance(modalEl);

              const onHidden = () => {
                modalEl.removeEventListener('hidden.bs.modal', onHidden);

                // ❌ ĐỪNG đụng vào body/backdrop ở đây nữa
                // ✅ Chỉ show toast và dispose instance để reset state
                showToast(data?.message || 'Đã thêm thẻ ngân hàng thành công!', 'success');
                instance.dispose(); // reset hoàn toàn state modal để lần sau mở lại sạch sẽ
              };

              modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });
              instance.hide();
            } else {
              showToast(data?.message || 'Đã thêm thẻ ngân hàng thành công!', 'success');
            }
          } catch (err) {
            showToast('Lỗi mạng, vui lòng thử lại.', 'error');
          } finally {
            if (btn) {
              btn.disabled = false;
              btn.innerHTML = original;
            }
          }
        });
      });
      (function () {
        const BANKCODES_URL = '{{ route('momo.bankcodes') }}';
        const CACHE_KEY = 'momo_bankcodes_v2_cache';
        const CACHE_TTL_MS = 24 * 60 * 60 * 1000; // 24h

        let loaded = false;
        let bankData = null;       // object keyed by bank code
        let items = [];            // mảng đã chuẩn hóa để render/filter

        const modalEl = document.getElementById('addCardModal');
        const selectEl = document.getElementById('bankSelect');
        const logoEl = document.getElementById('bankLogoPreview');
        const searchEl = document.getElementById('bankSearch');

        const hiddenName = document.getElementById('bankNameHidden');
        const hiddenCode = document.getElementById('bankCodeHidden');
        const hiddenBin = document.getElementById('bankBinHidden');

        // Bỏ dấu & lower-case để tìm kiếm
        const fold = (s = '') => s.normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/đ/gi, 'd')
          .toLowerCase()
          .trim();

        function readCache() {
          try {
            const raw = localStorage.getItem(CACHE_KEY);
            if (!raw) return null;
            const { ts, data } = JSON.parse(raw);
            if (!ts || !data) return null;
            if (Date.now() - ts > CACHE_TTL_MS) return null;
            return data;
          } catch { return null; }
        }

        function writeCache(data) {
          try { localStorage.setItem(CACHE_KEY, JSON.stringify({ ts: Date.now(), data })); } catch { }
        }

        async function fetchBankcodes() {
          const cached = readCache();
          if (cached) return cached;

          const res = await fetch(BANKCODES_URL, { method: 'GET' });
          if (!res.ok) throw new Error('Không tải được danh sách ngân hàng từ MoMo.');
          const data = await res.json();
          writeCache(data);
          return data;
        }

        function normalizeItems(data) {
          // -> [{code, name, shortName, bin, logo, label, keywords}]
          return Object.entries(data).map(([code, v]) => {
            const name = v?.name || v?.shortName || code;
            const shortName = v?.shortName || '';
            const bin = v?.bin || v?.napasCode || '';
            const logo = v?.bankLogoUrl || '';
            const label = shortName
              ? `${name} (${shortName} / ${code})`
              : `${name} (${code})`;
            const keywords = fold([name, shortName, code, bin].filter(Boolean).join(' '));
            return { code, name, shortName, bin, logo, label, keywords };
          }).sort((a, b) => a.name.localeCompare(b.name, 'vi'));
        }

        function renderOptions(list) {
          // giữ option đầu
          selectEl.length = 1;
          for (const it of list) {
            const opt = document.createElement('option');
            opt.value = it.code;
            opt.textContent = it.label; // "Tên đầy đủ (Short / CODE)"
            opt.setAttribute('data-label', it.label);
            opt.setAttribute('data-name', it.name);
            opt.setAttribute('data-short', it.shortName || '');
            opt.setAttribute('data-bin', it.bin || '');
            opt.setAttribute('data-logo', it.logo || '');
            opt.setAttribute('data-kw', it.keywords || '');
            selectEl.appendChild(opt);
          }
        }

        function filterOptions(q) {
          const query = fold(q);
          // Ẩn/hiện option theo keywords
          for (let i = 1; i < selectEl.options.length; i++) {
            const opt = selectEl.options[i];
            const kw = opt.getAttribute('data-kw') || '';
            opt.hidden = query && !kw.includes(query);
          }
          // auto chọn option đầu tiên còn hiện (nếu có query)
          if (query) {
            const visible = [...selectEl.options].slice(1).find(o => !o.hidden);
            if (visible) {
              selectEl.value = visible.value;
              onChangeBank();
            }
          }
        }

        function onChangeBank() {
          const opt = selectEl.options[selectEl.selectedIndex];
          if (!opt || !opt.value) {
            hiddenName.value = '';
            hiddenCode.value = '';
            hiddenBin.value = '';
            document.getElementById('bankShortHidden').value = '';
            if (logoEl) { logoEl.classList.add('d-none'); logoEl.removeAttribute('src'); }
            return;
          }
          // Lấy label đầy đủ để lưu (Tên đầy đủ + short + code)
          const label = opt.getAttribute('data-label') || opt.textContent || opt.value;
          const name = opt.getAttribute('data-name') || '';
          const short = opt.getAttribute('data-short') || '';
          const bin = opt.getAttribute('data-bin') || '';
          const logo = opt.getAttribute('data-logo') || '';

          // Lưu xuống hidden
          hiddenName.value = label;              // <-- LƯU NHÃN HOÀN CHỈNH
          hiddenCode.value = opt.value;
          hiddenBin.value = bin;
          document.getElementById('bankShortHidden').value = short;

          // Logo
          if (logo) { logoEl.src = logo; logoEl.classList.remove('d-none'); }
          else { logoEl.classList.add('d-none'); logoEl.removeAttribute('src'); }
        }

        if (modalEl) {
          modalEl.addEventListener('shown.bs.modal', async () => {
            if (loaded) {
              // focus vào ô tìm kiếm mỗi lần mở
              setTimeout(() => searchEl?.focus(), 50);
              return;
            }
            try {
              selectEl.disabled = true;
              selectEl.innerHTML = `<option value="">Đang tải danh sách ngân hàng...</option>`;
              bankData = await fetchBankcodes();
              items = normalizeItems(bankData);
              selectEl.innerHTML = `<option value="">— Chọn ngân hàng —</option>`;
              renderOptions(items);
              loaded = true;
            } catch (e) {
              selectEl.innerHTML = `<option value="">Không tải được danh sách ngân hàng (MoMo)</option>`;
            } finally {
              selectEl.disabled = false;
              setTimeout(() => searchEl?.focus(), 50);
            }
          });
        }

        if (selectEl) selectEl.addEventListener('change', onChangeBank);
        if (searchEl) searchEl.addEventListener('input', (e) => filterOptions(e.target.value));

      })();
    </script>
  @endpush



@endsection