@extends('layouts.app')
@section('title', 'JobLink - Trang chủ')
@section('content')
  <main class="main">
    <!-- Hero Section -->
    <section id="hero" class="hero section">

      <div class="container d-flex flex-column justify-content-center align-items-center text-center position-relative"
        data-aos="zoom-out">
        <img src="assets/img/hero-img.svg" class="img-fluid animated" alt="">
        <h1>Chào mừng đến với <span>JobLink</span></h1>
        <p>Nền tảng tìm kiếm, trao đổi và hỗ trợ việc làm dành cho các freelancer. Kết nối bạn với dự án phù hợp và cơ hội
          nghề nghiệp linh hoạt.</p>
        <div class="d-flex">
          <a href="#about" class="btn-get-started scrollto">Bắt đầu ngay</a>
          <a href="https://www.youtube.com/watch?v=Y7f98aduVJ8"
            class="glightbox btn-watch-video d-flex align-items-center"><i class="bi bi-play-circle"></i><span>Xem
              video</span></a>
        </div>
      </div>

    </section><!-- /Hero Section -->

    <!-- Featured Services Section -->
    <section id="featured-services" class="featured-services section">

      <div class="container">

        <div class="row gy-4">

          <div class="col-xl-3 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item position-relative">
              <div class="icon"><i class="bi bi-search icon"></i></div>
              <h4><a href="#" class="stretched-link">Tìm việc nhanh</a></h4>
              <p>Khám phá hàng ngàn dự án và công việc phù hợp dành cho người làm tự do.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-xl-3 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item position-relative">
              <div class="icon"><i class="bi bi-people icon"></i></div>
              <h4><a href="#" class="stretched-link">Kết nối Freelancer</a></h4>
              <p>Gặp gỡ, trao đổi và hợp tác với các freelancer khác trong cùng lĩnh vực.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-xl-3 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item position-relative">
              <div class="icon"><i class="bi bi-calendar4-event icon"></i></div>
              <h4><a href="#" class="stretched-link">Quản lý dự án</a></h4>
              <p>Dễ dàng theo dõi tiến độ công việc và quản lý các dự án freelance của bạn.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-xl-3 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item position-relative">
              <div class="icon"><i class="bi bi-broadcast icon"></i></div>
              <h4><a href="#" class="stretched-link">Hỗ trợ & Cộng đồng</a></h4>
              <p>Nhận hỗ trợ, chia sẻ kinh nghiệm và tham gia cộng đồng người làm tự do năng động.</p>
            </div>
          </div><!-- End Service Item -->

        </div>

      </div>

    </section><!-- /Featured Services Section -->

    <!-- About Section -->
    <section id="about" class="about section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Về Chúng Tôi</h2>
        <p>JobLink là nền tảng kết nối việc làm dành cho người làm tự do, giúp bạn tìm kiếm dự án phù hợp, trao đổi kinh
          nghiệm và phát triển sự nghiệp linh hoạt.</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up">

        <div class="row g-4 g-lg-5" data-aos="fade-up" data-aos-delay="200">

          <div class="col-lg-5">
            <div class="about-img">
              <img src="assets/img/about-portrait.jpg" class="img-fluid" alt="">
            </div>
          </div>

          <div class="col-lg-7">
            <h3 class="pt-0 pt-lg-5">Tại sao chọn JobLink cho công việc tự do của bạn?</h3>

            <!-- Tabs -->
            <ul class="nav nav-pills mb-3">
              <li><a class="nav-link active" data-bs-toggle="pill" href="#about-tab1">Tìm việc nhanh</a></li>
              <li><a class="nav-link" data-bs-toggle="pill" href="#about-tab2">Kết nối Freelancer</a></li>
              <li><a class="nav-link" data-bs-toggle="pill" href="#about-tab3">Hỗ trợ & Cộng đồng</a></li>
            </ul><!-- End Tabs -->

            <!-- Tab Content -->
            <div class="tab-content">

              <div class="tab-pane fade show active" id="about-tab1">

                <p class="fst-italic">
                  Khám phá hàng ngàn công việc và dự án freelance phù hợp với kỹ năng và nhu cầu của bạn một cách nhanh
                  chóng và tiện lợi.
                </p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Tìm việc theo kỹ năng</h4>
                </div>
                <p>Chọn các dự án phù hợp với kỹ năng của bạn, từ lập trình, thiết kế, viết lách đến marketing và nhiều
                  ngành nghề khác.</p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Dự án mới cập nhật mỗi ngày</h4>
                </div>
                <p>Các công việc freelance được cập nhật liên tục, đảm bảo bạn luôn tiếp cận cơ hội mới nhất.</p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Dễ dàng ứng tuyển</h4>
                </div>
                <p>Ứng tuyển nhanh chóng, quản lý hồ sơ và liên hệ trực tiếp với nhà tuyển dụng mà không mất thời gian.
                </p>

              </div><!-- End Tab 1 Content -->

              <div class="tab-pane fade" id="about-tab2">

                <p class="fst-italic">
                  JobLink giúp bạn kết nối với các freelancer khác và doanh nghiệp, mở rộng mạng lưới nghề nghiệp và cơ
                  hội hợp tác.
                </p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Mở rộng mạng lưới freelancer</h4>
                </div>
                <p>Gặp gỡ và trao đổi kinh nghiệm với các freelancer cùng lĩnh vực, tạo cơ hội hợp tác lâu dài.</p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Kết nối với doanh nghiệp</h4>
                </div>
                <p>Tiếp cận trực tiếp các doanh nghiệp đang tìm kiếm freelancer chất lượng cho dự án của họ.</p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Hợp tác dự án linh hoạt</h4>
                </div>
                <p>Tham gia các dự án freelance với điều kiện linh hoạt, thời gian chủ động, phù hợp với lối sống tự do.
                </p>

              </div><!-- End Tab 2 Content -->

              <div class="tab-pane fade" id="about-tab3">

                <p class="fst-italic">
                  JobLink cung cấp hỗ trợ toàn diện và tạo dựng cộng đồng freelancer năng động, giúp bạn phát triển sự
                  nghiệp bền vững.
                </p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Hỗ trợ kỹ thuật và hướng dẫn</h4>
                </div>
                <p>Nhận hướng dẫn, tư vấn và hỗ trợ kỹ thuật khi bạn gặp khó khăn trong quá trình tìm việc hoặc thực hiện
                  dự án.</p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Cộng đồng freelancer</h4>
                </div>
                <p>Tham gia diễn đàn, nhóm trao đổi và sự kiện để học hỏi, chia sẻ kinh nghiệm và kết nối với những người
                  cùng ngành.</p>

                <div class="d-flex align-items-center mt-4">
                  <i class="bi bi-check2"></i>
                  <h4>Chia sẻ kiến thức & kinh nghiệm</h4>
                </div>
                <p>Chia sẻ thành công và bài học từ các dự án thực tế, nhận phản hồi từ cộng đồng để nâng cao kỹ năng.</p>

              </div><!-- End Tab 3 Content -->

            </div>

          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Clients Section -->
    <section id="clients" class="clients section">

      <div class="container" data-aos="fade-up">

        <div class="row gy-4">

          <div class="col-xl-2 col-md-3 col-6 client-logo">
            <img src="assets/img/clients/client-1.png" class="img-fluid" alt="">
          </div><!-- End Client Item -->

          <div class="col-xl-2 col-md-3 col-6 client-logo">
            <img src="assets/img/clients/client-2.png" class="img-fluid" alt="">
          </div><!-- End Client Item -->

          <div class="col-xl-2 col-md-3 col-6 client-logo">
            <img src="assets/img/clients/client-3.png" class="img-fluid" alt="">
          </div><!-- End Client Item -->

          <div class="col-xl-2 col-md-3 col-6 client-logo">
            <img src="assets/img/clients/client-4.png" class="img-fluid" alt="">
          </div><!-- End Client Item -->

          <div class="col-xl-2 col-md-3 col-6 client-logo">
            <img src="assets/img/clients/client-5.png" class="img-fluid" alt="">
          </div><!-- End Client Item -->

          <div class="col-xl-2 col-md-3 col-6 client-logo">
            <img src="assets/img/clients/client-6.png" class="img-fluid" alt="">
          </div><!-- End Client Item -->

        </div>

      </div>

    </section><!-- /Clients Section -->

    <!-- Call To Action Section -->
    <section id="call-to-action" class="call-to-action section">

      <div class="container" data-aos="zoom-out">

        <div class="row g-5">

          <div class="col-lg-8 col-md-6 content d-flex flex-column justify-content-center order-last order-md-first">
            <h3>Cơ hội nghề nghiệp <em>tự do</em> ngay hôm nay</h3>
            <p>Tìm kiếm dự án, kết nối với doanh nghiệp và phát triển sự nghiệp freelance một cách linh hoạt, nhanh chóng
              và hiệu quả.</p>
            <a class="cta-btn align-self-start" href="#">Bắt đầu ngay</a>

          </div>

          <div class="col-lg-4 col-md-6 order-first order-md-last d-flex align-items-center">
            <div class="img">
              <img src="assets/img/cta.jpg" alt="" class="img-fluid">
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Call To Action Section -->

    <!-- Features Section -->
    <section id="features" class="features section">

      <div class="container" data-aos="fade-up">

        <ul class="nav nav-tabs row gy-4 d-flex">

          <li class="nav-item col-6 col-md-4 col-lg-2">
            <a class="nav-link active show" data-bs-toggle="tab" data-bs-target="#features-tab-1">
              <i class="bi bi-code-slash" style="color: #0dcaf0;"></i>
              <h4>Lập trình Web</h4>
            </a>
          </li>

          <li class="nav-item col-6 col-md-4 col-lg-2">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-2">
              <i class="bi bi-palette" style="color: #6610f2;"></i>
              <h4>Thiết kế Đồ họa</h4>
            </a>
          </li>

          <li class="nav-item col-6 col-md-4 col-lg-2">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-3">
              <i class="bi bi-brush" style="color: #20c997;"></i>
              <h4>UI/UX</h4>
            </a>
          </li>

          <li class="nav-item col-6 col-md-4 col-lg-2">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-4">
              <i class="bi bi-camera-video" style="color: #df1529;"></i>
              <h4>Video & Motion</h4>
            </a>
          </li>

          <li class="nav-item col-6 col-md-4 col-lg-2">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-5">
              <i class="bi bi-bar-chart" style="color: #0d6efd;"></i>
              <h4>Marketing & SEO</h4>
            </a>
          </li>

          <li class="nav-item col-6 col-md-4 col-lg-2">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-6">
              <i class="bi bi-chat-dots" style="color: #fd7e14;"></i>
              <h4>Viết Nội dung</h4>
            </a>
          </li>

        </ul>

        <div class="tab-content">

          <div class="tab-pane fade active show" id="features-tab-1">
            <div class="row gy-4">
              <div class="col-lg-8 order-2 order-lg-1" data-aos="fade-up" data-aos-delay="100">
                <h3>Lập trình Web</h3>
                <p class="fst-italic">
                  Chúng tôi cung cấp các dịch vụ lập trình web chuyên nghiệp, từ website tĩnh, động đến web app phức tạp,
                  phù hợp với nhu cầu của doanh nghiệp và cá nhân.
                </p>
                <ul>
                  <li><i class="bi bi-check-circle-fill"></i> Thiết kế giao diện responsive, thân thiện với người dùng.
                  </li>
                  <li><i class="bi bi-check-circle-fill"></i> Tích hợp API và các tính năng động hiện đại.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Tối ưu hóa tốc độ và bảo mật cho website.</li>
                </ul>
                <p>
                  Dịch vụ lập trình web của chúng tôi giúp bạn hiện thực hóa ý tưởng thành sản phẩm chất lượng cao, đảm
                  bảo
                  hiệu suất, bảo mật và trải nghiệm người dùng tốt nhất.
                </p>
              </div>
              <div class="col-lg-4 order-1 order-lg-2 text-center" data-aos="fade-up" data-aos-delay="200">
                <img src="assets/img/features-1.svg" alt="Lập trình Web" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content 1 -->

          <div class="tab-pane fade" id="features-tab-2">
            <div class="row gy-4">
              <div class="col-lg-8 order-2 order-lg-1">
                <h3>Thiết kế Đồ họa</h3>
                <p>
                  Chúng tôi cung cấp các dịch vụ thiết kế đồ họa chuyên nghiệp, từ logo, banner, poster đến bộ nhận diện
                  thương hiệu
                  đầy sáng tạo, phù hợp với mọi loại hình doanh nghiệp và dự án cá nhân.
                </p>
                <p class="fst-italic">
                  Tạo ra các sản phẩm trực quan đẹp mắt, truyền tải thông điệp hiệu quả và thu hút khách hàng.
                </p>
                <ul>
                  <li><i class="bi bi-check-circle-fill"></i> Thiết kế logo, bộ nhận diện thương hiệu chuyên nghiệp.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Tạo poster, banner, infographic thu hút và sáng tạo.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Dịch vụ chỉnh sửa ảnh và tạo nội dung hình ảnh số.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Hỗ trợ tư vấn màu sắc, bố cục và phong cách thiết kế phù
                    hợp.</li>
                </ul>
              </div>
              <div class="col-lg-4 order-1 order-lg-2 text-center">
                <img src="assets/img/features-2.svg" alt="Thiết kế Đồ họa" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content 2 -->

          <div class="tab-pane fade" id="features-tab-3">
            <div class="row gy-4">
              <div class="col-lg-8 order-2 order-lg-1">
                <h3>UI/UX Design</h3>
                <p>
                  Chúng tôi thiết kế giao diện người dùng (UI) và trải nghiệm người dùng (UX) chuyên nghiệp, giúp sản phẩm
                  của bạn
                  trực quan, dễ sử dụng và hấp dẫn người dùng.
                </p>
                <ul>
                  <li><i class="bi bi-check-circle-fill"></i> Thiết kế giao diện website và ứng dụng di động thân thiện
                    với người dùng.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Tối ưu trải nghiệm người dùng, cải thiện tương tác và giữ
                    chân khách hàng.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Tư vấn và xây dựng prototype, wireframe chuẩn UX.</li>
                </ul>
                <p class="fst-italic">
                  Mang đến trải nghiệm trực quan, dễ sử dụng và hiệu quả cho mọi loại sản phẩm số.
                </p>
              </div>
              <div class="col-lg-4 order-1 order-lg-2 text-center">
                <img src="assets/img/features-3.svg" alt="UI/UX Design" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content 3 -->

          <div class="tab-pane fade" id="features-tab-4">
            <div class="row gy-4">
              <div class="col-lg-8 order-2 order-lg-1">
                <h3>Video & Animation</h3>
                <p>
                  Chúng tôi sản xuất video chuyên nghiệp và các hiệu ứng hoạt hình (animation), giúp truyền tải thông điệp
                  thương hiệu một cách sinh động và thu hút người xem.
                </p>
                <p class="fst-italic">
                  Từ ý tưởng đến hậu kỳ, mọi sản phẩm đều được tối ưu để tạo trải nghiệm hình ảnh ấn tượng.
                </p>
                <ul>
                  <li><i class="bi bi-check-circle-fill"></i> Quay phim, dựng video quảng cáo, giới thiệu sản phẩm.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Animation 2D, 3D, motion graphics sinh động.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Biên tập hậu kỳ, chỉnh màu và hiệu ứng chuyên nghiệp.</li>
                </ul>
              </div>
              <div class="col-lg-4 order-1 order-lg-2 text-center">
                <img src="assets/img/features-4.svg" alt="Video & Animation" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content 4 -->

          <div class="tab-pane fade" id="features-tab-5">
            <div class="row gy-4">
              <div class="col-lg-8 order-2 order-lg-1">
                <h3>Marketing & SEO</h3>
                <p>
                  Chúng tôi cung cấp giải pháp marketing toàn diện, từ SEO, quảng cáo Google Ads, Facebook Ads đến chiến
                  lược nội dung, giúp thương hiệu của bạn tiếp cận đúng khách hàng.
                </p>
                <p class="fst-italic">
                  Tối ưu hóa công cụ tìm kiếm và chiến dịch quảng cáo hiệu quả giúp tăng traffic, lead và doanh thu.
                </p>
                <ul>
                  <li><i class="bi bi-check-circle-fill"></i> SEO On-page & Off-page, nghiên cứu từ khóa.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Quảng cáo Google, Facebook, Instagram hiệu quả.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Chiến lược nội dung và quản lý mạng xã hội chuyên nghiệp.
                  </li>
                </ul>
              </div>
              <div class="col-lg-4 order-1 order-lg-2 text-center">
                <img src="assets/img/features-5.svg" alt="Marketing & SEO" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content 5 -->

          <div class="tab-pane fade" id="features-tab-6">
            <div class="row gy-4">
              <div class="col-lg-8 order-2 order-lg-1">
                <h3>UI/UX Design</h3>
                <p>
                  Chúng tôi thiết kế giao diện người dùng (UI) và trải nghiệm người dùng (UX) chuyên nghiệp, đảm bảo sản
                  phẩm trực quan, thân thiện và dễ sử dụng.
                </p>
                <p class="fst-italic">
                  Từ nghiên cứu người dùng, wireframe, prototyping đến thiết kế hoàn chỉnh, mọi chi tiết đều được tối ưu
                  để nâng cao trải nghiệm và tăng tỷ lệ chuyển đổi.
                </p>
                <ul>
                  <li><i class="bi bi-check-circle-fill"></i> Thiết kế wireframe, mockup và prototype cho web và mobile
                    app.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Nghiên cứu trải nghiệm người dùng và tối ưu hành trình khách
                    hàng.</li>
                  <li><i class="bi bi-check-circle-fill"></i> Thiết kế giao diện hiện đại, responsive, thân thiện với
                    người dùng.</li>
                </ul>
              </div>
              <div class="col-lg-4 order-1 order-lg-2 text-center">
                <img src="assets/img/features-6.svg" alt="UI/UX Design" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content 6 -->

        </div>

      </div>

    </section><!-- /Features Section -->

    <!-- Recent Posts Section -->
    <section id="recent-posts" class="recent-posts section">

      <!-- Tiêu đề phần -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Công việc phù hợp với bạn</h2>
        <p>Những việc làm mới nhất phù hợp với hồ sơ và sở thích của bạn</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <!-- Việc làm 1 -->
          <div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <article>

              <div class="post-img">
                <img src="assets/img/blog/blog-1.jpg" alt="" class="img-fluid">
              </div>

              <p class="post-category">Toàn thời gian</p>

              <h2 class="title">
                <a href="job-details.html">Lập trình Frontend tại TechCorp</a>
              </h2>

              <div class="d-flex align-items-center">
                <img src="assets/img/blog/blog-author.jpg" alt="" class="img-fluid post-author-img flex-shrink-0">
                <div class="post-meta">
                  <p class="post-author">TechCorp</p>
                  <p class="post-date">
                    <time datetime="2025-09-17">17/09/2025</time>
                  </p>
                </div>
              </div>

            </article>
          </div>

          <!-- Việc làm 2 -->
          <div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <article>

              <div class="post-img">
                <img src="assets/img/blog/blog-2.jpg" alt="" class="img-fluid">
              </div>

              <p class="post-category">Bán thời gian</p>

              <h2 class="title">
                <a href="job-details.html">Thiết kế UI/UX tại CreativeStudio</a>
              </h2>

              <div class="d-flex align-items-center">
                <img src="assets/img/blog/blog-author-2.jpg" alt="" class="img-fluid post-author-img flex-shrink-0">
                <div class="post-meta">
                  <p class="post-author">CreativeStudio</p>
                  <p class="post-date">
                    <time datetime="2025-09-16">16/09/2025</time>
                  </p>
                </div>
              </div>

            </article>
          </div>

          <!-- Việc làm 3 -->
          <div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <article>

              <div class="post-img">
                <img src="assets/img/blog/blog-3.jpg" alt="" class="img-fluid">
              </div>

              <p class="post-category">Thực tập</p>

              <h2 class="title">
                <a href="job-details.html">Nhân viên Marketing tại BrightMedia</a>
              </h2>

              <div class="d-flex align-items-center">
                <img src="assets/img/blog/blog-author-3.jpg" alt="" class="img-fluid post-author-img flex-shrink-0">
                <div class="post-meta">
                  <p class="post-author">BrightMedia</p>
                  <p class="post-date">
                    <time datetime="2025-09-15">15/09/2025</time>
                  </p>
                </div>
              </div>

            </article>
          </div>

        </div><!-- End danh sách việc làm -->

      </div>

    </section><!-- /Recent Posts Section -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Nhóm freelancer</h2>
        <p>Khám phá các loại freelancer theo kỹ năng để tìm việc hoặc hợp tác phù hợp với dự án của bạn.</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-5">

          <!-- Freelancer Designer -->
          <div class="col-xl-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="service-item">
              <div class="img">
                <img src="assets/img/services-1.jpg" class="img-fluid" alt="Designer">
              </div>
              <div class="details position-relative">
                <div class="icon">
                  <i class="bi bi-paint-bucket"></i>
                </div>
                <h3>Designer</h3>
                <p>Thiết kế đồ họa, UI/UX, và các sản phẩm sáng tạo cho website, app và thương hiệu.</p>
              </div>
            </div>
          </div>

          <!-- Freelancer Developer -->
          <div class="col-xl-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
            <div class="service-item">
              <div class="img">
                <img src="assets/img/services-2.jpg" class="img-fluid" alt="Developer">
              </div>
              <div class="details position-relative">
                <div class="icon">
                  <i class="bi bi-code-slash"></i>
                </div>
                <h3>Developer</h3>
                <p>Lập trình web, mobile app, backend, frontend và các dự án công nghệ khác.</p>
              </div>
            </div>
          </div>

          <!-- Freelancer Content Writer -->
          <div class="col-xl-4 col-md-6" data-aos="zoom-in" data-aos-delay="400">
            <div class="service-item">
              <div class="img">
                <img src="assets/img/services-3.jpg" class="img-fluid" alt="Content Writer">
              </div>
              <div class="details position-relative">
                <div class="icon">
                  <i class="bi bi-journal-text"></i>
                </div>
                <h3>Content Writer</h3>
                <p>Viết bài, SEO content, blog, quảng cáo và các nội dung truyền thông cho doanh nghiệp.</p>
              </div>
            </div>
          </div>

          <!-- Freelancer Marketing -->
          <div class="col-xl-4 col-md-6" data-aos="zoom-in" data-aos-delay="500">
            <div class="service-item">
              <div class="img">
                <img src="assets/img/services-4.jpg" class="img-fluid" alt="Marketing">
              </div>
              <div class="details position-relative">
                <div class="icon">
                  <i class="bi bi-megaphone"></i>
                </div>
                <h3>Marketing</h3>
                <p>Quản lý chiến dịch quảng cáo, social media, branding và digital marketing cho dự án.</p>
              </div>
            </div>
          </div>

          <!-- Freelancer Video & Animation -->
          <div class="col-xl-4 col-md-6" data-aos="zoom-in" data-aos-delay="600">
            <div class="service-item">
              <div class="img">
                <img src="assets/img/services-5.jpg" class="img-fluid" alt="Video & Animation">
              </div>
              <div class="details position-relative">
                <div class="icon">
                  <i class="bi bi-camera-reels"></i>
                </div>
                <h3>Video & Animation</h3>
                <p>Sản xuất video, hoạt hình, motion graphics và các nội dung đa phương tiện.</p>
              </div>
            </div>
          </div>

          <!-- Freelancer Consultant -->
          <div class="col-xl-4 col-md-6" data-aos="zoom-in" data-aos-delay="700">
            <div class="service-item">
              <div class="img">
                <img src="assets/img/services-6.jpg" class="img-fluid" alt="Consultant">
              </div>
              <div class="details position-relative">
                <div class="icon">
                  <i class="bi bi-people"></i>
                </div>
                <h3>Consultant</h3>
                <p>Tư vấn chiến lược, dự án và giải pháp cho doanh nghiệp hoặc cá nhân.</p>
              </div>
            </div>
          </div>

        </div>
      </div>

    </section><!-- /Services Section -->

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials section dark-background">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Freelancer Nổi Bật</h2>
        <p>Những freelancer đạt nhiều thành tích và đánh giá cao từ khách hàng và dự án.</p>
      </div><!-- End Section Title -->

      <img src="assets/img/testimonials-bg.jpg" class="testimonials-bg" alt="">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="swiper init-swiper">
          <script type="application/json" class="swiper-config">
                              {
                                "loop": true,
                                "speed": 600,
                                "autoplay": {
                                  "delay": 5000
                                },
                                "slidesPerView": "auto",
                                "pagination": {
                                  "el": ".swiper-pagination",
                                  "type": "bullets",
                                  "clickable": true
                                }
                              }
                            </script>

          <div class="swiper-wrapper">

            <!-- Freelancer 1 -->
            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/testimonials-1.jpg" class="testimonial-img" alt="Hồ Gia Huy">
                <h3>Hồ Gia Huy</h3>
                <h4>Developer</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Chuyên lập trình web và ứng dụng mobile, hoàn thành nhiều dự án lớn với hiệu quả cao và đúng
                    hạn.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div>

            <!-- Freelancer 2 -->
            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/testimonials-2.jpg" class="testimonial-img" alt="Trần Thị B">
                <h3>Trần Thị B</h3>
                <h4>Designer</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Hoàn thành hơn 50 dự án thiết kế, nhận đánh giá 5 sao từ khách hàng, chuyên nghiệp và sáng
                    tạo.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div>

            <!-- Freelancer 3 -->
            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/testimonials-3.jpg" class="testimonial-img" alt="Lê Văn C">
                <h3>Lê Văn C</h3>
                <h4>Content Writer</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Viết nội dung chất lượng cao, SEO tốt, nhận đánh giá tích cực từ nhiều khách hàng và dự án.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div>

            <!-- Freelancer 4 -->
            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/testimonials-4.jpg" class="testimonial-img" alt="Phạm Thị D">
                <h3>Phạm Thị D</h3>
                <h4>Marketing</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Quản lý chiến dịch marketing thành công, giúp nhiều doanh nghiệp tăng hiệu quả kinh doanh.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div>

            <!-- Freelancer 5 -->
            <div class="swiper-slide">
              <div class="testimonial-item">
                <img src="assets/img/testimonials/testimonials-5.jpg" class="testimonial-img" alt="Ngô Văn E">
                <h3>Ngô Văn E</h3>
                <h4>Video & Animation</h4>
                <div class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Thực hiện video và hoạt hình sáng tạo, được nhiều khách hàng đánh giá cao về chất lượng và tiến
                    độ.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
              </div>
            </div>

          </div>
          <div class="swiper-pagination"></div>
        </div>

      </div>

    </section><!-- /Testimonials Section -->

    <!-- Pricing Section -->
    <section id="pricing" class="pricing section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Gói Dịch Vụ</h2>
        <p>Chọn gói phù hợp để tìm việc hoặc đăng dự án trên JobLink.</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <!-- Gói Miễn Phí -->
          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="200">
            <div class="pricing-item">

              <div class="pricing-header">
                <h3>Gói Miễn Phí</h3>
                <h4><sup>₫</sup>0<span> / tháng</span></h4>
              </div>

              <ul>
                <li><i class="bi bi-dot"></i> <span>Đăng ký hồ sơ cá nhân</span></li>
                <li><i class="bi bi-dot"></i> <span>Tìm kiếm việc làm cơ bản</span></li>
                <li><i class="bi bi-dot"></i> <span>Ứng tuyển tối đa 5 dự án/tháng</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Truy cập công cụ quản lý khách hàng</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Hỗ trợ ưu tiên từ JobLink</span></li>
              </ul>

              <div class="text-center mt-auto">
                <a href="#" class="buy-btn">Đăng ký</a>
              </div>

            </div>
          </div>

          <!-- Gói Freelancer -->
          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="400">
            <div class="pricing-item featured">

              <div class="pricing-header">
                <h3>Gói Freelancer</h3>
                <h4><sup>₫</sup>199,000<span> / tháng</span></h4>
              </div>

              <ul>
                <li><i class="bi bi-dot"></i>
                  <span>Đăng hồ sơ chuyên nghiệp với portfolio</span>
                </li>
                <li><i class="bi bi-dot"></i> <span>Tìm kiếm và ứng tuyển không giới hạn</span></li>
                <li><i class="bi bi-dot"></i> <span>Nhận thông báo dự án mới hàng ngày</span></li>
                <li><i class="bi bi-dot"></i> <span>Tham gia cộng đồng freelancer</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Hỗ trợ ưu tiên từ JobLink</span></li>
              </ul>

              <div class="text-center mt-auto">
                <a href="#" class="buy-btn">Đăng ký</a>
              </div>

            </div>
          </div>

          <!-- Gói Doanh Nghiệp -->
          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="600">
            <div class="pricing-item">

              <div class="pricing-header">
                <h3>Gói Doanh Nghiệp</h3>
                <h4><sup>₫</sup>499,000<span> / tháng</span></h4>
              </div>

              <ul>
                <li><i class="bi bi-dot"></i> <span>Đăng dự án không giới hạn</span></li>
                <li><i class="bi bi-dot"></i> <span>Truy cập hồ sơ freelancer chi tiết</span></li>
                <li><i class="bi bi-dot"></i> <span>Quản lý ứng tuyển và dự án dễ dàng</span></li>
                <li><i class="bi bi-dot"></i> <span>Hỗ trợ ưu tiên từ JobLink</span></li>
                <li><i class="bi bi-dot"></i> <span>Báo cáo phân tích hiệu quả dự án</span></li>
              </ul>

              <div class="text-center mt-auto">
                <a href="#" class="buy-btn">Đăng ký</a>
              </div>

            </div>
          </div>

        </div>

      </div>

    </section><!-- /Pricing Section -->

    <!-- Faq Section -->
    <section id="faq" class="faq section">

      <div class="container-fluid">

        <div class="row gy-4">

          <!-- Nội dung FAQ -->
          <div class="col-lg-7 d-flex flex-column justify-content-center order-2 order-lg-1">

            <div class="content px-xl-5" data-aos="fade-up" data-aos-delay="100">
              <h3><span></span><strong>Câu Hỏi Thường Gặp</strong></h3>
              <p>
                Đây là các câu hỏi phổ biến từ freelancer và doanh nghiệp khi sử dụng JobLink.
                Nếu bạn có thắc mắc khác, hãy liên hệ với chúng tôi để được hỗ trợ.
              </p>
            </div>

            <div class="faq-container px-xl-5" data-aos="fade-up" data-aos-delay="200">

              <div class="faq-item faq-active">
                <i class="faq-icon bi bi-question-circle"></i>
                <h3>Làm thế nào để đăng ký tài khoản freelancer?</h3>
                <div class="faq-content">
                  <p>Bạn chỉ cần nhấn nút "Đăng ký", điền thông tin cơ bản và xác thực email. Sau đó có thể tạo hồ sơ cá
                    nhân và portfolio để bắt đầu tìm việc.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

              <div class="faq-item">
                <i class="faq-icon bi bi-question-circle"></i>
                <h3>Làm sao để doanh nghiệp đăng dự án trên JobLink?</h3>
                <div class="faq-content">
                  <p>Doanh nghiệp đăng ký tài khoản, chọn gói dịch vụ phù hợp và tạo dự án với mô tả, kỹ năng yêu cầu. Sau
                    đó freelancer có thể ứng tuyển trực tiếp.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

              <div class="faq-item">
                <i class="faq-icon bi bi-question-circle"></i>
                <h3>JobLink có hỗ trợ thanh toán và bảo mật không?</h3>
                <div class="faq-content">
                  <p>Chúng tôi tích hợp các phương thức thanh toán an toàn, bảo vệ thông tin cá nhân và giao dịch của cả
                    freelancer và doanh nghiệp. Bạn có thể yên tâm sử dụng nền tảng để trao đổi công việc.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

            </div>

          </div>

          <!-- Hình minh họa FAQ -->
          <div class="col-lg-5 order-1 order-lg-2">
            <img src="assets/img/faq.jpg" class="img-fluid" alt="FAQ JobLink" data-aos="zoom-in" data-aos-delay="100">
          </div>

        </div>

      </div>

    </section><!-- /Faq Section -->

  </main>
  
@endsection