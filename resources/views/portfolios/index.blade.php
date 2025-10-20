@extends('layouts.app')

@section('title', $profile->fullname . ' - Trang cá nhân')

@section('content')
    <div class="container my-4">

        <div class="position-relative mb-5">
            <!-- Cover -->
            <div class="rounded-4" style="height: 180px; background: linear-gradient(135deg, #4e73df, #1cc88a);"></div>

            <!-- Avatar -->
            <div class="position-absolute top-100 start-50 translate-middle" style="margin-top: -60px;">
                <div class="position-relative d-inline-block" id="avatarWrapper">
                    {{-- Ảnh đại diện --}}
                    <img id="avatarImg" src="{{ $account->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                         class="rounded-circle border border-4 border-white shadow-lg"
                         style="width:160px; height:160px; object-fit:cover;">

                    {{-- Overlay spinner --}}
                    <div id="avatarSpinner" class="avatar-spinner d-none">
                        <div class="spinner-border text-light" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>

                    {{-- Tick xác minh --}}
                    @if($account->account_type_id == 2)
                        <span
                            class="position-absolute bottom-0 end-0 bg-warning border border-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width:36px; height:36px; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
                            <i class="bi bi-patch-check-fill text-white" style="font-size:20px;"></i>
                        </span>
                    @endif

                    {{-- Nút upload ảnh (chỉ hiện khi đúng chủ tài khoản) --}}
                    @if(Auth::check() && Auth::id() === $account->account_id)
                        <form action="{{ route('profile.avatar.upload') }}" method="POST" enctype="multipart/form-data"
                              id="avatarForm">
                            @csrf
                            <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none">
                        </form>

                        <span id="avatarBtn"
                              class="position-absolute bottom-0 end-0 bg-secondary border border-white rounded-circle d-flex align-items-center justify-content-center"
                              style="width:42px; height:42px; box-shadow:0 2px 6px rgba(0,0,0,0.2); cursor:pointer;">
                            <i class="bi bi-camera-fill text-white fs-6"></i>
                        </span>
                    @endif
                </div>
            </div>

        </div>
        <style>
            #avatarWrapper { width: 160px; height: 160px; }
            .avatar-spinner {
                position: absolute; inset: 0; background: rgba(0, 0, 0, 0.25);
                display: flex; align-items: center; justify-content: center;
                border-radius: 50%; transition: opacity .2s;
            }
            .is-uploading #avatarBtn { pointer-events: none; opacity: 0.6; }
        </style>

        <!-- Name + Stats -->
        <div class="text-center mt-5 mb-4">
            <h2 class="fw-bold">{{ $profile->fullname }}</h2>
            <p class="text-muted mb-1">@ {{ $profile->username }}</p>

            <div class="d-inline-flex align-items-center gap-2">
                <p class="mb-0">
                    <i class="bi bi-geo-alt"></i>
                    <span id="locationText">{{ $profile->location ?? 'Chưa cập nhật địa chỉ' }}</span>
                </p>

                @if(Auth::check() && Auth::id() === $account->account_id)
                    <button type="button" class="btn btn-link text-muted p-0 border-0" data-bs-toggle="modal"
                            data-bs-target="#editLocationModal" title="Chỉnh sửa địa chỉ" style="box-shadow:none;">
                        <i class="bi bi-pencil fs-15"></i>
                    </button>
                @endif
            </div>

            {{-- BADGE TRÊN ĐẦU: thêm ID để cập nhật realtime --}}
            <div id="topBadges" class="mt-2 d-flex flex-wrap justify-content-center gap-2">
                <span class="badge rounded-pill bg-success-subtle text-success border">
                    <i class="bi bi-patch-check-fill me-1"></i> Top Rated
                </span>

                @if($reviewCount > 0)
                    <span id="topRatingBadge" class="badge rounded-pill bg-primary-subtle text-primary border">
                      <i class="bi bi-star-fill me-1"></i>
                      <span id="topAvgBadgeNumber">{{ number_format($avgRating, 1) }}</span>/5
                      (<span id="topReviewCount">{{ $reviewCount }}</span> đánh giá)
                    </span>
                @else
                    <span id="topNoRatingBadge" class="badge rounded-pill bg-light text-muted border">
                      <i class="bi bi-star me-1"></i> Chưa có đánh giá
                    </span>
                @endif

                <span class="badge rounded-pill bg-info-subtle text-info border">
                    <i class="bi bi-clipboard-check me-1"></i>
                    {{ $stats['total_jobs'] > 0 ? round(($stats['completed_jobs'] / $stats['total_jobs']) * 100) : 0 }}% hoàn thành
                </span>
                <span class="badge rounded-pill bg-warning-subtle text-warning border">
                    <i class="bi bi-lightning-charge me-1"></i> Phản hồi nhanh
                </span>
            </div>

            {{-- Modal chỉnh sửa Location --}}
            @if(Auth::check() && Auth::id() === $account->account_id)
                <div class="modal fade" id="editLocationModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <form id="locationAjaxForm" class="modal-content" method="POST"
                              action="{{ route('portfolios.location.update') }}">
                            @csrf @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title">Chỉnh sửa địa chỉ</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Tỉnh / Thành phố</label>
                                    <select id="c_province" class="form-select" style="width:100%">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phường / Xã</label>
                                    <select id="c_ward" class="form-select" style="width:100%" disabled>
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Địa chỉ lưu</label>
                                    <input type="text" name="location" id="locationInput" class="form-control"
                                           value="{{ old('location', $profile->location) }}" maxlength="150"
                                           placeholder="VD: Phường Bến Nghé, TP.HCM">
                                    <div class="form-text">Tối đa 150 ký tự. Sẽ tự ghép theo lựa chọn phía trên.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary">Lưu</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="row justify-content-center mt-4 g-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded shadow-sm p-3 h-100">
                        <h4 class="fw-bold">{{ $stats['total_jobs'] }}</h4>
                        <small class="text-muted">Tổng công việc</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded shadow-sm p-3 h-100">
                        <h4 class="fw-bold text-success">{{ $stats['completed_jobs'] }}</h4>
                        <small class="text-muted">Hoàn thành</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded shadow-sm p-3 h-100">
                        <h4 class="fw-bold text-warning">{{ $stats['ongoing_jobs'] }}</h4>
                        <small class="text-muted">Đang làm</small>
                    </div>
                </div>
            </div>
        </div>
        <br>

        <!-- Layout 2 cột -->
        <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-lg-4">
                <!-- Liên hệ ngay -->
                @if(Auth::check() && Auth::id() != $account->account_id)
                    <div class="d-grid mb-4">
                        <a href="{{ url('/portfolios/' . $profile->username . '/chat') }}" class="btn btn-contact">
                            <span class="icon"><i class="bi bi-chat-dots-fill"></i></span>
                            <span class="text">Liên hệ ngay</span>
                        </a>
                    </div>
                @endif

                <style>
                    .btn-contact{
                        position:relative; display:inline-flex; align-items:center; justify-content:center; gap:8px;
                        background:linear-gradient(135deg,#4e73df,#1cc88a); color:#fff; font-weight:600; border:0;
                        border-radius:10px; padding:10px 20px; transition:all .3s ease;
                        box-shadow:0 4px 12px rgba(76,175,80,.25); overflow:hidden;
                    }
                    .btn-contact:hover{ background:linear-gradient(135deg,#1cc88a,#4e73df); transform:translateY(-2px);
                        box-shadow:0 6px 18px rgba(28,200,138,.35); color:#fff; text-decoration:none;}
                    .btn-contact .icon{ background:rgba(255,255,255,.15); border-radius:50%; width:36px; height:36px;
                        display:flex; align-items:center; justify-content:center; font-size:1.2rem; transition:background .3s }
                    .btn-contact:hover .icon{ background:rgba(255,255,255,.25) }
                    .btn-contact .text{ font-size:1rem; letter-spacing:.3px }
                </style>

                <!-- Kỹ năng -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill me-2"></i>Kỹ năng nổi bật</h5>
                            <span class="text-muted small"><i class="bi bi-award me-1"></i>Đã xác thực kỹ năng</span>
                        </div>

                        <div class="row g-3" id="skill-list">
                            @php
                                $__skills = isset($skills) && $skills->count() ? $skills->toArray() : [];
                            @endphp
                          @forelse($__skills as $index => $s)
    <div class="col-12 skill-item-wrapper {{ $index >= 3 ? 'd-none extra-skill' : '' }}">
      <div class="skill-item surface p-3 rounded-3 border">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-3">
            <div class="skill-dot"></div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold">{{ $s['name'] }}</span>
                <span class="badge rounded-pill bg-light text-muted border">{{ $s['level'] }}</span>
              </div>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="skill-stars" data-rating="{{ $s['rating'] }}"></span>
                <small class="text-muted">{{ number_format($s['rating'], 1) }}/5 • {{ $s['endorse'] }} xác nhận</small>
              </div>
            </div>
          </div>
          <div class="text-end">
            <div class="progress skill-progress" style="width:160px; height:6px;">
              @php $pct = min(100, max(0, ($s['rating'] / 5) * 100)); @endphp
              <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @empty
    <div class="col-12">
      <div class="alert alert-light border d-flex align-items-center justify-content-center text-muted py-3 mb-0 rounded-3">
        <i class="bi bi-info-circle me-2"></i>
        Người này chưa có kỹ năng nào được thêm.
      </div>
    </div>
  @endforelse

                            @if(count($__skills) > 3)
                                <div class="text-center mt-3">
                                    <a href="javascript:void(0)" id="toggle-skills" class="text-decoration-none fw-bold text-primary">
                                        Xem thêm <i class="bi bi-chevron-down"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <style>
                    .surface{ background:#fff }
                    .skill-item{ background:var(--bs-body-bg) }
                    .skill-dot{ width:12px; height:12px; border-radius:50%;
                        background:linear-gradient(135deg,#4e73df,#1cc88a); box-shadow:0 0 0 3px rgba(76,175,80,.15);}
                    .skill-stars i{ font-size:1rem; line-height:1 }
                    .skill-stars i.bi-star-fill,.skill-stars i.bi-star-half{ color:#f1c40f }
                    .skill-stars i.bi-star{ color:#e0e0e0 }
                    .skill-progress .progress-bar{ background:linear-gradient(90deg,#4e73df,#1cc88a) }
                    .skill-item-wrapper{ transition:all .3s ease }
                </style>

                <!-- Liên hệ & MXH -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-share me-2"></i>Liên hệ & Mạng xã hội</h5>
                        <div class="mt-2 d-flex align-items-center">
                            <a href="{{ $profile->github ?? '#' }}" class="btn btn-outline-dark btn-sm me-1" target="_blank">
                                <i class="bi bi-github"></i>
                            </a>
                            <a href="{{ $profile->facebook ?? '#' }}" class="btn btn-outline-primary btn-sm me-1" target="_blank">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to={{ $account->email }}"
                               class="btn btn-outline-danger btn-sm" target="_blank">
                                <i class="bi bi-envelope-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải -->
            <div class="col-lg-8">
                <!-- GIỚI THIỆU (TRUNG TÂM) -->
                <div class="row mb-4">
                    <div class="">
                        <div class="card border-0 shadow-sm intro-card position-relative">
                            @if(Auth::check() && Auth::id() === $account->account_id)
                                <button type="button" class="btn btn-link p-0 border-0 edit-intro-btn" data-bs-toggle="modal"
                                        data-bs-target="#editAboutModal" title="Chỉnh sửa giới thiệu">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            @endif
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="intro-icon d-none d-sm-flex align-items-center justify-content-center">
                                        <i class="bi bi-person-lines-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-2">Giới thiệu</h5>

                                        @php $description = $profile->description ?? null; @endphp
                                        @if($description)
                                            <div class="intro-content mb-0 text-secondary">{!! $description !!}</div>
                                        @else
                                            <div class="alert alert-light border d-flex align-items-center mb-0">
                                                <i class="bi bi-info-circle me-2"></i><span>Chưa cập nhật giới thiệu.</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL: Chỉnh sửa giới thiệu --}}
                <div class="modal fade" id="editAboutModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <form id="aboutForm" class="modal-content" method="POST" action="{{ route('profile.about.update') }}">
                            @csrf @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title">Chỉnh sửa giới thiệu</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                                                    {{-- Nút mở Offcanvas AI --}}
                            <div class="modal-body">
                                <label class="form-label fw-semibold">Nội dung chi tiết</label>
                                <button type="button" 
        class="btn text-white fw-semibold shadow-sm border-0 px-4 py-2 rounded-3" 
        style="background: linear-gradient(135deg, #6f42c1, #0d6efd); transition: all .3s ease; margin-left: 930px;"
        data-bs-toggle="offcanvas" 
        data-bs-target="#aiAboutCanvas">
  <i class="bi bi-stars me-2"></i> Tạo bằng AI
</button>

<style>
button[data-bs-target="#aiAboutCanvas"]:hover {
  background: linear-gradient(135deg, #0d6efd, #6f42c1);
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(13,110,253,0.3);
}
</style>

                                <textarea id="aboutEditor" name="description">{!! old('description', $profile->description ?? '') !!}</textarea>
                                <div class="form-text">Mẹo: Viết 2–4 câu về kinh nghiệm, thế mạnh, lĩnh vực nhận dự án. Có thể chèn tiêu đề, danh sách, hình ảnh.</div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary position-relative px-4 py-2 fw-semibold shadow-sm border-0 rounded-3" id="btnSaveAbout">
  <span class="spinner-border spinner-border-sm me-2 d-none" id="aboutSaveSpin"></span>
  <i class="bi bi-save me-1"></i> Lưu thay đổi
</button>

                            </div>
                        </form>
                    </div>
                </div>
                
<style>
  #btnSaveAbout {
    background: linear-gradient(135deg, #4e73df, #1cc88a);
    transition: all 0.3s ease;
  }
  #btnSaveAbout:hover {
    background: linear-gradient(135deg, #1cc88a, #4e73df);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
  }
  #aboutSaveSpin {
    vertical-align: -0.1em;
  }
</style>
                <style>
                    .edit-intro-btn{ position:absolute; top:10px; right:10px; color:#6c757d; transition:all .2s }
                    .edit-intro-btn:hover{ color:#0d6efd; transform:scale(1.1) }
                    .intro-card{ background:#fff }
                    .intro-icon{
                        width:48px; height:48px; border-radius:12px;
                        background:linear-gradient(135deg,#4e73df,#1cc88a); color:#fff; font-size:1.5rem; flex:0 0 48px;
                    }
                </style>

                <ul class="nav nav-tabs mb-3" id="profileTabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#jobs">Công việc</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#portfolio">Doanh nghiệp</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#reviews">Đánh giá</a></li>
                </ul>

                <div class="tab-content">
                    <!-- Jobs -->
                    <div class="tab-pane fade show active" id="jobs">
                        <div class="position-relative" id="job-list">
                            @forelse($jobs as $index => $job)
                                @php $applicantsCount = $job->applicants()->count(); @endphp

                                <div class="row g-0 mb-4 job-item {{ $index >= 3 ? 'd-none' : '' }}">
                                    <div class="col-auto d-flex flex-column align-items-center pe-3 position-relative">
                                        <div class="bg-{{ $job->status == 'completed' ? 'success' : 'warning' }} rounded-circle d-flex align-items-center justify-content-center border border-2 border-white"
                                             style="width:48px; height:48px;">
                                             <i class="bi bi-{{ $job->status == 'completed' ? 'check-circle-fill' : 'clock-fill' }} text-white fs-4"></i>
                                        </div>
                                        @if(!$loop->last)
                                            <div class="flex-grow-1 w-1 bg-secondary mt-2" style="min-height:60px; opacity:0.2;"></div>
                                        @endif
                                    </div>

                                    <div class="col">
                                        <div class="card shadow-sm border-0 rounded-3 p-3 ms-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="fw-bold mb-1">
                                                        <a href="{{ route('jobs.show', $job->job_id) }}" class="text-decoration-none text-dark">
                                                            {{ $job->title }}
                                                        </a>
                                                    </h6>
                                                    <p class="text-muted small mb-2">{{ Str::limit($job->description, 120) }}</p>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <span class="badge bg-{{ $job->status == 'completed' ? 'success' : 'warning' }} rounded-pill px-2 py-1" style="font-size:0.8rem;">
                                                            {{ $job->status == 'completed' ? 'Hoàn thành' : 'Đang làm' }}
                                                        </span>
                                                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-1" style="font-size:0.8rem;">
                                                            {{ $applicantsCount }} người ứng tuyển
                                                        </span>
                                                    </div>
                                                </div>
                                                <small class="text-muted">{{ $job->created_at->format('d/m/Y') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted text-center" style="font-size:1.1rem;">Chưa có công việc nào.</p>
                            @endforelse
                        </div>

                        @if($jobs->count() > 3)
                            <div class="text-center mt-3">
                                <a href="javascript:void(0)" id="toggle-jobs" class="text-decoration-none fw-bold">
                                    Xem thêm <i class="bi bi-chevron-down"></i>
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Doanh nghiệp -->
                    <div class="tab-pane fade" id="portfolio">
  <h5 class="fw-bold mb-3">Thông tin doanh nghiệp</h5>

  @if(($orgs ?? collect())->isEmpty())
    <p class="text-muted">Chưa có doanh nghiệp nào.</p>
  @else
    <div class="row g-4" id="org-grid">
      @foreach($orgs as $idx => $org)
        <div class="col-md-6 org-item {{ $idx >= 4 ? 'd-none extra-org' : '' }}">
          <div class="card shadow-sm border-0 h-100 position-relative overflow-hidden">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <h6 class="fw-bold mb-1 text-truncate" title="{{ $org->name }}">
                  {{ $org->name }}
                </h6>
                <div class="d-flex align-items-center gap-1">
                  <span class="badge rounded-pill
                               {{ strtoupper($org->verification_status) === 'VERIFIED' ? 'bg-success-subtle text-success border' :
                                  (strtoupper($org->verification_status) === 'PENDING' ? 'bg-warning-subtle text-warning border' :
                                   (strtoupper($org->verification_status) === 'REJECTED' ? 'bg-danger-subtle text-danger border' :
                                    'bg-secondary-subtle text-secondary border')) }}">
                    {{ $org->verification_status }}
                  </span>
                  <span class="badge rounded-pill {{ ($org->via_role ?? '') === 'OWNER' ? 'bg-primary' : 'bg-secondary' }}">
                    {{ ($org->via_role ?? '') === 'OWNER' ? 'Chủ sở hữu' : (($org->via_role ?? 'THÀNH VIÊN')) }}
                  </span>
                </div>
              </div>

              <div class="small text-muted mb-2">
                @if(!empty($org->member_status) && ($org->via_role ?? '') !== 'OWNER')
                  Thành viên: {{ $org->member_status }} •
                @endif
                Ghế: {{ $org->seats_limit ?? 0 }} • Thành viên: {{ $org->members_count ?? 0 }}
              </div>

              <div class="text-muted mb-2">
                <i class="bi bi-geo-alt me-1"></i>{{ $org->address ?? 'Địa chỉ: chưa cập nhật' }}
              </div>

              <div class="d-flex flex-wrap gap-2 small mb-2">
                @if($org->website)
                  <a href="{{ $org->website }}" target="_blank" class="text-decoration-none">
                    <i class="bi bi-globe2 me-1"></i>Website
                  </a>
                @endif
                @if($org->email)
                  <a href="mailto:{{ $org->email }}" class="text-decoration-none">
                    <i class="bi bi-envelope me-1"></i>{{ $org->email }}
                  </a>
                @endif
                @if($org->phone)
                  <span><i class="bi bi-telephone me-1"></i>{{ $org->phone }}</span>
                @endif
              </div>

              <p class="text-muted small mb-0">
                {{ \Illuminate\Support\Str::limit($org->description ?? 'Không có mô tả', 120) }}
              </p>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    @if(($orgs->count() ?? 0) > 4)
      <div class="text-center mt-3">
        <button id="toggle-orgs" class="btn btn-outline-primary btn-sm fw-semibold">
          Xem thêm <i class="bi bi-chevron-down"></i>
        </button>
      </div>
    @endif
  @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('toggle-orgs');
  const items = document.querySelectorAll('.extra-org');
  if(!btn) return;
  let open = false;
  btn.addEventListener('click', function(){
    items.forEach(el => el.classList.toggle('d-none'));
    open = !open;
    btn.innerHTML = open
      ? 'Thu gọn <i class="bi bi-chevron-up"></i>'
      : 'Xem thêm <i class="bi bi-chevron-down"></i>';
  });
});
</script>
@endpush


                    <!-- Reviews -->
                    <div class="tab-pane fade" id="reviews">
                        <h5 class="fw-bold mb-3">Đánh giá</h5>

                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3 text-center">
                                <h2 id="avgRatingNumber" class="mb-0 fw-bold text-primary">{{ number_format($avgRating ?? 0, 1) }}</h2>
                                <small class="text-muted">/ 5</small>
                            </div>
                            <div class="flex-grow-1">
                                <div id="avgStarsContainer" class="mb-1" style="color:#f1c40f;">
                                    @php $stars = floor($avgRating ?? 0); $half = (($avgRating ?? 0) - $stars) >= 0.5; @endphp
                                    @for($i=1;$i<=5;$i++)
                                        @if($i <= $stars)
                                            <i class="bi bi-star-fill"></i>
                                        @elseif($half && $i==$stars+1)
                                            <i class="bi bi-star-half"></i>
                                        @else
                                            <i class="bi bi-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <small id="reviewCountText" class="text-muted">{{ $reviewCount }} đánh giá</small>
                            </div>

                            @auth
                                @if(Auth::id() !== $account->account_id)
                                    <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#reviewCreateModal">
                                        <i class="bi bi-pencil-square me-1"></i> Đánh giá ngay
                                    </button>
                                @endif
                            @endauth
                        </div>

                        <div id="review-list">
                            @forelse($reviews as $index => $rev)
                                <div class="card shadow-sm border-0 mb-3 {{ $index >= 3 ? 'd-none extra-review' : '' }}">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <strong class="me-2">
                                                @if($rev->reviewerProfile)
                                                    <a href="{{ url('/portfolios/' . $rev->reviewerProfile->username) }}"
                                                       class="text-decoration-none text-dark fw-semibold hover-underline">
                                                        {{ $rev->reviewerProfile->fullname }}
                                                    </a>
                                                @else
                                                    Ẩn danh
                                                @endif
                                            </strong>
                                            <div style="color:#f1c40f;">
                                                @for($i=1;$i<=5;$i++)
                                                    <i class="bi {{ $i <= (int)$rev->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                        <p class="mb-0 text-secondary">{{ $rev->comment }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">Chưa có đánh giá nào.</p>
                            @endforelse
                        </div>

                        @if($reviews->count() > 3)
                            <div class="text-center mt-3">
                                <a href="javascript:void(0)" id="toggle-reviews" class="text-decoration-none fw-bold text-primary">
                                    Xem thêm <i class="bi bi-chevron-down"></i>
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Modal thêm đánh giá --}}
                    <meta name="csrf-token" content="{{ csrf_token() }}">

                    <div class="modal fade" id="reviewCreateModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <form id="reviewAjaxForm" class="modal-content" method="POST" action="{{ route('reviews.store') }}">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Đánh giá {{ $profile->fullname }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <input type="hidden" name="reviewee_id" value="{{ $profile->profile_id }}">
                                    <div class="mb-3 text-center">
                                        <label class="form-label d-block">Chọn số sao</label>
                                        <div id="reviewStars" class="fs-3" style="color:#f1c40f;">
                                            @for($i=1;$i<=5;$i++)
                                                <i class="bi bi-star" data-value="{{ $i }}"></i>
                                            @endfor
                                        </div>
                                        <input type="hidden" name="rating" id="ratingInput" value="5">
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Nhận xét</label>
                                        <textarea name="comment" class="form-control" rows="3" maxlength="2000" placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                                    </div>
                                    <div id="reviewError" class="text-danger small d-none"></div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                                    <button type="submit" class="btn btn-gradient d-flex align-items-center justify-content-center gap-2">
  <span class="spinner-border spinner-border-sm d-none" id="reviewSpin"></span>
  <i class="bi bi-send-fill"></i>
  <span>Gửi đánh giá</span>
</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if (session('ok'))
                        <div aria-live="polite" aria-atomic="true" class="position-relative">
                            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080">
                                <div id="okToast" class="toast align-items-center text-white bg-success border-0" role="alert"
                                     aria-live="assertive" aria-atomic="true">
                                    <div class="d-flex">
                                        <div class="toast-body">{{ session('ok') }}</div>
                                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                                data-bs-dismiss="toast" aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
<div class="offcanvas offcanvas-end" tabindex="-1" id="aiAboutCanvas">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title"><i class="bi bi-magic me-2"></i>Tạo giới thiệu bằng AI</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form id="aiAboutForm" class="vstack gap-3">
      <div>
        <label class="form-label">Giọng văn</label>
        <select name="tone" class="form-select">
          <option value="chuyên nghiệp, rõ ràng">Chuyên nghiệp</option>
          <option value="thân thiện, gần gũi">Thân thiện</option>
          <option value="ngắn gọn, súc tích">Ngắn gọn</option>
        </select>
      </div>
      <div>
        <label class="form-label">Số năm kinh nghiệm</label>
        <input type="number" min="0" max="50" name="years" class="form-control" placeholder="VD: 3">
      </div>
      <div>
        <label class="form-label">Vai trò chính</label>
        <input type="text" name="roles" class="form-control" placeholder="VD: Backend Developer, Fullstack...">
      </div>
      <div>
        <label class="form-label">Kỹ năng nổi bật (CSV)</label>
        <input type="text" name="skills" class="form-control" placeholder="VD: PHP,Laravel,MySQL,Vue">
      </div>
      <div>
        <label class="form-label">Thành tựu/điểm nhấn</label>
        <input type="text" name="highlights" class="form-control" placeholder="VD: Dẫn team 5 người, tối ưu hiệu năng 40%">
      </div>
      <div>
        <label class="form-label">Ngôn ngữ</label>
        <select name="language" class="form-select">
          <option value="vi">Tiếng Việt</option>
          <option value="en">English</option>
        </select>
      </div>

      <button id="aiAboutRun" type="button" 
        class="btn text-white fw-semibold px-4 py-2 border-0 rounded-3 shadow-sm d-flex align-items-center gap-2"
        style="background: linear-gradient(135deg, #6f42c1, #0d6efd); transition: all .3s ease;">
  <span class="spinner-border spinner-border-sm d-none" id="aiAboutSpin"></span>
  <i class="bi bi-robot"></i>
  <span>Sinh nội dung</span>
</button>

<style>
#aiAboutRun:hover {
  background: linear-gradient(135deg, #0d6efd, #6f42c1);
  transform: translateY(-1px);
  box-shadow: 0 6px 14px rgba(13, 110, 253, 0.3);
}
#aiAboutRun:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
#aboutToast {
  box-shadow: 0 4px 12px rgba(0,0,0,.15);
  border-radius: 8px;
  font-weight: 500;
}
</style>

      <div class="small text-muted">AI sẽ sinh văn bản và tự chèn vào ô soạn thảo ở modal.</div>
    </form>
  </div>
</div>

<!-- Toast lưu giới thiệu thành công -->
<div aria-live="polite" aria-atomic="true" class="position-relative">
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000">
    <div id="aboutToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-check-circle-fill me-2"></i> Giới thiệu đã được cập nhật thành công!
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
</div>

<style>
  /* Đưa offcanvas lên trên modal */
  .offcanvas,
  .offcanvas.show { z-index: 1065; }          /* > 1055 của modal */
  .offcanvas-backdrop { z-index: 1060; }       /* backdrop cũng cao hơn */
</style>
    {{-- Libs (chỉ import 1 lần) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.7/build/jodit.min.css">

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jodit@3.24.7/build/jodit.min.js"></script>

    <style>
        /* Giữ nguyên style avatar */
        #avatarWrapper { width:160px; height:160px; }
        .avatar-spinner { position:absolute; inset:0; background:rgba(0,0,0,.25); display:flex; align-items:center; justify-content:center; border-radius:50%; transition:opacity .2s; }
        .is-uploading #avatarBtn { pointer-events:none; opacity:.6; }

        /* Chặn Bootstrap tự set padding-right khi mở modal (tránh giật/đơ cuộn) */
        body.modal-open { padding-right:0 !important; }
        /* Nếu SweetAlert2 mở, vẫn cho cuộn (tránh kẹt) */
        body.swal2-shown { overflow:auto !important; }
        .btn-gradient {
  background: linear-gradient(135deg, #4e73df, #1cc88a);
  color: #fff;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  padding: 8px 20px;
  transition: all 0.3s ease;
  box-shadow: 0 3px 8px rgba(76,175,80,0.25);
}
.btn-gradient:hover {
  background: linear-gradient(135deg, #1cc88a, #4e73df);
  transform: translateY(-1px);
  box-shadow: 0 5px 12px rgba(76,175,80,0.35);
  color: #fff;
}
.btn-gradient:disabled {
  opacity: 0.7;
  pointer-events: none;
}
    </style>
<style>
  /* hiệu ứng mượt khi đổi sao */
  #reviewStars i {
    cursor: pointer;
    transition: transform .08s ease, color .08s ease;
    color: #ccc;
  }
  #reviewStars i.filled { color: #f1c40f; }
  #reviewStars i:hover { transform: scale(1.08); }
</style>
    <script>
        // ====== Helper gỡ mọi khóa cuộn (Modal/Swal) ======
        function unlockScroll() {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
            document.documentElement.style.removeProperty('overflow');
            ['swal2-shown','swal2-height-auto','swal2-no-backdrop','swal2-toast-shown']
                .forEach(c => document.body.classList.remove(c));
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }

        // ====== UI: Toggle jobs ======
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggle-jobs');
            const jobItems = document.querySelectorAll('.job-item.d-none');
            let expanded = false;
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    jobItems.forEach(item => item.classList.toggle('d-none'));
                    expanded = !expanded;
                    toggleBtn.innerHTML = expanded ? 'Thu gọn <i class="bi bi-chevron-up"></i>' : 'Xem thêm <i class="bi bi-chevron-down"></i>';
                });
            }
        });

        // ====== Avatar AJAX upload ======
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('avatarBtn');
            const input = document.getElementById('avatarInput');
            const form = document.getElementById('avatarForm');
            const spinner = document.getElementById('avatarSpinner');
            const wrapper = document.getElementById('avatarWrapper');
            const img = document.getElementById('avatarImg');

            if (!btn || !input || !form || !spinner || !img) return;

            btn.addEventListener('click', () => input.click());

            input.addEventListener('change', () => {
                if (!input.files.length) return;
                const file = input.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    Swal.fire({ icon: 'error', title: 'Tệp không hợp lệ', text: 'Chỉ chấp nhận JPG, PNG, WEBP.' });
                    input.value = '';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'Tệp quá lớn', text: 'Kích thước tối đa 5MB.' });
                    input.value = '';
                    return;
                }

                spinner.classList.remove('d-none');
                wrapper.classList.add('is-uploading');

                const fd = new FormData();
                fd.append('avatar', file);

                const token = (form.querySelector('input[name="_token"]') || {}).value
                    || (document.querySelector('meta[name="csrf-token"]') || {}).content
                    || '';

                $.ajax({
                    url: form.action, method: 'POST', data: fd, processData: false, contentType: false,
                    headers: { 'X-CSRF-TOKEN': token }
                })
                .done(function (res) {
                    if (res && res.ok) {
                        const url = res.url + (res.url.includes('?') ? '&' : '?') + 't=' + Date.now();
                        img.src = url;
                        Swal.fire({
                            icon: 'success', title: 'Thành công!', text: res.message || 'Ảnh đại diện đã được cập nhật.',
                            timer: 1500, showConfirmButton: false,
                            didOpen: unlockScroll, willClose: unlockScroll, didClose: unlockScroll, didDestroy: unlockScroll
                        });
                    } else {
                        const msg = (res && (res.message || (res.errors && Object.values(res.errors).flat().join(' ')))) || 'Upload thất bại.';
                        Swal.fire({ icon:'error', title:'Lỗi!', text:msg, didOpen:unlockScroll, willClose:unlockScroll, didClose:unlockScroll, didDestroy:unlockScroll });
                    }
                })
                .fail(function (xhr) {
                    let msg = 'Upload thất bại.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({ icon:'error', title:'Lỗi!', text:msg, didOpen:unlockScroll, willClose:unlockScroll, didClose:unlockScroll, didDestroy:unlockScroll });
                })
                .always(function () {
                    spinner.classList.add('d-none');
                    wrapper.classList.remove('is-uploading');
                    input.value = '';
                });
            });
        });

        // ====== Modal Location + Select2 ======
        (function () {
            const $modal = $('#editLocationModal');
            let provincesData = [];
            let provincesLoaded = false;

            function ensureProvincesLoaded() {
                return new Promise((resolve, reject) => {
                    if (provincesLoaded && provincesData.length) return resolve(provincesData);
                    fetch('https://provinces.open-api.vn/api/v2/?depth=2', { method: 'GET', cache: 'no-store', mode: 'cors', credentials: 'omit' })
                        .then(res => { if (!res.ok) throw new Error('Fetch provinces failed: ' + res.status); return res.json(); })
                        .then(data => { provincesData = Array.isArray(data) ? data : []; provincesLoaded = true; resolve(provincesData); })
                        .catch(reject);
                });
            }
            function collectWards(p) {
                if (!p) return [];
                if (Array.isArray(p.wards) && p.wards.length) return p.wards;
                const wards = [];
                (p.districts || []).forEach(d => (d.wards || []).forEach(w => wards.push(w)));
                return wards;
            }
            function fillProvinces() {
                const $prov = $('#c_province');
                $prov.empty().append('<option value=""></option>');
                provincesData.forEach(p => $prov.append(`<option value="${p.code}">${p.name}</option>`));
                $prov.val('');
            }
            function initSelect2() {
                if ($('#c_province').hasClass('select2-hidden-accessible')) $('#c_province').select2('destroy');
                if ($('#c_ward').hasClass('select2-hidden-accessible')) $('#c_ward').select2('destroy');
                const s2 = { theme: 'bootstrap-5', allowClear: true, width: '100%', dropdownParent: $modal };
                $('#c_province').select2({ ...s2, placeholder: 'Chọn tỉnh / thành phố' });
                $('#c_ward').select2({ ...s2, placeholder: 'Chọn phường / xã' });
                $(document).off('select2:open._focus').on('select2:open._focus', () => {
                    const el = document.querySelector('.select2-container--open .select2-search__field');
                    if (el) el.focus();
                });
            }
            function setupHandlers() {
                $('#c_province').off('change').on('change', function () {
                    const provCode = $(this).val();
                    const $ward = $('#c_ward');
                    $ward.prop('disabled', true).empty().append('<option value=""></option>').val(null).trigger('change.select2');
                    $('#locationInput').val('');
                    if (!provCode) return;

                    const province = provincesData.find(p => String(p.code) === String(provCode));
                    if (!province) return;
                    $('#locationInput').val(province.name);

                    const wards = collectWards(province);
                    wards.forEach(w => $ward.append(`<option value="${w.code}" data-codename="${w.codename || ''}">${w.name}</option>`));
                    $ward.prop('disabled', false).val(null).trigger('change.select2');
                    syncLocation();
                });
                $('#c_ward').off('change').on('change', function () { syncLocation(); });
            }
            function syncLocation() {
                const pCode = $('#c_province').val();
                const wText = $('#c_ward').find(':selected').text() || '';
                const pName = provincesData.find(x => String(x.code) === String(pCode))?.name || '';
                $('#locationInput').val([wText, pName].filter(Boolean).join(', '));
            }

            $modal.on('shown.bs.modal', async function () {
                try { await ensureProvincesLoaded(); fillProvinces(); initSelect2(); setupHandlers(); } catch (e) {}
            });
            $modal.on('hidden.bs.modal', function () {
                if ($('#c_province').hasClass('select2-hidden-accessible')) $('#c_province').select2('destroy');
                if ($('#c_ward').hasClass('select2-hidden-accessible')) $('#c_ward').select2('destroy');
                forceModalCleanup(); unlockScroll();
            });
        })();

        // ====== AJAX cập nhật Location ======
        (function () {
            const $modal = $('#editLocationModal');
            $modal.off('submit.location').on('submit.location', 'form#locationAjaxForm, form[action$="portfolios/location"]', function (e) {
                e.preventDefault();
                const $form = $(this);
                const action = $form.attr('action');
                const $btnSave = $form.find('button[type="submit"]');
                const token = $form.find('input[name="_token"]').val();

                $btnSave.prop('disabled', true).addClass('disabled');
                const originalHtml = $btnSave.html();
                $btnSave.html('<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...');

                const data = $form.serializeArray();
                if (!data.find(x => x.name === '_method')) data.push({ name: '_method', value: 'PATCH' });

                $.ajax({
                    url: action, type: 'POST', data: $.param(data),
                    headers: { 'X-CSRF-TOKEN': token || ($('meta[name="csrf-token"]').attr('content') || '') }
                })
                .done(function (res) {
                    if (res?.ok) {
                        if (res.location) $('#locationText').text(res.location);
                        try { $('#c_province').select2('close'); } catch (e) {}
                        try { $('#c_ward').select2('close'); } catch (e) {}
                        const modalEl = document.getElementById('editLocationModal');
                        const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalEl.addEventListener('hidden.bs.modal', function onHidden() {
                            modalEl.removeEventListener('hidden.bs.modal', onHidden);
                            forceModalCleanup(); unlockScroll();
                        }, { once: true });
                        m.hide();
                        setTimeout(function () { forceModalCleanup(); unlockScroll(); }, 150);
                        Swal.fire({
                            icon:'success', title:'Thành công!', text:res.message || 'Đã lưu địa chỉ.', timer:1800, showConfirmButton:false,
                            didOpen:unlockScroll, willClose:unlockScroll, didClose:unlockScroll, didDestroy:unlockScroll
                        });
                    } else {
                        Swal.fire({ icon:'error', title:'Lỗi!', text:res?.message || 'Không thể cập nhật địa chỉ.',
                            didOpen:unlockScroll, willClose:unlockScroll, didClose:unlockScroll, didDestroy:unlockScroll });
                    }
                })
                .fail(function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Có lỗi xảy ra khi cập nhật.';
                    Swal.fire({ icon:'error', title:'Lỗi!', text:msg, didOpen:unlockScroll, willClose:unlockScroll, didClose:unlockScroll, didDestroy:unlockScroll });
                })
                .always(function () {
                    $btnSave.prop('disabled', false).removeClass('disabled').html(originalHtml);
                });
            });

            window.forceModalCleanup = function () {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            };
        })();

        // ====== Watchdog dọn backdrop còn sót ======
        (function () {
            function cleanupBackdrops() {
                const hasOpen = document.querySelector('.modal.show');
                if (!hasOpen) { window.forceModalCleanup && window.forceModalCleanup(); unlockScroll(); }
            }
            document.addEventListener('hidden.bs.modal', cleanupBackdrops);
            document.addEventListener('shown.bs.modal', cleanupBackdrops);
            setInterval(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length && !document.querySelector('.modal.show')) cleanupBackdrops();
            }, 800);
        })();

        // ====== Jodit ======
        let jodit;
        document.getElementById('editAboutModal')?.addEventListener('shown.bs.modal', () => {
            const el = document.getElementById('aboutEditor');
            if (!el) return;
            if (el.classList.contains('jodit-wysiwyg_mode')) return;
            jodit = new Jodit('#aboutEditor', {
                uploader: { insertImageAsBase64URI: true },
                toolbarAdaptive: false,
                height: 380,
                placeholder: 'Start writing...',
                buttons: 'undo,redo,|,paragraph,|,bold,underline,italic,|,ul,ol,|,outdent,indent,|,image,link,table,|,brush,fullscreen,|,source',
            });
        });
        document.getElementById('editAboutModal')?.addEventListener('hidden.bs.modal', () => {
            if (jodit) { try { jodit.destruct(); } catch(e){} jodit = null; }
        });
        document.getElementById('aboutForm')?.addEventListener('submit', function(e){
            e.preventDefault();
            const form = this;
            const spin = document.getElementById('aboutSaveSpin');
            spin?.classList.remove('d-none');
            const data = new FormData(form);
            fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data })
            .then(async (res) => {
                const json = await res.json().catch(()=> ({}));
                if (!res.ok || !json?.ok) throw new Error(json?.message || 'Lưu thất bại');
                const container = document.querySelector('.intro-card .flex-grow-1');
                if (container) {
                    const alert = container.querySelector('.alert'); if (alert) alert.remove();
                    let p = container.querySelector('.intro-content');
                    if (!p) { p = document.createElement('div'); p.className = 'intro-content mb-0 text-secondary'; container.appendChild(p); }
                    p.innerHTML = json.html ?? data.get('description');
                    const modalEl = document.getElementById('editAboutModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                const toastEl = document.getElementById('aboutToast');
      if (toastEl) new bootstrap.Toast(toastEl, { delay: 2500 }).show();
            })
            .catch(err => { alert(err.message || 'Có lỗi xảy ra khi lưu.'); })
            .finally(() => { spin?.classList.add('d-none'); });
        });

        // ====== Toggle skills ======
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggle-skills');
            const extraSkills = document.querySelectorAll('.extra-skill');
            let expanded = false;
            if (!toggleBtn) return;
            toggleBtn.addEventListener('click', function () {
                extraSkills.forEach(s => s.classList.toggle('d-none'));
                expanded = !expanded;
                toggleBtn.innerHTML = expanded ? 'Thu gọn <i class="bi bi-chevron-up"></i>' : 'Xem thêm <i class="bi bi-chevron-down"></i>';
            });
        });

        // ====== Reviews: Toggle, Star picker, Submit + cập nhật realtime ======
        (function(){
            // Toggle reviews
            document.addEventListener('DOMContentLoaded', function(){
                const btn = document.getElementById('toggle-reviews');
                if(!btn) return;
                const items = document.querySelectorAll('.extra-review');
                let open = false;
                btn.addEventListener('click', function(){
                    items.forEach(el => el.classList.toggle('d-none'));
                    open = !open;
                    btn.innerHTML = open ? 'Thu gọn <i class="bi bi-chevron-up"></i>' : 'Xem thêm <i class="bi bi-chevron-down"></i>';
                });
            });

            // Star picker
            const starsWrap   = document.getElementById('reviewStars');
            const ratingInput = document.getElementById('ratingInput');

            function renderStars(v){
                if(!starsWrap) return;
                const val = Math.min(5, Math.max(1, parseInt(v || '5', 10)));
                if (ratingInput) ratingInput.value = String(val);
                [...starsWrap.querySelectorAll('i')].forEach((el, idx)=>{
                    el.classList.toggle('bi-star-fill', idx < val);
                    el.classList.toggle('bi-star',      idx >= val);
                    el.classList.toggle('active',       idx < val);
                });
            }
            if (ratingInput) renderStars(ratingInput.value || 5);
            starsWrap?.addEventListener('click', (e)=>{
                const t = e.target.closest('i[data-value]');
                if(!t) return;
                renderStars(t.getAttribute('data-value'));
            });

            // Submit review
            const form   = document.getElementById('reviewAjaxForm');
            const spin   = document.getElementById('reviewSpin');
            const errBox = document.getElementById('reviewError');
            const token  = document.querySelector('meta[name="csrf-token"]')?.content || '';

            function ensureTopRatingBadge(avg, count){
                const topBadges = document.getElementById('topBadges');
                if (!topBadges) return;
                const noBadge = document.getElementById('topNoRatingBadge');
                if (noBadge){
                    noBadge.remove();
                    const badge = document.createElement('span');
                    badge.id = 'topRatingBadge';
                    badge.className = 'badge rounded-pill bg-primary-subtle text-primary border';
                    badge.innerHTML = `
                        <i class="bi bi-star-fill me-1"></i>
                        <span id="topAvgBadgeNumber">${Number(avg).toFixed(1)}</span>/5
                        (<span id="topReviewCount">${count}</span> đánh giá)
                    `;
                    topBadges.insertBefore(badge, topBadges.children[1] || null);
                    return;
                }
                const topAvg = document.getElementById('topAvgBadgeNumber');
                const topCnt = document.getElementById('topReviewCount');
                if (topAvg) topAvg.textContent = Number(avg).toFixed(1);
                if (topCnt) topCnt.textContent = String(count);
            }

            form?.addEventListener('submit', async function(e){
                e.preventDefault();
                errBox?.classList.add('d-none');
                spin?.classList.remove('d-none');

                try {
                    const fd  = new FormData(form);
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    });
                    const json = await res.json();

                    if(!res.ok || !json?.ok){
                        throw new Error(json?.message || 'Gửi đánh giá thất bại.');
                    }

                    // (1) Cập nhật số trung bình & số lượng ở tab Reviews
                    const avgEl = document.getElementById('avgRatingNumber');
                    const cntEl = document.getElementById('reviewCountText');
                    if (avgEl) avgEl.textContent = Number(json.avg_rating || 0).toFixed(1);
                    if (cntEl) cntEl.textContent = `${json.review_count} đánh giá`;

                    // (2) Vẽ lại dãy sao trung bình
                    const starsEl = document.getElementById('avgStarsContainer');
                    if (starsEl) {
                        const avg  = Number(json.avg_rating || 0);
                        const full = Math.floor(avg);
                        const half = (avg - full) >= 0.5;
                        let html = '';
                        for (let i=1;i<=5;i++){
                            if (i <= full) html += '<i class="bi bi-star-fill"></i>';
                            else if (half && i === full + 1) html += '<i class="bi bi-star-half"></i>';
                            else html += '<i class="bi bi-star"></i>';
                        }
                        starsEl.innerHTML = html;
                    }

                    // (3) Thêm review mới lên đầu danh sách
                    const list = document.getElementById('review-list');
                    if (list) {
                        list.querySelector('p.text-muted')?.remove();
                        const card = document.createElement('div');
                        card.className = 'card shadow-sm border-0 mb-3';
                        const nameHtml = json.review.username
                          ? `<a href="${window.location.origin}/portfolios/${json.review.username}" class="text-decoration-none text-dark fw-semibold hover-underline">${json.review.fullname}</a>`
                          : (json.review.fullname || 'Ẩn danh');
                        const stars = [1,2,3,4,5].map(i => `<i class="bi ${i<=json.review.rating?'bi-star-fill':'bi-star'}"></i>`).join('');
                        card.innerHTML = `
                          <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                              <strong class="me-2">${nameHtml}</strong>
                              <div style="color:#f1c40f;">${stars}</div>
                            </div>
                            <p class="mb-0 text-secondary">${(json.review.comment||'').replace(/</g,'&lt;')}</p>
                          </div>`;
                        list.prepend(card);
                    }

                    // (4) Cập nhật badge trên đầu trang
                    ensureTopRatingBadge(json.avg_rating, json.review_count);

                    // (5) Đóng modal + reset form + trả sao về 5
                    bootstrap.Modal.getInstance(document.getElementById('reviewCreateModal'))?.hide();
                    form.reset();
                    renderStars(5);

                    // (6) Toast
                    if (window.Swal) {
                        Swal.fire({ icon:'success', title:'Thành công!', text: json.message || 'Đã gửi đánh giá.',
                                    timer: 1500, showConfirmButton:false });
                    }
                } catch (err) {
                    if (errBox) { errBox.textContent = err.message || 'Có lỗi xảy ra.'; errBox.classList.remove('d-none'); }
                    if (window.Swal) Swal.fire({ icon:'error', title:'Lỗi', text: err.message || 'Có lỗi xảy ra.' });
                } finally {
                    spin?.classList.add('d-none');
                }
            });
        })();
        (function(){
  const wrap  = document.getElementById('reviewStars');
  const input = document.getElementById('ratingInput');
  if (!wrap || !input) return;

  let selected = Math.min(5, Math.max(1, parseInt(input.value || '5', 10)));

  function paint(val){
    const stars = [...wrap.querySelectorAll('i[data-value]')];
    stars.forEach((el, idx) => {
      const fill = idx < val;
      el.classList.toggle('filled', fill);
      el.classList.toggle('bi-star-fill', fill);
      el.classList.toggle('bi-star', !fill);
      el.setAttribute('aria-checked', fill && idx+1===val ? 'true' : 'false');
    });
  }

  // Khởi tạo
  paint(selected);

  // Hover tới đâu => CHỌN tới đó (set luôn input)
  wrap.addEventListener('mouseover', (e) => {
    const star = e.target.closest('i[data-value]');
    if (!star) return;
    selected = parseInt(star.dataset.value, 10);
    input.value = selected;
    paint(selected);
  });

  // Khi rời khỏi cụm sao giữ nguyên lựa chọn cuối cùng
  wrap.addEventListener('mouseleave', () => paint(selected));

  // Vẫn hỗ trợ click (không bắt buộc)
  wrap.addEventListener('click', (e) => {
    const star = e.target.closest('i[data-value]');
    if (!star) return;
    selected = parseInt(star.dataset.value, 10);
    input.value = selected;
    paint(selected);
  });

  // Hỗ trợ bàn phím (Accessibility)
  wrap.setAttribute('role', 'radiogroup');
  [...wrap.querySelectorAll('i')].forEach(i => i.setAttribute('role', 'radio'));
  wrap.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
      selected = Math.min(5, selected + 1);
      input.value = selected; paint(selected); e.preventDefault();
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
      selected = Math.max(1, selected - 1);
      input.value = selected; paint(selected); e.preventDefault();
    }
  });
})();

