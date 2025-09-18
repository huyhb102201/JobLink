@extends('layouts.app')
@section('title', 'Hồ sơ')

@section('content')
    <div class="container" style="max-width: 980px; margin-top:50px;">
        @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div> @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">

                {{-- Avatar --}}
                <div>
                    @php
                        $name = $account->name ?? 'User';
                        $initials = collect(explode(' ', $name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                    @endphp

                    @if($account->avatar_url)
                        <img src="{{ asset($account->avatar_url) }}" alt="avatar" class="rounded-circle"
                            style="width:84px;height:84px;object-fit:cover;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold"
                            style="width:84px;height:84px;font-size:28px;">{{ $initials }}</div>
                    @endif

                    <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" class="mt-2">
                        @csrf @method('PUT')
                        <label class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-camera"></i> Đổi ảnh
                            <input type="file" name="avatar" accept="image/*" hidden onchange="this.form.submit()">
                        </label>
                        @error('avatar') <div class="text-danger small">{{ $message }}</div> @enderror
                    </form>
                </div>

                {{-- Info --}}
                <div class="flex-grow-1">
                    <h4 class="mb-1">{{ $profile->fullname ?: $account->name }}</h4>
                    <div class="text-muted">
                        <i class="bi bi-envelope"></i> {{ $profile->email ?: $account->email }}
                    </div>
                </div>

                <a class="btn btn-outline-primary" href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="bi bi-pencil-square"></i> Chỉnh sửa hồ sơ
                </a>

            </div>
        </div>

        {{-- Hiển thị mô tả + kỹ năng --}}
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> Giới thiệu</h5>
                        <p class="mb-0 text-secondary">{{ $profile->description ?: 'Chưa có mô tả.' }}</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-stars"></i> Kỹ năng</h5>
                        @php $skills = $profile->skill_list; @endphp
                        @if(count($skills))
                            @foreach($skills as $s)
                                <span class="badge bg-light text-dark border me-1 mb-1">
                                    <i class="bi bi-hash"></i> {{ $s }}
                                </span>
                            @endforeach
                        @else
                            <span class="text-secondary">Chưa thêm kỹ năng.</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-3">Tài khoản</h6>
                        <ul class="list-unstyled vstack gap-2">
                            <li><i class="bi bi-person-badge me-2"></i> ID: {{ $account->account_id }}</li>
                            <li><i class="bi bi-person-fill me-2"></i> {{ $account->name }}</li>
                            <li><i class="bi bi-envelope me-2"></i> {{ $account->email }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form sửa --}}
    </div>
    <!-- Modal chỉnh sửa hồ sơ -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-gear"></i> Chỉnh sửa hồ sơ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body vstack gap-3">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ tên</label>
                                <input type="text" name="fullname" class="form-control"
                                    value="{{ old('fullname', $profile->fullname ?? $account->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $profile->email ?? $account->email) }}">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Mô tả (tối đa 255 ký tự)</label>
                            <input type="text" name="description" class="form-control"
                                value="{{ old('description', $profile->description) }}" placeholder="Giới thiệu ngắn...">
                        </div>

                        <div>
                            <label class="form-label">Kỹ năng (ngăn cách bằng dấu phẩy)</label>
                            <input type="text" name="skill" class="form-control" value="{{ old('skill', $profile->skill) }}"
                                placeholder="laravel, react, mysql">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button class="btn btn-primary"><i class="bi bi-save2"></i> Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection