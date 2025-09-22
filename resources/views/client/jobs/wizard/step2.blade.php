@extends('layouts.app')
@section('title', 'Tạo job · Bước 2')

@push('styles')
    {{-- Jodit Editor --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.8/build/jodit.min.css">
    <style>
        .wizard-wrap {
            max-width: 1100px;
            margin-top: 50px;
            margin-bottom: 120px
        }
    </style>
@endpush

@section('content')
    <div class="container wizard-wrap" style="margin-bottom:200px;">
        @include('client.jobs.wizard._progress', ['n' => $n, 'total' => $total])
        <div class="row mt-4">
            <div class="col-12">
                <h2 class="h3 fw-bold mb-1">Mô tả công việc</h2>
                <p class="text-secondary">Viết mô tả cơ bản & nội dung chi tiết (định dạng).</p>
            </div>

            <div class="col-12">
                <form action="{{ route('client.jobs.wizard.store', 2) }}" method="POST"
                    class="p-4 border rounded-3 bg-white shadow-sm">
                    @csrf

                    {{-- Hàng 1: mô tả cơ bản (trái) + danh mục (phải) --}}
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-8">
                            <label class="form-label fw-semibold">Mô tả cơ bản *</label>
                            <textarea name="description" rows="7"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Tóm tắt ngắn gọn">{{ old('description', $d['description'] ?? '') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Danh mục</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->category_id }}" @selected(old('category_id', $d['category_id'] ?? '') == $c->category_id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Hàng 2: editor full chiều ngang --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nội dung chi tiết</label>
                            <textarea id="detailEditor"
                                name="content">{!! old('content', $d['content'] ?? '') !!}</textarea>
                            <div class="form-text">Bạn có thể định dạng văn bản, chèn ảnh, link…</div>
                        </div>

                        {{-- Hàng 3: nút --}}
                        <div class="col-12 d-flex justify-content-between mt-1">
                            <a class="btn btn-link" href="{{ route('client.jobs.wizard.step', 1) }}">← Quay lại</a>
                            <button class="btn btn-primary">Tiếp tục</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jodit@3.24.8/build/jodit.min.js"></script>
    <script>
        new Jodit('#detailEditor', {
            height: 380,
            toolbarAdaptive: false,
            uploader: { insertImageAsBase64URI: true }, // demo nhanh; sau có thể thay bằng upload server
            buttons: [
                'undo', 'redo', '|', 'paragraph', 'bold', 'underline', 'italic', 'strikethrough', '|',
                'ul', 'ol', '|', 'align', '|', 'image', 'link', 'table', '|', 'eraser', 'fullscreen', 'source'
            ]
        });
    </script>
@endpush