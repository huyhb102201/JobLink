@extends('settings.layout')
@section('title', 'Doanh nghiệp của tôi')
@section('settings_content')

    <div class="container" style="max-width:1500px;margin-top:40px;margin-bottom:120px;">
        <div class="row g-4">
            <div class="col-12">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="text-secondary">Quản lý tổ chức, ghế thành viên và quyền truy cập.</div>
                </div>

                {{-- Alerts --}}
                @if(session('ok'))
                    <div class="alert alert-success shadow-sm rounded-3">{{ session('ok') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger shadow-sm rounded-3">{{ $errors->first() }}</div>
                @endif

                @if(!$isBusiness)
                    <div class="surface p-4 rounded-4">
                        <h5 class="mb-1 text-dark">Chỉ dành cho tài khoản Business</h5>
                        <div class="text-secondary">Nâng cấp lên gói Business để tạo doanh nghiệp, mời thành viên và phân quyền.
                        </div>
                    </div>

                @elseif(!$org)
                    {{-- Empty state --}}
                    <div class="empty-light rounded-4 p-5 text-center">
                        <div class="empty-icon mx-auto mb-3"><i class="bi bi-buildings"></i></div>
                        <h4 class="fw-semibold mb-2 text-dark">Bạn chưa có doanh nghiệp</h4>
                        <p class="text-secondary mb-4">Tạo doanh nghiệp để bắt đầu mời đồng đội, phân quyền và cộng tác.</p>
                        <button class="btn btn-primary btn-lg rounded-pill px-4" data-bs-toggle="modal"
                            data-bs-target="#createOrgModal">
                            <i class="bi bi-plus-lg me-2"></i>Tạo doanh nghiệp
                        </button>
                    </div>

                @else
                    @php
                        $total = max(1, (int) ($org->seats_limit ?? 1));
                        $used = (int) ($usedSeats ?? 0);
                        $percent = min(100, (int) round(($used / $total) * 100));
                        $remain = max(0, $total - $used);
                    @endphp

                    {{-- Tổng quan --}}
                    <div class="card border-0 rounded-4 shadow-xs mb-4 overview-card">
                        <div class="card-body p-4 p-md-4">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-buildings text-primary fs-4"></i>
                                        <h3 class="m-0 fw-bold text-dark">{{ $org->name }}</h3>

                                        @php
                                            $v = $org->status ?? 'UNVERIFIED';
                                            $map = [
                                                'VERIFIED' => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-patch-check-fill', 'text' => 'Đã xác minh'],
                                                'PENDING' => ['bg' => '#fff4cc', 'color' => '#8a6d3b', 'icon' => 'bi-hourglass-split', 'text' => 'Đang chờ duyệt'],
                                                'REJECTED' => ['bg' => '#ffe4e6', 'color' => '#9f1239', 'icon' => 'bi-x-octagon-fill', 'text' => 'Bị từ chối'],
                                                'UNVERIFIED' => ['bg' => '#e2e8f0', 'color' => '#0f172a', 'icon' => 'bi-shield-exclamation', 'text' => 'Chưa xác minh'],
                                            ];
                                            $b = $map[$v] ?? $map['UNVERIFIED'];
                                          @endphp

                                        <span class="badge d-inline-flex align-items-center"
                                            style="background: {{ $b['bg'] }}; color: {{ $b['color'] }}; gap:.35rem; border-radius:999px; padding:.35rem .6rem;">
                                            <i class="bi {{ $b['icon'] }}"></i> {{ $b['text'] }}
                                        </span>
                                    </div>

                                    @if($org->description)
                                        <div class="text-secondary mb-2">{{ $org->description }}</div>
                                    @endif
                                    <span class="chip-light">Mã tổ chức: #{{ $org->org_id }}</span>
                                </div>

                                <div class="text-end">
                                    <div class="fw-semibold text-dark">Số lượng: {{ $used }} / {{ $total }}</div>
                                    {{-- Hiển thị đúng Chủ sở hữu theo $owner --}}
                                    <div class="small text-secondary">
                                        Chủ sở hữu: {{ $owner?->profile?->fullname ?? $owner?->email ?? '—' }}
                                    </div>

                                    <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                        {{-- Thumbnail / nút xác minh: chỉ Owner mới thấy --}}
                                        @if($isOwner)
                                            @if(!empty($latestVerification))
                                                @php
                                                    $isImg = str_starts_with($latestVerification->mime_type ?? '', 'image/');
                                                    $fileUrl = $latestVerification->file_url
                                                        ?? \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::getUrl($latestVerification->file_path);
                                                  @endphp
                                                @if($isImg)
                                                    <button type="button" class="p-0 border-0 bg-transparent" data-bs-toggle="modal"
                                                        data-bs-target="#verifyOrgModal" title="Xem hồ sơ đã gửi">
                                                        <img src="{{ $fileUrl }}" alt="Giấy tờ đã gửi" class="verify-thumb">
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill"
                                                        data-bs-toggle="modal" data-bs-target="#verifyOrgModal" title="Xem hồ sơ đã gửi">
                                                        <i class="bi bi-file-earmark-text me-1"></i> Tệp đã gửi
                                                    </button>
                                                @endif
                                            @endif

                                            {{-- Nút xác minh / trạng thái (Owner) --}}
                                            @if(in_array($org->status, ['UNVERIFIED', 'REJECTED']))
                                                <button class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal"
                                                    data-bs-target="#verifyOrgModal">
                                                    <i class="bi bi-upload me-1"></i> Xác minh doanh nghiệp
                                                </button>
                                            @elseif($org->status === 'PENDING')
                                                <button class="btn btn-outline-secondary btn-sm rounded-pill" disabled>
                                                    <i class="bi bi-hourglass me-1"></i> Đã gửi xác minh
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL: Xác minh doanh nghiệp (chỉ Owner) --}}
                            @if($isOwner)
                                <div class="modal fade" id="verifyOrgModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-md modal-dialog-centered">
                                        <div class="modal-content rounded-4">
                                            <form method="POST" action="{{ route('company.verification.submit') }}"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="_modal" value="verify_org">

                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-dark">
                                                        <i class="bi bi-patch-check me-2"></i> Xác minh doanh nghiệp
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Đóng"></button>
                                                </div>

                                                <div class="modal-body">
                                                    @if($errors->any() && old('_modal') === 'verify_org')
                                                        <div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>
                                                    @endif

                                                    <p class="text-secondary">Tải lên giấy phép/giấy chứng nhận doanh nghiệp (ảnh
                                                        hoặc PDF).</p>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Tệp đính kèm</label>
                                                        <input type="file" name="file" class="form-control"
                                                            accept="image/*,application/pdf" required>
                                                        <div class="form-text">Hỗ trợ: JPG, PNG, WEBP, PDF. Tối đa 10MB.</div>
                                                        @error('file') <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    @if(!empty($latestVerification))
                                                        <div class="p-3 rounded-3"
                                                            style="background:#f8fafc; border:1px solid #e2e8f0;">
                                                            <div class="small text-secondary mb-1">Lần gửi gần nhất:</div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span
                                                                    class="badge bg-light text-dark">{{ $latestVerification->status }}</span>
                                                                <span class="text-secondary small">
                                                                    {{ \Carbon\Carbon::parse($latestVerification->created_at)->format('d/m/Y H:i') }}
                                                                </span>
                                                            </div>
                                                            @if($latestVerification->review_note)
                                                                <div class="mt-2 small">Ghi chú: <span
                                                                        class="text-secondary">{{ $latestVerification->review_note }}</span>
                                                                </div>
                                                            @endif
                                                          @php
  $previewUrl = $latestVerification->file_url
      ?: \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary
            ::image($latestVerification->file_path)->secure()->toUrl();
