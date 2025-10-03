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
                                    </div>
                                    @if($org->description)
                                        <div class="text-secondary mb-2">{{ $org->description }}</div>
                                    @endif
                                    <span class="chip-light">Mã tổ chức: #{{ $org->org_id }}</span>
                                </div>

                                <div class="text-end">
                                    <div class="fw-semibold text-dark">Số lượng: {{ $used }} / {{ $total }}</div>
                                    <div class="small text-secondary">Chủ sở hữu:
                                        {{ $account->profile->fullname ?? $account->email }}</div>
                                </div>
                            </div>

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
                            <button class="btn btn-primary rounded-pill" data-bs-toggle="modal"
                                data-bs-target="#addMemberModal">
                                <i class="bi bi-person-plus me-2"></i>Thêm thành viên
                            </button>
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
                                        @php $state = $m->status ?? 'ACTIVE'; @endphp
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
                                            @if($role !== 'OWNER') {{-- không cho xoá Owner --}}
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-danger rounded-pill px-2 kebab"
              data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác">
        <i class="bi bi-three-dots-vertical"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li class="dropdown-header">Thành viên</li>
        <li>
          <form method="POST"
                action="{{ route('company.members.remove', ['org' => $org->org_id, 'account' => $m->account_id]) }}"
                onsubmit="return confirm('Xoá {{ $name ?? $m->email }} khỏi tổ chức?');">
            @csrf @method('DELETE')
            <button type="submit" class="dropdown-item text-danger">
              <i class="bi bi-trash me-2"></i>Xoá khỏi tổ chức
            </button>
          </form>
        </li>
      </ul>
    </div>
  @endif
                                        </div>

                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center text-secondary py-5">
                                    Chưa có thành viên nào ngoài chủ tổ chức.
                                </div>
                            @endforelse

                            {{-- LỜI MỜI ĐANG CHỜ NGAY DƯỚI DANH SÁCH THÀNH VIÊN --}}
                            @if(!empty($pendingInvites) && $pendingInvites->isNotEmpty())
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

    {{-- MODAL: Thêm thành viên bằng username --}}
    @if(!empty($org))
        <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content rounded-4">
                    <form method="POST" action="{{ route('company.members.invite') }}">
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
                                <input type="text" name="username" class="form-control" required placeholder="vd: nguyenvana"
                                    value="{{ old('username') }}">
                            </div>
                            <div class="form-text">Người được mời cần xác nhận qua email. Quyền mặc định:
                                <strong>Member</strong>.</div>
                            @error('username') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <button class="btn btn-primary rounded-pill px-4" type="submit">Gửi lời mời</button>
                            <button class="btn btn-outline-secondary rounded-pill" type="button"
                                data-bs-dismiss="modal">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

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
        </style>
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
        </script>
    @endpush

@endsection