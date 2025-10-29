@extends('layouts.app')
@section('title','Tạo job · Bước 4')

@push('styles')
  {{-- Flatpickr CSS --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
<main class="main">
  <!-- Page Title -->
  <div class="page-title">
    <div class="container d-lg-flex justify-content-between align-items-center">
      <h1 class="mb-2 mb-lg-0">Đăng công việc</h1>
      <nav class="breadcrumbs">
        <ol>
          <li><a href="{{ route('home') }}">Trang chủ</a></li>
          <li class="current">Đăng công việc</li>
        </ol>
      </nav>
    </div>
  </div>

  <div class="container" style="max-width:780px;margin-top:50px;margin-bottom:200px;">
    @include('client.jobs.wizard._progress', ['n'=>$n,'total'=>$total])

    @php
      // Lấy giá trị deadline hiện có (YYYY-MM-DD[ HH:MM:SS]..)
      $raw = old('deadline', $d['deadline'] ?? '');
      $iso = $raw ? \Illuminate\Support\Str::of($raw)->substr(0,10) : ''; // YYYY-MM-DD
      // Convert ra dd/mm/yyyy để hiển thị
      $display = '';
      if ($iso && preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso)) {
        try {
          $display = \Illuminate\Support\Carbon::parse($iso)->format('d/m/Y');
        } catch (\Throwable $e) {}
      }
    @endphp

    <form action="{{ route('client.jobs.wizard.store',4) }}" method="POST" class="p-4 border rounded-3 bg-white shadow-sm" id="step4Form">
      @csrf

      <label class="form-label fw-semibold">Deadline (tuỳ chọn)</label>

      {{-- Ô hiển thị dd/mm/yyyy + lịch --}}
      <input type="text"
             id="deadline_display"
             class="form-control @error('deadline') is-invalid @enderror"
             placeholder="dd/mm/yyyy"
             value="{{ $display }}"
             autocomplete="off">

      {{-- Ô ẩn submit theo ISO YYYY-MM-DD để backend nhận đúng --}}
      <input type="hidden" name="deadline" id="deadline_iso" value="{{ $iso }}">

      @error('deadline') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

      <div class="d-flex justify-content-between mt-4">
        <a class="btn btn-link" href="{{ route('client.jobs.wizard.step',3) }}">← Quay lại</a>
        <button class="btn btn-primary" type="submit">Tiếp tục</button>
      </div>
    </form>
  </div>
</main>
@endsection

@push('scripts')
  {{-- Flatpickr JS + locale vi --}}
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
  <script>
    (function () {
      const dp   = document.getElementById('deadline_display');
      const iso  = document.getElementById('deadline_iso');
      const form = document.getElementById('step4Form');

      // Helper: chuyển dd/mm/yyyy -> yyyy-mm-dd
      function toISO(dmy) {
        const m = String(dmy || '').match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!m) return '';
        const d = (+m[1]).toString().padStart(2,'0');
        const mth = (+m[2]).toString().padStart(2,'0');
        const y = m[3];
        return `${y}-${mth}-${d}`;
      }

      // Helper: so sánh ngày (yyyy-mm-dd)
      function cmpDateISO(a, b) { // a ? b -> -1,0,1
        if (!a && !b) return 0;
        if (!a) return -1;
        if (!b) return 1;
        if (a < b) return -1;
        if (a > b) return 1;
        return 0;
      }

      // Lấy yyyy-mm-dd của hôm nay (theo máy client)
      function todayISO() {
        const t = new Date();
        const y = t.getFullYear();
        const m = String(t.getMonth()+1).padStart(2,'0');
        const d = String(t.getDate()).padStart(2,'0');
        return `${y}-${m}-${d}`;
      }

      // Khởi tạo flatpickr
      const fp = flatpickr(dp, {
        locale: flatpickr.l10ns.vn || 'vn',
        dateFormat: 'd/m/Y',       // hiển thị dd/mm/yyyy
        allowInput: true,
        defaultDate: dp.value || null,
        // Không cho chọn ngày quá khứ & hôm nay: minDate = "tomorrow"
        minDate: new Date(Date.now() + 24*60*60*1000),
        // Khi chọn ngày -> cập nhật hidden iso
        onChange: function(selectedDates, dateStr) {
          // selectedDates[0] là object Date; nhưng vì dùng định dạng d/m/Y,
          // ta convert từ dateStr để chắc chắn khớp
          iso.value = toISO(dateStr);
          dp.classList.remove('is-invalid');
        },
        onClose: function(selectedDates, dateStr) {
          // Nếu gõ tay thì vẫn cập nhật iso ở sự kiện onClose
          iso.value = toISO(dateStr);
        }
      });

      // Validate khi submit: deadline phải > hôm nay (nếu có)
      form.addEventListener('submit', function(e) {
        const vDisplay = dp.value.trim();
        if (!vDisplay) return; // deadline là tuỳ chọn

        const vIso = toISO(vDisplay);
        iso.value = vIso;

        if (!vIso) {
          e.preventDefault();
          dp.classList.add('is-invalid');
          // tạo feedback runtime nếu chưa có
          let fb = dp.nextElementSibling;
          const needFb = !(fb && fb.classList.contains('invalid-feedback'));
          if (needFb) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            fb.textContent = 'Ngày không hợp lệ. Vui lòng nhập theo định dạng dd/mm/yyyy.';
            dp.insertAdjacentElement('afterend', fb);
          }
          dp.focus();
          return;
        }

        const today = todayISO();
        if (cmpDateISO(vIso, today) <= 0) {
          e.preventDefault();
          dp.classList.add('is-invalid');
          let fb = dp.nextElementSibling;
          if (!(fb && fb.classList.contains('invalid-feedback'))) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            fb.textContent = 'Deadline phải lớn hơn ngày hiện tại.';
            dp.insertAdjacentElement('afterend', fb);
          } else {
            fb.textContent = 'Deadline phải lớn hơn ngày hiện tại.';
          }
          dp.focus();
        } else {
          dp.classList.remove('is-invalid');
          const fb = dp.nextElementSibling;
          if (fb && fb.classList.contains('invalid-feedback')) fb.remove();
        }
      });
    })();
  </script>
@endpush