document.addEventListener('DOMContentLoaded', function () {
  const runBtn   = document.getElementById('aiAboutRun');
  const spin     = document.getElementById('aiAboutSpin');
  const form     = document.getElementById('aiAboutForm');
  const editorEl = document.getElementById('aboutEditor');
  const csrf     = document.querySelector('meta[name="csrf-token"]')?.content || '';

  function setEditorContent(html) {
    // Lấy instance Jodit theo các đường khác nhau để chắc chắn
    const inst =
      (typeof jodit !== 'undefined' && jodit) ||
      (window.Jodit && window.Jodit.instances && window.Jodit.instances.aboutEditor) ||
      (document.getElementById('aboutEditor')?.__jodit) ||
      null;

    if (inst) {
      // Gán trực tiếp vào editor
      inst.value = html;
      // nếu muốn, bắn sự kiện change để các listener khác cập nhật
      if (inst.events) inst.events.fire('change');
      return;
    }

    // Fallback: nếu Jodit chưa init, gán tạm vào textarea
    const el = document.getElementById('aboutEditor');
    if (el) el.value = html;
  }

  runBtn?.addEventListener('click', async function () {
    const fd = new FormData(form);

    runBtn.disabled = true;
    spin.classList.remove('d-none');

    try {
      const res = await fetch("{{ route('profile.about.ai') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      });
      const json = await res.json();

      if (!res.ok || !json?.ok) {
        throw new Error(json?.error || 'Không tạo được nội dung.');
      }

      setEditorContent(json.html || '');
      // đóng offcanvas cho gọn
      const canvasEl = document.getElementById('aiAboutCanvas');
      bootstrap.Offcanvas.getInstance(canvasEl)?.hide();

      // highlight nhẹ phần editor
      const container = editorEl?.closest('.modal-body') || document;
      const flash = document.createElement('div');
      flash.style.cssText = 'position:absolute;inset:0;background:rgba(28,200,138,.12);border-radius:12px;pointer-events:none;';
      container.appendChild(flash);
      setTimeout(()=>flash.remove(), 550);
    } catch (e) {
      alert(e.message || 'Lỗi không xác định.');
    } finally {
      runBtn.disabled = false;
      spin.classList.add('d-none');
    }
  });
});
    </script>
@endsection
