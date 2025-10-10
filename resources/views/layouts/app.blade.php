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
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Source+Sans+Pro:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700;1,900&display=swap"
    rel="stylesheet">

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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @stack('styles')
</head>

<body>
  {{-- Header --}}
  @include('layouts.header')

  {{-- jQuery --}}
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  @yield('content')

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>

  {{-- Footer --}}
  @include('layouts.footer')

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script>
  <script src="{{ asset('assets/vendor/aos/aos.js') }}"></script>
  <script src="{{ asset('assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
  <!-- Main JS File -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
  @stack('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const el = document.getElementById('userDropdown');
      if (el) {
        el.addEventListener('click', function (e) {
          e.preventDefault(); // chặn nhảy trang #
          bootstrap.Dropdown.getOrCreateInstance(el).toggle();
        });
      }
    });
  </script>

  <!-- Toast Container cho thông báo -->
  <div id="chatToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

  <!-- Âm thanh thông báo -->
  <audio id="chatNotifySound" src="{{ asset('assets/sounds/notify.mp3') }}" preload="auto"></audio>

  <!-- CSS cho toast -->
  @if(file_exists(public_path('assets/css/chat.css')))
    <link href="{{ asset('assets/css/chat.css') }}" rel="stylesheet">
  @endif


  <!-- Đảm bảo Laravel Echo được load -->
  @vite('resources/js/app.js') <!-- Sử dụng bootstrap.js thay vì app.js -->

  <!-- Các biến cần thiết -->
  <script>
    window.authId = {{ auth()->id() ?? 'null' }};
    window.chatConfig = {
      defaultAvatar: "{{ asset('assets/img/defaultavatar.jpg') }}",
      chatListUrl: "{{ route('messages.chat_list') }}"
    };
  </script>

  <!-- Load JS tĩnh -->
  <script src="{{ asset('assets/js/global-chat.js') }}"></script>

  <!-- Fallback CSS cho toast nếu chat.css không load được -->
  <style>
    #chatToastContainer {
      z-index: 9999;
    }

    .toast.show {
      animation: slideInRight 0.3s ease-out;
      background: white;
      border: 1px solid #dee2e6;
      max-width: 350px;
    }

    .toast:not(.show) {
      animation: slideOutRight 0.3s ease-in;
      opacity: 0;
      transform: translateX(100%);
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(100%);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes slideOutRight {
      from {
        opacity: 1;
        transform: translateX(0);
      }

      to {
        opacity: 0;
        transform: translateX(100%);
      }
    }

    .cursor-pointer {
      cursor: pointer !important;
    }

    .toast-body {
      padding: 1rem;
    }
  </style>
</body>

</html>