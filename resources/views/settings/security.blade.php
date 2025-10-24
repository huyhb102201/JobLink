@extends('settings.layout')

@section('settings_content')
  <section class="card border-0 shadow-sm">
    <div class="card-body" style="min-height:500px;">

      <form id="securityForm" action="{{ route('settings.security.update') }}" method="POST"
        class="row g-4 needs-validation form-elevated" novalidate>
        @csrf @method('PUT')

        <div class="col-12">
          <div class="section-title">
            <i class="bi bi-shield-lock me-2"></i> Đổi mật khẩu
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Mật khẩu hiện tại</label>
          <div class="input-group input-with-icon">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" name="password_current" id="password_current"
              placeholder="••••••••" disabled required>
            <button class="btn toggle-pass" type="button" data-target="#password_current" tabindex="-1"
              aria-label="Hiện/ẩn mật khẩu">
              <i class="bi bi-eye"></i>
            </button>
            <div class="invalid-feedback">Vui lòng nhập mật khẩu hiện tại.</div>
          </div>
          <small class="form-text text-muted ms-1">Vì lý do bảo mật, bạn cần xác thực trước khi đặt mật khẩu mới.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Mật khẩu mới</label>
          <div class="input-group input-with-icon">
            <span class="input-group-text"><i class="bi bi-key"></i></span>
            <input type="password" class="form-control" name="password" id="password" placeholder="Tối thiểu 8 ký tự"
              disabled required minlength="8">
            <button class="btn toggle-pass" type="button" data-target="#password" tabindex="-1"
              aria-label="Hiện/ẩn mật khẩu">
              <i class="bi bi-eye"></i>
            </button>
            <div class="invalid-feedback">Mật khẩu mới tối thiểu 8 ký tự.</div>
          </div>
          <!-- Strength meter -->
          <div class="password-strength mt-2 d-none" id="pwMeterWrap">
            <div class="progress" style="height: 6px;">
              <div class="progress-bar" id="pwMeter" role="progressbar" style="width: 0%"></div>
            </div>
            <small id="pwHint" class="text-muted ms-1 d-block mt-1">Gợi ý: kết hợp chữ hoa, số và ký tự đặc biệt.</small>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Nhập lại mật khẩu mới</label>
          <div class="input-group input-with-icon">
            <span class="input-group-text"><i class="bi bi-check2"></i></span>
            <input type="password" class="form-control" name="password_confirmation" id="password_confirmation"
              placeholder="Nhập lại mật khẩu" disabled required>
            <button class="btn toggle-pass" type="button" data-target="#password_confirmation" tabindex="-1"
              aria-label="Hiện/ẩn mật khẩu">
              <i class="bi bi-eye"></i>
            </button>
            <div class="invalid-feedback" id="confirmFeedback">Mật khẩu nhập lại chưa khớp.</div>
          </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
          <button type="button" id="btnCancelEdit" class="btn btn-ghost d-none">
            <i class="bi bi-x-lg me-1"></i> Hủy
          </button>
          <button type="button" id="btnEditSave" class="btn btn-primary btn-modern">
            <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa
          </button>
        </div>
      </form>

    </div>
  </section>
@endsection

