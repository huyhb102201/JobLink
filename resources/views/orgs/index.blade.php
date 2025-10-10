@extends('layouts.app')
@section('title', 'JobLink - Doanh nghiệp')
@section('content')
  <main class="main">

    <!-- Page Title -->
    <div class="page-title py-4 bg-light">
      <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0">Danh sách doanh nghiệp</h1>
        <nav class="breadcrumbs">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active">Doanh nghiệp</li>
          </ol>
        </nav>
      </div>
    </div>

    <!-- Filter Icon -->
    <section class="py-3 bg-white">
      <div class="container d-flex justify-content-end">
        <button id="toggle-filter" class="btn btn-outline-primary btn-sm rounded-circle">
          <i class="bi bi-funnel fs-5"></i>
        </button>
      </div>

      <!-- Hidden Filter Form -->
      <div id="filter-form-container" class="container mt-3" style="display: none;">
        <form id="orgs-filter" class="row g-3 align-items-end bg-light p-3 rounded shadow-sm">

          <!-- Keyword -->
          <div class="col-md-3">
            <label class="form-label">Tên doanh nghiệp</label>
            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm...">
          </div>

          <!-- Category -->
          <div class="col-md-3">
            <label class="form-label">Ngành nghề</label>
            <select name="category" class="form-select select2">
              <option value="">Tất cả</option>
              <option value="it">Công nghệ thông tin</option>
              <option value="finance">Tài chính</option>
              <option value="marketing">Marketing</option>
              <option value="hr">Nhân sự</option>
            </select>
          </div>

          <!-- Location -->
          <div class="col-md-3">
            <label class="form-label">Khu vực</label>
            <select name="location" class="form-select select2">
              <option value="">Tất cả</option>
              <option value="hanoi">Hà Nội</option>
              <option value="hcm">TP.Hồ Chí Minh</option>
              <option value="danang">Đà Nẵng</option>
            </select>
          </div>

          <!-- Sort -->
          <div class="col-md-3">
            <label class="form-label">Lọc theo</label>
            <select name="sort" class="form-select select2">
              <option value="newest">Mới nhất</option>
              <option value="oldest">Cũ nhất</option>
              <option value="most_members">Nhiều thành viên</option>
              <option value="available">Còn trống</option>
              <option value="my_orgs">Của tôi</option>
            </select>
          </div>

        </form>
      </div>
    </section>

    <!-- Orgs List -->
    <section id="services" class="services section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div id="orgs-list">
          @include('orgs.partials.orgs-list', ['orgs' => $orgs])
        </div>

        <div id="pagination-wrapper" class="mt-4">
          {{ $orgs->links('components.pagination') }}
        </div>
      </div>
    </section>
  </main>

  <!-- JS -->
  <script>
    $(document).ready(function () {

      // Toggle filter form
      $('#toggle-filter').click(function () {
        $('#filter-form-container').slideToggle(200);
        $(this).find('i').toggleClass('bi-funnel bi-x');
      });

      // Initialize Select2
      $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chọn...',
        allowClear: true
      });

      // Ajax load function
      function loadOrgs(page = 1) {
        let filters = $("#orgs-filter").serialize();
        $.ajax({
          url: "/orgs?page=" + page,
          type: "GET",
          dataType: "json",
          data: filters,
          beforeSend: function () { $("#orgs-list").css("opacity", "0.5"); },
          success: function (res) {
            $("#orgs-list").html(res.orgs);
            $("#pagination-wrapper").html(res.pagination);
            $("#orgs-list").css("opacity", "1");
          },
          error: function () {
            alert("Không load được dữ liệu. Vui lòng thử lại.");
            $("#orgs-list").css("opacity", "1");
          }
        });
      }

      // Pagination click
      $(document).on('click', '.ajax-page-link', function (e) {
        e.preventDefault();
        let page = $(this).data('page');
        loadOrgs(page);
      });

      // Auto apply filter on change / keyup
      $('#orgs-filter input[name="keyword"]').on('keyup', function () {
        loadOrgs(1);
      });
      $('#orgs-filter select').on('change', function () {
        loadOrgs(1);
      });

    });
  </script>

  <!-- CSS -->
  <style>
    #toggle-filter {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Card hover effect */
    #orgs-list .card {
      transition: transform 0.3s, box-shadow 0.3s;
    }

    #orgs-list .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    /* Equal height card */
    #orgs-list .d-flex {
      display: flex;
    }

    #orgs-list .flex-fill {
      display: flex;
      flex-direction: column;
    }
  </style>

  <!-- Include Select2 CSS & JS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
    rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@endsection