@endphp

@if($previewUrl)
  <img src="{{ $previewUrl }}" alt="preview" style="max-width:100%;border-radius:8px;">
@endif

                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="modal-footer border-0 pt-0">
                                                    <button class="btn btn-primary rounded-pill px-4" type="submit">
                                                        <i class="bi bi-send-check me-2"></i>Gửi xác minh
                                                    </button>
                                                    <button class="btn btn-outline-secondary rounded-pill" type="button"
                                                        data-bs-dismiss="modal">Hủy</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-3">
                                <div class="progress rounded-pill" style="height: 10px;background:#eef2ff;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%;">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 text-secondary small">
                                    <span>Đã dùng {{ $used }}</span>
                                    <span>Còn lại {{ $remain }} ghế</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Thành viên + Lời mời chờ xác nhận --}}
                    <div class="card border-0 rounded-4 shadow-xs">
                        <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="fw-semibold text-dark mb-3">Thành viên</h5>

                            @if($isOwner)
                                <button class="btn btn-sm text-white fw-semibold rounded-pill px-4 py-2 border-0 shadow-sm d-inline-flex align-items-center gap-2"
        style="background: linear-gradient(135deg, #4e73df, #1cc88a); transition: all .3s;"
        data-bs-toggle="modal" data-bs-target="#addMemberModal">
  <i class="bi bi-person-plus"></i>
  <span>Thêm thành viên</span>