@push('styles')
  <style>
    .form-elevated {
      background: #fff;
      border-radius: 14px;
      padding: 20px 18px;
      box-shadow: 0 8px 22px rgba(30, 41, 59, .06);
      border: 1px solid rgba(0, 0, 0, .04);
    }

    .section-title {
      font-weight: 700;
      font-size: 1.05rem;
      color: #0f172a;
    }

    .input-with-icon .input-group-text {
      background: #f8fafc;
      border-right: 0;
    }

    .input-with-icon .form-control {
      border-left: 0;
    }

    /* ✅ Bỏ hoàn toàn khung xanh khi focus input */
    .form-control:focus {
      border-color: #ced4da !important;
      /* viền xám nhạt */
      box-shadow: none !important;
      /* không có glow xanh */
      outline: none !important;
    }

    /* Giữ layout icon trong input */
    .input-with-icon {
      box-shadow: none;
    }

    /* Nút con mắt trong suốt, không viền */
    .toggle-pass {
      background: transparent !important;
      border: none !important;
      box-shadow: none !important;
      color: #6c757d;
      transition: color .2s ease;
    }

    .toggle-pass:hover {
      color: #0d6efd;
      background: transparent !important;
    }

    /* Style nút gradient */
    .btn-modern {
      border: none;
      border-radius: 10px;
      padding: .6rem 1.1rem;
      transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .btn-modern.btn-primary {
      background: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #06b6d4 100%);
      box-shadow: 0 8px 16px rgba(37, 99, 235, .25);
    }

    .btn-modern.btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 22px rgba(37, 99, 235, .32);
    }

    .btn-modern.btn-success {
      background: linear-gradient(135deg, #16a34a 0%, #22c55e 55%, #34d399 100%);
      box-shadow: 0 8px 16px rgba(22, 163, 74, .22);
    }

    .btn-modern.btn-success:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 22px rgba(16, 185, 129, .32);
    }

    .btn-ghost {
      background: #fff;
      border: 1px solid #e2e8f0;
      color: #0f172a;
      border-radius: 10px;
      padding: .56rem 1rem;
    }

    .btn-ghost:hover {
      background: #f8fafc;
    }

    /* Thanh strength meter */
    .password-strength .progress {
      background: #eef2f7;
    }

    .password-strength .progress-bar {
      transition: width .25s ease;
    }

    /* Tắt halo xanh cho cả cụm input-group khi focus vào bất kỳ phần tử con */
    .input-with-icon:focus-within {
      box-shadow: none !important;
      outline: none !important;
    }

    /* Tắt focus ring cho từng phần tử bên trong nhóm */
    .input-with-icon .form-control:focus,
    .input-with-icon .btn:focus,
    .input-with-icon .input-group-text:focus {
      box-shadow: none !important;
      outline: none !important;
      border-color: #ced4da !important;
      /* giữ viền xám nhạt */
    }

    /* Khi validation + focus cũng không show halo */
    .was-validated .form-control:valid:focus,
    .was-validated .form-control:invalid:focus,
    .form-control.is-valid:focus,
    .form-control.is-invalid:focus {
      box-shadow: none !important;
    }

    /* (Tuỳ chọn) Nếu có .form-select trong card này */
    .input-with-icon .form-select:focus {
      box-shadow: none !important;
      outline: none !important;
      border-color: #ced4da !important;
    }
  </style>
@endpush


