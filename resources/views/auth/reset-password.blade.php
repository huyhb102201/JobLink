@extends('layouts.app')
@section('title','Đặt lại mật khẩu')

@section('content')
<div class="container" style="max-width:500px; margin-top:60px; margin-bottom:80px;">
  <h3 class="mb-3">Đặt lại mật khẩu</h3>

  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ old('email', $email ?? '') }}">

    {{-- Mật khẩu mới --}}
    {{-- Mật khẩu mới --}}
<div class="mb-3">
  <label class="form-label">Mật khẩu mới</label>

  {{-- wrapper chỉ chứa input để canh icon đúng tâm ô --}}
  <div class="position-relative">
    <input id="password" type="password" name="password"
           class="form-control pe-5 @error('password') is-invalid @enderror"
           required autocomplete="new-password">

    <button type="button"
            class="btn btn-link p-0 border-0 text-muted position-absolute top-50 end-0 translate-middle-y me-3 btn-toggle-eye"
            data-toggle-password="#password" aria-label="Hiện/ẩn mật khẩu">
      <i class="bi bi-eye fs-5"></i>
    </button>
  </div>


{{-- Xác nhận mật khẩu --}}
<div class="mb-3">
  <label class="form-label">Xác nhận mật khẩu</label>

  <div class="position-relative">
    <input id="password_confirmation" type="password" name="password_confirmation"
           class="form-control pe-5" required autocomplete="new-password">

    <button type="button"
            class="btn btn-link p-0 border-0 text-muted position-absolute top-50 end-0 translate-middle-y me-3 btn-toggle-eye"
            data-toggle-password="#password_confirmation" aria-label="Hiện/ẩn mật khẩu">
      <i class="bi bi-eye fs-5"></i>
    </button>
  </div>
