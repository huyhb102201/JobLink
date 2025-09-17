@extends('layouts.app')
@section('title', 'JobLink - Liên hệ')
@section('content')
<main class="main">
    <!-- Page Title -->
    <div class="page-title">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Liên hệ</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="current">Liên hệ</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

        <div class="mb-5 text-center">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.0239035802674!2d105.96172329999999!3d10.2498396!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310a82ce95555555%3A0x451cc8d95d6039f8!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBTxrAgcGjhuqFtIEvhu7kgdGh14bqtdCBWxKluaCBMb25n!5e1!3m2!1svi!2s!4v1758094910618!5m2!1svi!2s"
                width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div><!-- End Google Maps -->

        <div class="container" data-aos="fade">

            <div class="row gy-5 gx-lg-5">

                <!-- Contact Info -->
                <div class="col-lg-4">
                    <div class="info">
                        <h3>Liên hệ với chúng tôi</h3>
                        <p>Hãy liên hệ để được hỗ trợ, tư vấn hoặc giải đáp thắc mắc của bạn.</p>

                        <div class="info-item d-flex">
                            <i class="bi bi-geo-alt flex-shrink-0"></i>
                            <div>
                                <h4>Địa chỉ:</h4>
                                <p> Số 73, Nguyễn Huệ, Phường Long Châu, Tp. Vĩnh Long</p>
                            </div>
                        </div>

                        <div class="info-item d-flex">
                            <i class="bi bi-envelope flex-shrink-0"></i>
                            <div>
                                <h4>Email:</h4>
                                <p>22004046@st.vlute.edu.vn</p>
                            </div>
                        </div>

                        <div class="info-item d-flex">
                            <i class="bi bi-phone flex-shrink-0"></i>
                            <div>
                                <h4>Điện thoại:</h4>
                                <p>+84 963 887 651</p>
                            </div>
                        </div>
                    </div>
                </div><!-- End Contact Info -->

                <!-- Contact Form -->
                <div class="col-lg-8">
                    <form action="#" method="post" class="php-email-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <input type="text" name="name" class="form-control" id="name" placeholder="Họ và tên" required>
                            </div>
                            <div class="col-md-6 form-group mt-3 mt-md-0">
                                <input type="email" name="email" class="form-control" id="email" placeholder="Email" required>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <input type="text" name="subject" class="form-control" id="subject" placeholder="Tiêu đề" required>
                        </div>
                        <div class="form-group mt-3">
                            <textarea name="message" class="form-control" rows="6" placeholder="Nội dung" required></textarea>
                        </div>
                        <div class="my-3">
                            <div class="loading">Đang gửi...</div>
                            <div class="error-message"></div>
                            <div class="sent-message">Tin nhắn của bạn đã được gửi. Cảm ơn!</div>
                        </div>
                        <div class="text-center"><button type="submit" class="btn btn-primary">Gửi tin nhắn</button></div>
                    </form>
                </div><!-- End Contact Form -->

            </div>

        </div>

    </section><!-- /Contact Section -->

</main>
@endsection
