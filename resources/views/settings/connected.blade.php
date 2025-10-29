@extends('settings.layout')
@section('title', 'Dịch vụ đã liên kết')

@section('settings_content')
    <div class="card border-0 shadow-sm" style="margin-bottom:200px;">
        <div class="card-body">
            <h5 class="mb-1">Dịch vụ đã liên kết</h5>
            <div class="text-muted mb-4">Kết nối tài khoản bên thứ ba để tăng cơ hội của bạn.</div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @elseif(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="list-group list-group-flush">
                {{-- GitHub --}}
                <div class="list-group-item d-flex align-items-center justify-content-between px-0">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-github fs-3"></i>
                        <div>
                            <div class="fw-semibold">GitHub</div>
                            @if($linked['github'])
                                <div class="small text-muted">
                                    Đã liên kết:
                                    <a href="{{ $linked['github']->nickname }}" target="_blank" rel="noopener">
                                        {{ $linked['github']->nickname }}
                                    </a>
                                </div>
                            @else
                                <div class="small text-muted">Chưa liên kết</div>
                            @endif

                        </div>
                    </div>
                    <div>
                        @if($linked['github'])
                            <form method="POST" action="{{ route('settings.connected.unlink', 'github') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">
                                    Hủy liên kết
                                </button>
                            </form>
                        @else
                            <a href="{{ route('oauth.redirect', 'github') }}?mode=link" class="btn btn-dark btn-sm">
                                <i class="bi bi-github me-1"></i> Liên kết GitHub
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Facebook --}}
                    
                </div>
            </div>


        </div>
    </div>
@endsection