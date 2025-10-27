<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'JobLink')</title>
  <!-- Favicons -->
  <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
  <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>

  <!-- Vendor CSS Files -->
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/aos/aos.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
  <!-- Main CSS File -->
  <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
  <!-- Sweet Alert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"> -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @stack('styles')
</head>

<body>
  {{-- jQuery --}}
  <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>

  {{-- Header --}}
  @include('layouts.header')

  {{-- Main Content --}}
  @yield('content')

  <!-- Toast Container for Notifications
  <div id="chatToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
  <audio id="chatNotifySound" src="{{ asset('assets/sounds/notify.mp3') }}" preload="auto"></audio> -->
  @if(!request()->is('chat'))
    @include('partials.chatBox')
  @endif

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>

  {{-- Footer --}}
  @include('layouts.footer')

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/php-email-form/validate.js') }}" async></script>
  <script src="{{ asset('assets/vendor/aos/aos.js') }}" async></script>
  <script src="{{ asset('assets/vendor/glightbox/js/glightbox.min.js') }}" async></script>
  <script src="{{ asset('assets/vendor/swiper/swiper-bundle.min.js') }}" async></script>
  <script src="{{ asset('assets/vendor/imagesloaded/imagesloaded.pkgd.min.js') }}" async></script>
  <script src="{{ asset('assets/vendor/isotope-layout/isotope.pkgd.min.js') }}" async></script>

  <script src="{{ asset('assets/js/main.js') }}" async></script>
  @vite('resources/js/app.js')
  @include('chat.scripts.global-notifications')
  @include('chat.scripts.messages-notifications')
  @stack('scripts')

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const el = document.getElementById('userDropdown');
      if (el) {
        el.addEventListener('click', function (e) {
          e.preventDefault();
          bootstrap.Dropdown.getOrCreateInstance(el).toggle();
        });
      }
    });
  </script>
</body>

</html>