</div>
  {{-- Meter --}}
  <div class="mt-2">
    <div class="progress" style="height:8px;">
      <div id="pwd-strength-bar" class="progress-bar bg-danger" role="progressbar" style="width:0%"></div>
    </div>
    <small id="pwd-strength-text" class="d-block mt-1 text-muted">Nhập mật khẩu để đánh giá độ mạnh…</small>
    <ul class="small mt-2 mb-0" id="pwd-checklist" style="padding-left:18px;">
      <li data-req="len">Tối thiểu 8 ký tự</li>
      <li data-req="lower">Có chữ thường (a–z)</li>
      <li data-req="upper">Có chữ hoa (A–Z)</li>
      <li data-req="num">Có số (0–9)</li>
      <li data-req="sym">Có ký tự đặc biệt (!@#$...)</li>
    </ul>
  </div>
</div>
    <button id="submit-btn" class="btn btn-success w-100" type="submit">Cập nhật mật khẩu</button>
  </form>
</div>
{{-- Script nên đặt sau các phần tử trên --}}
{{-- Toast container --}}
<div id="toast-area" class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;"></div>

<script>
(function () {
  const pwd  = document.getElementById('password');
  const bar  = document.getElementById('pwd-strength-bar');
  const text = document.getElementById('pwd-strength-text');
  const list = document.getElementById('pwd-checklist');
  const submitBtn = document.getElementById('submit-btn');

  const reqs = {
    len:   v => v.length >= 8,
    lower: v => /[a-z]/.test(v),
    upper: v => /[A-Z]/.test(v),
    num:   v => /\d/.test(v),
    sym:   v => /[^A-Za-z0-9]/.test(v),
  };

  function scorePassword(v) {
    let s = 0;
    s += reqs.len(v)   ? 1 : 0;
    s += reqs.lower(v) ? 1 : 0;
    s += reqs.upper(v) ? 1 : 0;
    s += reqs.num(v)   ? 1 : 0;
    s += reqs.sym(v)   ? 1 : 0;
    if (v.length >= 12) s += 1; // thưởng
    return Math.min(s, 6);
  }

  function updateChecklist(v) {
    list.querySelectorAll('li').forEach(li => {
      const key = li.getAttribute('data-req');
      const ok  = reqs[key](v);
      li.style.color = ok ? '#198754' : '#6c757d';
      const txt = li.textContent.replace(/^(\u2713 |\u2022 )/, ''); // bỏ ✓/•
      li.textContent = (ok ? '✓ ' : '• ') + txt;
    });
  }

  function updateUI(v) {
    const s = scorePassword(v);
    const map = [
      { w: 0,   cls: 'bg-danger',  label: 'Chưa có mật khẩu' },
      { w: 20,  cls: 'bg-danger',  label: 'Rất yếu' },
      { w: 40,  cls: 'bg-warning', label: 'Yếu' },
      { w: 60,  cls: 'bg-warning', label: 'Trung bình' },
      { w: 80,  cls: 'bg-info',    label: 'Khá mạnh' },
      { w: 100, cls: 'bg-success', label: 'Mạnh' },
      { w: 100, cls: 'bg-success', label: 'Rất mạnh' },
    ];
    const m = map[s] || map[0];

    bar.classList.remove('bg-danger','bg-warning','bg-info','bg-success');
    bar.classList.add(m.cls);
    bar.style.width = m.w + '%';
    text.textContent = v ? `Độ mạnh: ${m.label}` : 'Nhập mật khẩu để đánh giá độ mạnh…';

    updateChecklist(v);

    // (Tuỳ chọn) chỉ cho submit khi >= Trung bình (>=60%)
    submitBtn.disabled = (m.w < 60);
  }

  if (pwd) {
    pwd.addEventListener('input', e => updateUI(e.target.value || ''));
    updateUI(pwd.value || '');
  }
})();
document.querySelectorAll('[data-toggle-password]').forEach(btn => {
    btn.addEventListener('click', () => {
      const sel = btn.getAttribute('data-toggle-password');
      const input = document.querySelector(sel);
      const icon = btn.querySelector('i');

      if (!input) return;

      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';

      // đổi icon
      icon.classList.toggle('bi-eye', showing);
      icon.classList.toggle('bi-eye-slash', !showing);
    });

    // (tuỳ chọn) nhấn giữ để “nhìn lén”
    btn.addEventListener('mousedown', () => {
      const input = document.querySelector(btn.getAttribute('data-toggle-password'));
      const icon = btn.querySelector('i');
      if (input) { input.type = 'text'; icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
    });
    btn.addEventListener('mouseup', () => {
      const input = document.querySelector(btn.getAttribute('data-toggle-password'));
      const icon = btn.querySelector('i');
      if (input) { input.type = 'password'; icon.classList.add('bi-eye'); icon.classList.remove('bi-eye-slash'); }
    });
    btn.addEventListener('mouseleave', () => {
      const input = document.querySelector(btn.getAttribute('data-toggle-password'));
      const icon = btn.querySelector('i');
      if (input) { input.type = 'password'; icon.classList.add('bi-eye'); icon.classList.remove('bi-eye-slash'); }
    });
  });
  function showToast(message, variant = 'danger', delay = 3000) {
    const area = document.getElementById('toast-area');
    // tạo toast
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${variant} border-0`;
    el.setAttribute('role','alert');
    el.setAttribute('aria-live','assertive');
    el.setAttribute('aria-atomic','true');
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;
    area.appendChild(el);

    // khởi tạo & show
    const t = new bootstrap.Toast(el, { delay });
    t.show();

    // tự xoá khỏi DOM khi ẩn
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  // === Cảnh báo khi không khớp ===
  (function () {
    const form  = document.querySelector('form[action="{{ route('password.update') }}"]');
    const pwd   = document.getElementById('password');
    const conf  = document.getElementById('password_confirmation');

    if (!form || !pwd || !conf) return;

    let warned = false; // tránh spam toast khi đang gõ

    function checkMatch(show = false) {
      const v1 = pwd.value || '';
      const v2 = conf.value || '';
      const mismatch = v1 && v2 && v1 !== v2;
      if (mismatch && show && !warned) {
        showToast('Xác nhận mật khẩu không khớp', 'warning');
        warned = true;
        setTimeout(() => warned = false, 1200);
      }
      return !mismatch;
    }

    // Khi người dùng gõ vào ô xác nhận → nếu không khớp thì hiện toast nhẹ
    conf.addEventListener('input', () => checkMatch(true));

    // Trước khi submit → chặn & báo lỗi nếu không khớp
    form.addEventListener('submit', (e) => {
      if (!checkMatch(true)) {
        e.preventDefault();
        conf.focus();
      }
    });
  })();
</script>
@endsection