@push('scripts')
  {{-- SweetAlert2 CDN (nếu dự án đã có thì bỏ dòng dưới) --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    (function () {
      const form = document.getElementById('securityForm');
      const btnEditSave = document.getElementById('btnEditSave');
      const btnCancel = document.getElementById('btnCancelEdit');
      const inputs = [
        document.getElementById('password_current'),
        document.getElementById('password'),
        document.getElementById('password_confirmation'),
      ];
      const confirmInput = document.getElementById('password_confirmation');
      const passwordInput = document.getElementById('password');
      const confirmFeedback = document.getElementById('confirmFeedback');
      const pwMeterWrap = document.getElementById('pwMeterWrap');
      const pwMeter = document.getElementById('pwMeter');
      const pwHint = document.getElementById('pwHint');
      let editing = false;

      // Helper
      function toast(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1800, timerProgressBar: true, icon, title });
      }
      function showModal(icon, title, text) {
        Swal.fire({ icon, title, text, confirmButtonText: 'OK', confirmButtonColor: '#2563eb' });
      }
      function getCsrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        if (m) return m.getAttribute('content');
        const f = document.querySelector('#securityForm input[name="_token"]');
        return f ? f.value : '';
      }
      function setEnabled(enabled) { inputs.forEach(i => i.disabled = !enabled); }
      function clearFieldErrors() { inputs.forEach(i => i.classList.remove('is-invalid')); }
      function setFieldError(name, message) {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        field.classList.add('is-invalid');
        const fb = field.closest('.input-group')?.querySelector('.invalid-feedback') || field.nextElementSibling;
        if (fb) fb.textContent = message;
      }
      function resetFormUI() {
        form.classList.remove('was-validated');
        confirmInput.setCustomValidity('');
        confirmFeedback.textContent = 'Mật khẩu nhập lại chưa khớp.';
        pwMeter.style.width = '0%'; pwMeter.className = 'progress-bar'; pwMeterWrap.classList.add('d-none');
        pwHint.textContent = 'Gợi ý: kết hợp chữ hoa, số và ký tự đặc biệt.';
      }
      function setLoading(loading) {
        if (loading) {
          btnEditSave.disabled = true;
          btnEditSave.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang lưu...';
        } else {
          btnEditSave.disabled = false;
          // 👉 về đúng text theo trạng thái hiện tại
          btnEditSave.innerHTML = editing
            ? '<i class="bi bi-check2-circle me-1"></i> Lưu'
            : '<i class="bi bi-pencil-square me-1"></i> Chỉnh sửa';
        }
      }

      function toEditMode() {
        editing = true; setEnabled(true);
        btnCancel.classList.remove('d-none');
        btnEditSave.classList.remove('btn-primary');
        btnEditSave.classList.add('btn-success', 'btn-modern');
        btnEditSave.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Lưu';
        inputs[0].focus();
      }
      function toViewMode() {
        editing = false; setEnabled(false);
        btnCancel.classList.add('d-none');
        btnEditSave.classList.remove('btn-success');
        btnEditSave.classList.add('btn-primary', 'btn-modern');
        btnEditSave.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Chỉnh sửa';
        resetFormUI();
      }

      // Toggle mật khẩu
      document.querySelectorAll('.toggle-pass').forEach(btn => {
        btn.addEventListener('click', function () {
          const target = document.querySelector(this.dataset.target); if (!target) return;
          target.type = (target.type === 'password') ? 'text' : 'password';
          const icon = this.querySelector('i'); icon.classList.toggle('bi-eye'); icon.classList.toggle('bi-eye-slash');
        });
      });

      // Meter
      function scorePassword(pw) {
        let s = 0; if (!pw) return s;
        [/.{8,}/, /[A-Z]/, /[a-z]/, /[0-9]/, /[^A-Za-z0-9]/].forEach(r => { if (r.test(pw)) s++; });
        return s; // 0..5
      }
      function updatePwMeter() {
        const val = passwordInput.value || '';
        if (!editing || !val.length) { pwMeterWrap.classList.add('d-none'); return; }
        pwMeterWrap.classList.remove('d-none');
        const sc = scorePassword(val); const pct = [0, 20, 40, 60, 80, 100][sc];
        pwMeter.style.width = pct + '%'; pwMeter.className = 'progress-bar';
        if (sc <= 2) { pwMeter.classList.add('bg-danger'); pwHint.textContent = 'Mật khẩu yếu – hãy thêm số & ký tự đặc biệt.'; }
        else if (sc === 3) { pwMeter.classList.add('bg-warning'); pwHint.textContent = 'Tạm ổn – thêm chữ hoa/đặc biệt để mạnh hơn.'; }
        else { pwMeter.classList.add('bg-success'); pwHint.textContent = 'Mạnh – có thể sử dụng.'; }
      }

      // Confirm match
      function validateConfirmMatch() {
        if (passwordInput.value && confirmInput.value && passwordInput.value !== confirmInput.value) {
          confirmInput.setCustomValidity('not-match');
          confirmFeedback.textContent = 'Mật khẩu nhập lại chưa khớp.';
        } else {
          confirmInput.setCustomValidity('');
        }
      }
      confirmInput.addEventListener('input', validateConfirmMatch);
      passwordInput.addEventListener('input', function () { validateConfirmMatch(); updatePwMeter(); });

      // AJAX save
      async function ajaxSave() {
        clearFieldErrors();
        toast('info', 'Đang lưu thay đổi...');
        const body = new URLSearchParams();
        body.append('_token', getCsrf());
        body.append('_method', 'PUT');
        body.append('password_current', document.getElementById('password_current').value);
        body.append('password', document.getElementById('password').value);
        body.append('password_confirmation', document.getElementById('password_confirmation').value);

        setLoading(true);
        try {
          const res = await fetch(`{{ route('settings.security.update') }}`, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body
          });
          const data = await res.json().catch(() => ({}));

          if (res.ok) {
            showModal('success', 'Thành công', data.ok || 'Đã đổi mật khẩu thành công.');
            form.reset(); toViewMode();
          } else if (res.status === 422) {
            const errs = data.errors || {};
            Object.keys(errs).forEach(k => setFieldError(k, errs[k][0]));
            showModal('error', 'Không hợp lệ', 'Vui lòng kiểm tra lại các trường thông tin.');
          } else if (res.status === 404) {
            showModal('error', 'Lỗi', 'Không tìm thấy tài khoản.');
          } else {
            showModal('error', 'Lỗi', data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
          }
        } catch (e) {
          showModal('error', 'Mạng không ổn định', 'Không thể kết nối máy chủ. Vui lòng thử lại.');
        } finally {
          setLoading(false);
        }
      }

      // Edit/Save click
      btnEditSave.addEventListener('click', function () {
        if (!editing) { toEditMode(); return; }
        validateConfirmMatch();
        form.classList.add('was-validated');
        if (!form.checkValidity()) return;
        ajaxSave();
      });

      // Cancel
      btnCancel.addEventListener('click', function () { form.reset(); toViewMode(); });

      // Init
      toViewMode();
    })();
  </script>
@endpush