</button>

                            @else
                                <form method="POST" action="{{ route('company.members.leave', ['org' => $org->org_id]) }}"
                                    class="m-0" onsubmit="return confirm('Bạn chắc chắn muốn rời doanh nghiệp này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger rounded-pill">
                                        <i class="bi bi-box-arrow-right me-2"></i> Rời doanh nghiệp
                                    </button>
                                </form>
                            @endif
                        </div>


                        <div class="list-group list-group-flush member-list">
                            {{-- DANH SÁCH THÀNH VIÊN --}}
                            @forelse($members as $m)
                                @php
                                    $name = trim($m->fullname ?? '') !== '' ? $m->fullname : null;
                                    $initial = strtoupper(mb_substr($name ?? $m->email, 0, 1));
                                    $role = $m->role;
                                    $roleClass = [
                                        'OWNER' => 'badge-owner',
                                        'ADMIN' => 'badge-admin',
                                        'MANAGER' => 'badge-manager',
                                        'MEMBER' => 'badge-member',
                                        'BILLING' => 'badge-billing',
                                    ][$role] ?? 'badge-member';
                                    $state = $m->status ?? 'ACTIVE';
                                  @endphp

                                <div class="list-group-item px-4 py-3">
                                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">{{ $initial }}</div>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $name ?? $m->email }}</div>
                                                <div class="text-secondary small">{{ $m->email }}</div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-3">
                                            @if($state === 'PENDING')
                                                <span class="badge rounded-pill"
                                                    style="background:#fff4cc;color:#8a6d3b;">Pending</span>
                                            @endif
                                            <span
                                                class="badge rounded-pill role-badge {{ $roleClass }}">{{ ucfirst(strtolower($role)) }}</span>
                                            <span class="text-secondary small">
                                                @if($state === 'ACTIVE')
                                                    Tham gia {{ \Carbon\Carbon::parse($m->joined_at)->format('d/m/Y') }}
                                                @else
                                                    Đã mời
                                                    {{ \Carbon\Carbon::parse($m->invited_at ?? $m->joined_at)->format('d/m/Y H:i') }}
                                                @endif
                                            </span>

                                            {{-- Xoá thành viên: chỉ Owner và không xoá Owner --}}
                                            @if($isOwner && $role !== 'OWNER')
                                                <form method="POST"
                                                    action="{{ route('company.members.remove', ['org' => $org->org_id, 'account' => $m->account_id]) }}"
                                                    class="d-inline js-remove-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-icon btn-soft-danger"
                                                        data-name="{{ $name ?? $m->email }}" title="Xoá khỏi tổ chức"
                                                        aria-label="Xoá khỏi tổ chức">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center text-secondary py-5">
                                    Chưa có thành viên nào ngoài chủ tổ chức.
                                </div>
                            @endforelse

                            {{-- LỜI MỜI ĐANG CHỜ: chỉ Owner --}}
                            @if($isOwner && !empty($pendingInvites) && $pendingInvites->isNotEmpty())
                                <div class="list-group-item px-4 py-2 bg-white">
                                    <span class="text-secondary small fw-semibold">
                                        Đang chờ xác nhận ({{ $pendingInvites->count() }})
                                    </span>
                                </div>

                                @foreach($pendingInvites as $iv)
                                    @php
                                        $email = $iv->email ?? $iv->invitee_email ?? '—';
                                        $name = $iv->invitee_fullname ?: ($iv->invitee_username ? '@' . $iv->invitee_username : $email);
                                        $initial = strtoupper(mb_substr($name, 0, 1));
                                        $role = $iv->role ?? 'MEMBER';
                                        $exp = $iv->expires_at ? \Carbon\Carbon::parse($iv->expires_at) : null;

                                        $roleClass = [
                                            'OWNER' => 'badge-owner',
                                            'ADMIN' => 'badge-admin',
                                            'MANAGER' => 'badge-manager',
                                            'MEMBER' => 'badge-member',
                                            'BILLING' => 'badge-billing',
                                        ][$role] ?? 'badge-member';
                                    @endphp

                                    <div class="list-group-item px-4 py-3 bg-light">
                                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar">{{ $initial }}</div>
                                                <div>
                                                    <div class="fw-semibold text-dark">{{ $name }}</div>
                                                    <div class="text-secondary small">
                                                        {{ $email }}
                                                        • Mời {{ \Carbon\Carbon::parse($iv->created_at)->format('d/m/Y H:i') }}
                                                        @if($exp) • Hết hạn {{ $exp->format('d/m/Y H:i') }} @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge rounded-pill"
                                                    style="background:#fff4cc;color:#8a6d3b;">Pending</span>
                                                <span
                                                    class="badge rounded-pill role-badge {{ $roleClass }}">{{ ucfirst(strtolower($role)) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- MODAL: Tạo doanh nghiệp --}}
    <div class="modal fade" id="createOrgModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <form method="POST" action="{{ route('settings.company.store') }}">
                    @csrf
                    <input type="hidden" name="_modal" value="create_org">

                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-building me-2"></i>Tạo doanh nghiệp</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>

                    <div class="modal-body pt-3">
                        @if($errors->any() && old('_modal') === 'create_org')
                            <div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Tên doanh nghiệp</label>
                                <input id="orgName" type="text" name="name" class="form-control form-control-lg"
                                    placeholder="VD: Công ty ABC" required value="{{ old('name') }}">
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Số ghế (seats)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="seats_limit" class="form-control w-auto"
                                        style="max-width:120px" min="1" max="5" required
                                        value="{{ old('seats_limit', 5) }}">
                                    <span class="text-secondary small">Tối đa 5 thành viên</span>
                                </div>
                                @error('seats_limit') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Mô tả</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="Mô tả ngắn về doanh nghiệp">{{ old('description') }}</textarea>
                                @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button class="btn btn-primary rounded-pill px-4" type="submit">
                            <i class="bi bi-check2-circle me-2"></i>Tạo doanh nghiệp
                        </button>
                        <button class="btn btn-outline-secondary rounded-pill" type="button"
                            data-bs-dismiss="modal">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Thêm thành viên bằng username (chỉ Owner) --}}
    @if($isOwner && !empty($org))
        <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content rounded-4">
                    <form id="inviteForm" method="POST" action="{{ route('company.members.invite') }}">
                        @csrf
                        <input type="hidden" name="_modal" value="add_member">
                        <input type="hidden" name="org_id" value="{{ $org->org_id }}">

                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold text-dark">
                                <i class="bi bi-person-plus me-2"></i>Thêm thành viên
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>

                        <div class="modal-body">
                            @if($errors->any() && old('_modal') === 'add_member')
                                <div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>
                            @endif

                            <label class="form-label fw-semibold">Username</label>
