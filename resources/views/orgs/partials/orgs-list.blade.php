<div class="row gy-5">

    @foreach ($orgs as $org)
        <div class="col-xl-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="service-item">
                <div class="img">
                    <img src="assets/img/services-2.jpg" class="img-fluid" alt="">
                </div>
                <div class="details position-relative text-center">
                    <div class="icon mb-2">
                        <img src="{{ optional($org->account)->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                            alt="{{ optional($org->account)->name ?? 'Người đăng' }}" class="rounded-circle mx-auto d-block"
                            width="80" height="80">
                    </div>
                    <h3 class="text-truncate d-block" style="max-width: 100%; cursor: pointer;" data-bs-toggle="tooltip"
                        data-bs-placement="top" title="{{ $org->name }}">
                        {{ $org->name }}
                    </h3>

                    <p class="text-truncate d-block" style="max-width: 100%;">
                        {{ $org->description }}
                    </p>

                </div>
            </div>
        </div><!-- End Service Item -->

    @endforeach

</div>