<div class="input-group">
  <span class="input-group-text">@</span>
  <input id="inviteUsername" type="text" name="username" class="form-control" required
         placeholder="vd: nguyenvana" value="{{ old('username') }}">
</div>
<!-- nơi hiện lỗi validate của username -->
<div id="inviteUsernameError" class="text-danger small mt-1 d-none"></div>
                            <div class="form-text">Người được mời cần xác nhận qua email. Quyền mặc định:
                                <strong>Member</strong>.</div>
                            @error('username') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="modal-footer border-0 pt-0">
  <button id="btnInviteSubmit"
          class="btn btn-sm fw-semibold text-white rounded-pill px-4 py-2 border-0 shadow-sm"
          style="background: linear-gradient(135deg, #5fa8f1, #7fe0b5); transition: all .3s;"
          type="submit" form="inviteForm">
    <i class="bi bi-send me-1"></i> Gửi lời mời
  </button>
  <button class="btn btn-sm fw-semibold rounded-pill px-4 py-2 border-0 text-secondary bg-light shadow-sm"
          type="button" data-bs-dismiss="modal">
    <i class="bi bi-x-circle me-1"></i> Hủy
  </button>
</div>
                    </form>
                </div>
            </div>
        </div>
    @endif
        {{-- MODAL: Xác nhận xoá thành viên (dùng chung) --}}
<div class="modal fade" id="confirmRemoveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold text-dark">
          <i class="bi bi-person-dash me-2"></i> Xác nhận xoá
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-light border d-flex align-items-start gap-2 mb-0">
          <i class="bi bi-exclamation-circle-fill fs-5 text-danger"></i>
          <div>
            Bạn có chắc muốn xoá
            <strong id="confirmRemoveName">thành viên</strong>
            khỏi tổ chức? Hành động này không thể hoàn tác.
          </div>
        </div>
      </div>

      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
          Hủy
        </button>
        <button id="btnConfirmRemove" type="button"
                class="btn btn-danger rounded-pill px-4">
          <i class="bi bi-trash me-1"></i> Xoá khỏi tổ chức
        </button>
      </div>
    </div>
  </div>
</div>
    @push('styles')
        <style>
            .surface {
                background: #fff;
                border: 1px solid #edf0f4;
                box-shadow: 0 2px 12px rgba(30, 41, 59, .04)
            }

            .empty-light {
                background: linear-gradient(180deg, #f9fbff 0%, #fff 100%);
                border: 1px dashed #d8e0ef;
            }

            .empty-icon {
                width: 72px;
                height: 72px;
                border-radius: 20px;
                background: #eff4ff;
                color: #4f46e5;
                display: grid;
                place-items: center;
                font-size: 30px;
            }

            .overview-card {
                background: #fff;
                border: 1px solid #edf0f4;
                box-shadow: 0 6px 20px rgba(30, 41, 59, .06);
            }

            .chip-light {
                display: inline-block;
                padding: .35rem .75rem;
                border-radius: 999px;
                background: #f3f6ff;
                color: #334155;
                font-size: .875rem;
                border: 1px solid #e5eaff;
            }

            .shadow-xs {
                box-shadow: 0 4px 16px rgba(30, 41, 59, .05);
            }

            .avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #e6ecff;
                color: #233876;
                display: grid;
                place-items: center;
                font-weight: 700;
            }

            .role-badge {
                padding: .4rem .75rem;
                font-weight: 600;
            }

            .badge-owner {
                background: #fef3c7;
                color: #92400e;
            }

            .badge-admin {
                background: #dcfce7;
                color: #166534;
            }

            .badge-manager {
                background: #e0e7ff;
                color: #3730a3;
            }

            .badge-member {
                background: #f1f5f9;
                color: #0f172a;
            }

            .badge-billing {
                background: #ffe4e6;
                color: #9f1239;
            }

            .btn-icon {
                width: 34px;
                height: 34px;
                padding: 0;
                display: inline-grid;
                place-items: center;
                border-radius: 999px;
                line-height: 1;
            }

            .btn-soft-danger {
                background: #fff5f5;
                border: 1px solid #ffe0e0;
                color: #c1121f;
            }

            .btn-soft-danger:hover {
                background: #ffe5e5;
                border-color: #ffc9c9;
                color: #a50e19;
                box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .12);
            }

            .btn-soft-danger:active {
                transform: translateY(1px);
            }

            .verify-badge {
                border-radius: 999px;
                font-weight: 600;
                padding: .25rem .6rem;
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                font-size: .8rem;
                border: 1px solid transparent;
            }

            .verify-badge.verified {
                background: #e8f7ef;
                color: #166534;
                border-color: #c7eed8;
            }

            .verify-badge.unverified {
                background: #fff7ed;
                color: #9a3412;
                border-color: #ffedd5;
            }

            .verify-badge.pending {
                background: #fefce8;
                color: #854d0e;
                border-color: #fde68a;
            }

            .verify-badge.rejected {
                background: #fee2e2;
                color: #7f1d1d;
                border-color: #fecaca;
            }

            .verify-thumb {
                width: 48px;
                height: 36px;
                object-fit: cover;
                border-radius: 8px;
                border: 1px solid #e5e7eb;
                box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
                display: block;
            }

            .verify-thumb:hover {
                transform: scale(1.03);
                box-shadow: 0 4px 10px rgba(0, 0, 0, .08);
            }
        </style>
    @endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form   = document.getElementById('inviteForm');
  if (!form) return;

  const btn    = document.getElementById('btnInviteSubmit');
  const modalEl= document.getElementById('addMemberModal');
  const usernameEl = document.getElementById('inviteUsername');
  const errEl  = document.getElementById('inviteUsernameError');
  const csrf   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  // Toast helper (nếu bạn đã có sẵn, dùng cái hiện có)
  function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container position-fixed top-0 end-0 p-3';
      container.style.zIndex = 1090;
      document.body.appendChild(container);
    }
    const cls = {
      success:'bg-success text-white',
      error:'bg-danger text-white',
      warning:'bg-warning text-dark',
      info:'bg-info text-dark'
    }[type] || 'bg-success text-white';

    const el = document.createElement('div');
    el.className = `toast align-items-center border-0 ${cls}`;
    el.setAttribute('role','alert'); el.setAttribute('aria-live','assertive'); el.setAttribute('aria-atomic','true');
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    container.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3000, autohide: true });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  // (Tuỳ chọn) chèn dòng Pending vào danh sách
  function appendPendingInvite(invite) {
    // Tìm khối “Đang chờ xác nhận” nếu có
    const list = document.querySelector('.member-list');
    if (!list) return;

    // Nếu chưa có header “Đang chờ xác nhận”, thêm nhanh
    let header = [...list.querySelectorAll('.list-group-item')].find(x => x.textContent.includes('Đang chờ xác nhận'));
    if (!header) {
      const count = 1;
      list.insertAdjacentHTML('beforeend', `
        <div class="list-group-item px-4 py-2 bg-white">
          <span class="text-secondary small fw-semibold">Đang chờ xác nhận (${count})</span>
        </div>
      `);
      header = list.lastElementChild;
    } else {
      // cập nhật số lượng (tìm số trong ngoặc)
      const span = header.querySelector('span');
      if (span) {
        const m = span.textContent.match(/\((\d+)\)/);
        const n = m ? (parseInt(m[1],10) + 1) : 1;
        span.textContent = `Đang chờ xác nhận (${n})`;
      }
    }

    // Thêm item mới
    const name  = invite.invitee_fullname || (invite.invitee_username ? '@' + invite.invitee_username : (invite.email || invite.invitee_email || '—'));
    const email = invite.email || invite.invitee_email || '—';
    const role  = (invite.role || 'MEMBER').toLowerCase();
    const roleClass = {
      OWNER:'badge-owner', ADMIN:'badge-admin', MANAGER:'badge-manager', MEMBER:'badge-member', BILLING:'badge-billing'
    }[(invite.role || 'MEMBER').toUpperCase()] || 'badge-member';
    const initial = (name || email).trim().charAt(0).toUpperCase();

    const createdAt = invite.created_at_fmt || '';
    const expiresAt = invite.expires_at_fmt ? ` • Hết hạn ${invite.expires_at_fmt}` : '';

    const html = `
      <div class="list-group-item px-4 py-3 bg-light">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">${initial}</div>
            <div>
              <div class="fw-semibold text-dark">${name}</div>
              <div class="text-secondary small">
                ${email} • Mời ${createdAt}${expiresAt}
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" style="background:#fff4cc;color:#8a6d3b;">Pending</span>
            <span class="badge rounded-pill role-badge ${roleClass}">${role.charAt(0).toUpperCase()+role.slice(1)}</span>
          </div>
        </div>
      </div>`;
    list.insertAdjacentHTML('beforeend', html);
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (errEl) { errEl.textContent = ''; errEl.classList.add('d-none'); }

    // BTN state
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang gửi...';

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
        },
        body: new FormData(form)
      });

      const data = await res.json().catch(() => null);

      if (!res.ok) {
        // 422 từ Laravel: có errors
        if (res.status === 422 && data?.errors) {
          const msg = data.errors.username?.join(' ') || data.message || 'Dữ liệu không hợp lệ.';
          if (errEl) { errEl.textContent = msg; errEl.classList.remove('d-none'); }
          usernameEl?.classList.add('is-invalid');
        } else {
          showToast(data?.message || 'Không thể gửi lời mời.', 'error');
        }
        return;
      }

      // Success
      usernameEl?.classList.remove('is-invalid');
      form.reset();

      // Đóng modal
      if (window.bootstrap && modalEl) {
        const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalEl.addEventListener('hidden.bs.modal', () => {
          showToast(data?.message || 'Đã gửi lời mời.', 'success');
        }, { once: true });
        instance.hide();
      } else {
        showToast(data?.message || 'Đã gửi lời mời.', 'success');
      }

      // (Tuỳ chọn) chèn pending invite mới vào danh sách
      if (data?.invite) {
        appendPendingInvite(data.invite);
      }

    } catch (err) {
      showToast('Lỗi mạng, vui lòng thử lại.', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  });
});
</script>
@endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Focus tên DN khi mở modal tạo org
                const createEl = document.getElementById('createOrgModal');
                if (createEl) {
                    createEl.addEventListener('shown.bs.modal', function () {
                        const input = document.getElementById('orgName'); if (input) input.focus();
                    });
                }

                // Tự mở lại modal tương ứng nếu có lỗi
                @if($errors->any() && old('_modal') === 'create_org')
                    new bootstrap.Modal(document.getElementById('createOrgModal')).show();
                @endif
                    @if($errors->any() && old('_modal') === 'add_member')
                        const addEl = document.getElementById('addMemberModal');
                        if (addEl) new bootstrap.Modal(addEl).show();
                    @endif
        });

            // Xoá thành viên (chỉ Owner mới render nút)
            // Xoá thành viên bằng Bootstrap Modal xác nhận
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('confirmRemoveModal');
  if (!modalEl) return;

  const nameEl  = modalEl.querySelector('#confirmRemoveName');
  const okBtn   = modalEl.querySelector('#btnConfirmRemove');
  const bsModal = new bootstrap.Modal(modalEl);

  let pendingForm = null;   // form sẽ submit sau khi xác nhận
  let triggerBtn  = null;   // nút X để restore trạng thái nếu cần
  let savedHtml   = '';     // HTML gốc của nút

  // Gán click cho các nút xoá
  document.querySelectorAll('.js-remove-form .btn-icon').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const name = this.dataset.name?.trim() || 'thành viên';
      nameEl.textContent = name;

      // Lưu lại context
      pendingForm = this.closest('form');
      triggerBtn  = this;
      savedHtml   = this.innerHTML;

      // Mở modal
      bsModal.show();
    });
  });

  // Khi bấm xác nhận
 // Khi bấm xác nhận
okBtn.addEventListener('click', async function () {
  if (!pendingForm || !triggerBtn) return;

  // --- Loading state trên NÚT XÁC NHẬN ---
  const okOriginal = okBtn.innerHTML;
  okBtn.disabled = true;
  okBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xoá...';

  // (tuỳ chọn) khoá nút X cho chắc, nhưng KHÔNG đổi icon/text của nó
  triggerBtn.disabled = true;

  const url  = pendingForm.action;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const fd   = new FormData(pendingForm); // có _token + _method=DELETE

  try {
    const res  = await fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
      },
      body: fd
    });
    const data = await res.json().catch(() => null);

    if (!res.ok || !data?.success) {
      window.showToast(data?.message || 'Không thể xoá thành viên.', 'error');
      return;
    }

    // Xoá item trên UI
    const item = pendingForm.closest('.list-group-item');
    if (item) item.remove();

    window.showToast(data.message || 'Đã xoá thành viên khỏi tổ chức.', 'success');
    bsModal.hide();
  } catch (e) {
    window.showToast('Lỗi mạng, vui lòng thử lại.', 'error');
  } finally {
    // Khôi phục trạng thái nút xác nhận & nút X
    okBtn.disabled = false;
    okBtn.innerHTML = okOriginal;
    if (triggerBtn) triggerBtn.disabled = false;

    pendingForm = null;
    triggerBtn  = null;
  }
});


            // Nếu có lỗi xác minh thì tự mở modal (chỉ mở nếu modal tồn tại — tức Owner)
            document.addEventListener('DOMContentLoaded', function () {
                @if($errors->any() && old('_modal') === 'verify_org')
                    const el = document.getElementById('verifyOrgModal');
                    if (el) new bootstrap.Modal(el).show();
                @endif
        });
        </script>
        <script>
window.showToast = window.showToast || function (message, type = 'success') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = 1090;
    document.body.appendChild(container);
  }
  const cls = { success:'bg-success text-white', error:'bg-danger text-white', warning:'bg-warning text-dark', info:'bg-info text-dark' }[type] || 'bg-success text-white';
  const el = document.createElement('div');
  el.className = `toast align-items-center border-0 ${cls}`;
  el.setAttribute('role','alert'); el.setAttribute('aria-live','assertive'); el.setAttribute('aria-atomic','true');
  el.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  container.appendChild(el);
  const t = new bootstrap.Toast(el, { delay: 3000, autohide: true });
  t.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
};

document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('confirmRemoveModal');
  if (!modalEl) { console.warn('confirmRemoveModal not found'); return; }

  const nameEl  = modalEl.querySelector('#confirmRemoveName');
  const okBtn   = modalEl.querySelector('#btnConfirmRemove');
  const bsModal = new bootstrap.Modal(modalEl);

  let pendingForm = null;
  let triggerBtn  = null;
  let savedHtml   = '';

  // 1) UỶ QUYỀN SỰ KIỆN: bắt mọi click vào nút X
  document.body.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-remove-form .btn-icon');
    if (!btn) return;

    const form = btn.closest('form');
    if (!form) return;

    const name = btn.dataset.name?.trim() || 'thành viên';
    nameEl.textContent = name;

    pendingForm = form;
    triggerBtn  = btn;
    savedHtml   = btn.innerHTML;

    bsModal.show();
  });

  // 2) XÁC NHẬN XOÁ (AJAX)
  okBtn.addEventListener('click', async function () {
    if (!pendingForm || !triggerBtn) return;

    okBtn.disabled = true;
    triggerBtn.disabled = true;
    const originalHtml = triggerBtn.innerHTML;
    triggerBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

    const url  = pendingForm.action;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fd   = new FormData(pendingForm); // có _token + _method=DELETE

    try {
      const res = await fetch(url, {
        method: 'POST', // Laravel hiểu method qua _method
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
        },
        body: fd
      });

      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.success) {
        window.showToast(data?.message || 'Không thể xoá thành viên.', 'error');
        return;
      }

      // Remove item khỏi UI
      const item = pendingForm.closest('.list-group-item');
      if (item) item.remove();

      window.showToast(data.message || 'Đã xoá thành viên khỏi tổ chức.', 'success');
      bsModal.hide();
    } catch (err) {
      window.showToast('Lỗi mạng, vui lòng thử lại.', 'error');
    } finally {
      okBtn.disabled = false;
      if (triggerBtn) {
        triggerBtn.disabled = false;
        triggerBtn.innerHTML = savedHtml || originalHtml;
      }
      pendingForm = null;
      triggerBtn  = null;
      savedHtml   = '';
    }
  });

  // 3) Reset khi modal đóng (phòng trường hợp user bấm Hủy)
  modalEl.addEventListener('hidden.bs.modal', () => {
    okBtn.disabled = false;
    if (triggerBtn) {
      triggerBtn.disabled = false;
      triggerBtn.innerHTML = savedHtml || triggerBtn.innerHTML;
    }
    pendingForm = null;
    triggerBtn  = null;
    savedHtml   = '';
  });
});
</script>

    @endpush

@endsection