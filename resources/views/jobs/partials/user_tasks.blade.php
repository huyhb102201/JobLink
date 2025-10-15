@php
    use Carbon\Carbon;
    $now = Carbon::now();
    Carbon::setLocale('vi');
@endphp

{{-- TASK CỦA BẠN --}}
@if($userTasks->isEmpty())
    <div class="alert alert-light border text-muted py-2 px-3 mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Không có task nào được giao cho bạn trong công việc này.
    </div>
@else
    <div class="card card-body border-0 bg-light mb-4">
        <h6 class="fw-semibold mt-2 mb-3">
            <i class="bi bi-list-task me-1"></i> Task của bạn
        </h6>

        @foreach($userTasks as $group)
            @php
                $task = $group['main_task'];
                $otherAssignees = $group['other_assignees'];
                $start = $task->start_date ? Carbon::parse($task->start_date) : null;
                $end = $task->due_date ? Carbon::parse($task->due_date) : null;
                $inTimeRange = $start && $end && $now->between($start, $end);
                // Tính thời gian còn lại
                $timeLeft = null;
                if ($end && $end->greaterThan($now)) {
                    $diff = $now->diff($end);
                    $timeLeft = '';
                    if ($diff->d > 0)
                        $timeLeft .= $diff->d . ' ngày ';
                    if ($diff->h > 0)
                        $timeLeft .= $diff->h . ' giờ ';
                    if ($diff->i > 0 && $diff->d == 0)
                        $timeLeft .= $diff->i . ' phút';
                    $timeLeft = trim($timeLeft);
                }
            @endphp

            <div
                class="list-group-item list-group-item-action py-3 d-flex flex-column flex-md-row justify-content-between align-items-start">

                {{-- Thông tin chính của task --}}
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="fw-semibold text-dark">
                            <i class="bi bi-tags me-1"></i> {{ $task->title }}
                        </div>

                        @if($task->file_root)
                            <a href="{{ asset('storage/' . $task->file_root) }}" target="_blank"
                                class="badge bg-success-subtle text-success border border-success d-flex align-items-center gap-1 px-2 py-1 text-decoration-none">
                                <i class="bi bi-download"></i>
                                <span class="fw-semibold small">File gốc</span>
                            </a>
                        @endif
                    </div>

                    {{-- Thời gian --}}
                    <div class="small text-muted mt-1">
                        @if($start)
                            Bắt đầu: {{ $start->translatedFormat('d F, Y H:i') }}
                        @endif
                        @if($end)
                            · Hạn: {{ $end->translatedFormat('d F, Y H:i') }}
                        @endif
                        @if($timeLeft)
                            · Còn lại: {{ $timeLeft }}
                        @elseif($end && $end->lessThan($now))
                            · Quá hạn
                        @endif
                    </div>

                    {{-- Đồng hành --}}
                    @if($otherAssignees->isNotEmpty())
                        <div class="mt-2">
                            <div class="small text-muted mb-1">Đang làm cùng bạn:</div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                @foreach($otherAssignees->take(3) as $assignee)
                                    @if($assignee->assignee)
                                        @php
                                            $profile = $assignee->assignee->profile;
                                            $username = $profile->username ?? null;
                                        @endphp
                                        <a href="{{ $username ? route('portfolios.show', $username) : '#' }}" class="d-inline-block"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $assignee->assignee->name }}">
                                            <img src="{{ $assignee->assignee->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                                                alt="{{ $assignee->assignee->name }}" class="rounded-circle border border-light" width="32"
                                                height="32">
                                        </a>
                                    @endif
                                @endforeach
                                @if($otherAssignees->count() > 3)
                                    <span class="small text-muted">+{{ $otherAssignees->count() - 3 }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column align-items-md-end align-items-start gap-2 mt-2 mt-md-0 text-md-end">
                    <div>
                        @include('jobs.partials.task_badge', ['task' => $task])
                    </div>

                    <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                        <!-- Nút mở modal thư mục ảo -->
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal"
                            data-bs-target="#virtualDriveModal-{{ $task->job_id }}">
                            <i class="bi bi-folder me-1"></i> Mở thư mục ảo
                        </button>

                        @if($inTimeRange)
                            <!-- Nút mở modal nộp file -->
                            <button type="button" class="btn btn-sm btn-primary task-submit-btn" data-bs-toggle="modal"
                                data-bs-target="#submitTaskModal-{{ $task->id }}" data-task-id="{{ $task->id }}"
                                data-existing-files="{{ $task->file_path ? json_encode(explode('|', $task->file_path)) : '[]' }}">
                                <i class="bi bi-upload me-1"></i>
                                {{ $task->file_path ? 'Cập nhật' : 'Nộp ngay' }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Modal nộp file -->
            <div class="modal fade" id="submitTaskModal-{{ $task->id }}" tabindex="-1"
                aria-labelledby="submitTaskLabel-{{ $task->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content shadow-lg border-0">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="submitTaskLabel-{{ $task->id }}">
                                <i class="bi bi-upload text-primary me-2"></i>
                                {{ $task->file_path ? 'Cập nhật file' : 'Nộp file' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <form id="taskSubmitForm{{ $task->id }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <div class="dropzone border border-2 border-dashed rounded p-4 text-center position-relative"
                                    id="dropzone-{{ $task->id }}" role="button" tabindex="0"
                                    style="min-height: 200px; cursor: pointer;">
                                    <div class="dz-message">
                                        <i class="bi bi-cloud-upload text-muted" style="font-size: 2rem;"></i>
                                        <p class="mt-2">Kéo và thả file/thư mục vào đây hoặc nhấn để chọn</p>
                                        <p class="small text-muted">Tối đa 16MB, hỗ trợ file nén (.zip, .rar) và thư mục</p>
                                    </div>
                                    <input type="file" name="files[]" id="fileInput{{ $task->id }}" multiple
                                        accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" webkitdirectory
                                        style="position:absolute; inset:0; opacity:0; cursor:pointer;">
                                </div>
                                <div class="form-text text-muted mt-1">Chọn tối đa 10 file (jpg, png, pdf, zip, rar...)</div>
                                <div class="mt-3" id="filePreview-{{ $task->id }}">
                                    <!-- Hiển thị file đã upload khi cập nhật -->
                                    @if($task->file_path)
                                        <h6 class="fw-semibold mb-2">File đã upload:</h6>
                                        <ul class="list-group mb-3" id="existingFiles-{{ $task->id }}">
                                            @foreach(explode('|', $task->file_path) as $file)
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span>{{ $file }}</span>
                                                    <form action="#" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="file" value="{{ $file }}">
                                                        <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                                    </form>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <!-- Hiển thị preview file mới -->
                                    <h6 class="fw-semibold mb-2">File mới:</h6>
                                    <div class="d-flex flex-wrap gap-2 new-file-preview" id="newFilePreview-{{ $task->id }}">
                                    </div>
                                </div>
                                <div id="alertBox-{{ $task->id }}" class="mt-3"></div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-primary" id="submitTask-{{ $task->id }}">
                                {{ $task->file_path ? 'Cập nhật' : 'Nộp file' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- TASK KHÁC --}}
@if($otherTasks->isNotEmpty())
    <div class="card card-body border-0 bg-white">
        <h6 class="fw-semibold mt-2 mb-3">
            <i class="bi bi-people me-1"></i> Task khác trong công việc này
        </h6>

        @foreach($otherTasks as $group)
            @php
                $task = $group['other_assignees']->first();
                $assignees = $group['other_assignees'];
                $start = $task->start_date ? Carbon::parse($task->start_date) : null;
                $end = $task->due_date ? Carbon::parse($task->due_date) : null;
            @endphp

            <div
                class="list-group-item list-group-item-action py-3 d-flex flex-column flex-md-row justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">{{ $task->title }}</div>
                    <div class="small text-muted mt-1">
                        Người phụ trách: {{ $assignees->pluck('assignee.name')->filter()->join(', ') }}
                        @if($start)
                            · Bắt đầu: {{ $start->format('d/m/Y H:i') }}
                        @endif
                        @if($end)
                            · Hạn: {{ $end->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>

                <div class="mt-2 mt-md-0 text-md-end d-flex flex-column align-items-md-end align-items-start gap-2">
                    @include('jobs.partials.task_badge', ['task' => $task])
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Modal thư mục ảo --}}
<div class="modal fade" id="virtualDriveModal-{{ $task->job_id }}" tabindex="-1"
    aria-labelledby="virtualDriveLabel-{{ $task->job_id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="virtualDriveLabel-{{ $task->job_id }}">
                    <i class="bi bi-folder2-open text-warning me-2"></i>
                    Thư mục công việc #{{ $task->job_id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                @include('jobs.partials.virtual_drive', ['jobId' => $task->job_id])
            </div>
        </div>
    </div>
</div>

<style>
    .dropzone {
        border-style: dashed;
        border-color: #ccc;
        transition: all 0.25s ease;
    }

    .dropzone.dragover {
        background-color: #f8f9fa;
        border-color: #007bff;
        box-shadow: 0 0 0.5rem rgba(0, 123, 255, 0.3);
    }

    .new-file-preview .thumb {
        position: relative;
        width: 90px;
        height: 90px;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 2px dashed #007bff;
        transition: transform 0.2s;
    }

    .new-file-preview .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .new-file-preview .thumb:hover {
        transform: scale(1.05);
    }

    .new-file-preview .thumb .remove-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        border: none;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 16px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .new-file-preview .thumb:hover .remove-btn {
        opacity: 1;
    }
</style>

<script>
    $(function () {
        const MAX_FILES = 10;

        @foreach($userTasks as $group)
            @php $task = $group['main_task']; @endphp
            const taskId{{ $task->id }} = '{{ $task->id }}';
            const dropzone{{ $task->id }} = $('#dropzone-{{ $task->id }}');
            const fileInput{{ $task->id }} = $('#fileInput{{ $task->id }}');
            const preview{{ $task->id }} = $('#newFilePreview-{{ $task->id }}');
            const alertBox{{ $task->id }} = $('#alertBox-{{ $task->id }}');
            let selectedFiles{{ $task->id }} = [];

            // Xử lý khi mở modal
            $('.task-submit-btn[data-task-id="{{ $task->id }}"]').on('click', function () {
                $('#taskSubmitForm{{ $task->id }}')[0].reset();
                selectedFiles{{ $task->id }} = [];
                preview{{ $task->id }}.empty();
                alertBox{{ $task->id }}.empty();
                console.log('Opening modal for task {{ $task->id }}');
            });

            // Hiển thị preview file
            function renderPreview{{ $task->id }}() {
                preview{{ $task->id }}.empty();
                selectedFiles{{ $task->id }}.forEach((file, i) => {
                    const thumb = $('<div class="thumb" data-index="' + i + '"></div>');
                    if (file.type.startsWith('image/')) {
                        const url = URL.createObjectURL(file);
                        thumb.append(`<img src="${url}" alt="${file.name}">`);
                    } else {
                        thumb.append(`<span class="d-flex align-items-center justify-content-center h-100 text-muted">${file.name}</span>`);
                    }
                    thumb.append(`<button type="button" class="remove-btn">&times;</button>`);
                    thumb.find('.remove-btn').on('click', (e) => {
                        e.stopPropagation();
                        console.log(`Removing file ${file.name} from task {{ $task->id }}`);
                        selectedFiles{{ $task->id }}.splice(i, 1);
                        renderPreview{{ $task->id }}();
                    });
                    preview{{ $task->id }}.append(thumb);
                });
                console.log(`Rendered ${selectedFiles{{ $task->id }}.length} files for task {{ $task->id }}`);
            }

            // Thêm file
            function addFiles{{ $task->id }}(files) {
                console.log(`Adding files for task {{ $task->id }}:`, files);
                const incoming = Array.from(files).filter(f => {
                    if (f.size > 16 * 1024 * 1024) {
                        showAlert{{ $task->id }}(`File ${f.name} vượt quá giới hạn 16MB!`, 'danger');
                        return false;
                    }
                    return true;
                });
                if (selectedFiles{{ $task->id }}.length + incoming.length > MAX_FILES) {
                    showAlert{{ $task->id }}(`Chỉ được chọn tối đa ${MAX_FILES} file.`, 'danger');
                    return;
                }
                selectedFiles{{ $task->id }} = selectedFiles{{ $task->id }}.concat(incoming);
                renderPreview{{ $task->id }}();
            }

            // Hiển thị thông báo
            function showAlert{{ $task->id }}(msg, type = 'danger') {
                alertBox{{ $task->id }}.html(`<div class="alert alert-${type}">${msg}</div>`);
                console.log(`Alert for task {{ $task->id }}: ${msg}`);
            }

            // Kéo thả file
            dropzone{{ $task->id }}.on('dragover', (e) => {
                e.preventDefault();
                dropzone{{ $task->id }}.addClass('dragover');
                console.log('Dragover on dropzone {{ $task->id }}');
            });

            dropzone{{ $task->id }}.on('dragleave drop', (e) => {
                e.preventDefault();
                dropzone{{ $task->id }}.removeClass('dragover');
                console.log('Dragleave/drop on dropzone {{ $task->id }}');
            });

            dropzone{{ $task->id }}.on('drop', (e) => {
                const files = e.originalEvent.dataTransfer.files;
                console.log('Files dropped for task {{ $task->id }}:', files);
                addFiles{{ $task->id }}(files);
            });

            // Nhấn để chọn file
            dropzone{{ $task->id }}.on('click', () => {
                console.log('Dropzone clicked for task {{ $task->id }}');
                fileInput{{ $task->id }}.click();
            });

            // Chọn file qua input
            fileInput{{ $task->id }}.on('change', function () {
                console.log('Files selected for task {{ $task->id }}:', this.files);
                addFiles{{ $task->id }}(this.files);
                $(this).val('');
            });

            // Gửi form
            $('#submitTask-{{ $task->id }}').on('click', function () {
                console.log(`Submitting files for task {{ $task->id }}:`, selectedFiles{{ $task->id }});
                if (selectedFiles{{ $task->id }}.length === 0) {
                    showAlert{{ $task->id }}('Vui lòng chọn ít nhất một file để nộp!');
                    return;
                }

                const formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('task_id', taskId{{ $task->id }});
                selectedFiles{{ $task->id }}.forEach(f => formData.append('files[]', f));

                alertBox{{ $task->id }}.html(`
                    <div class="d-flex align-items-center justify-content-center my-3 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang gửi file...
                    </div>
                `);

                $.ajax({
                    url: '{{ route('tasks.submit', $task->id) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        console.log('Submit response for task {{ $task->id }}:', res);
                        if (res.success) {
                            showAlert{{ $task->id }}('File đã được nộp thành công!', 'success');
                            setTimeout(() => $('#submitTaskModal-{{ $task->id }}').modal('hide'), 1500);
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showAlert{{ $task->id }}(res.message || 'Không thể nộp file.');
                        }
                    },
                    error: function (xhr) {
                        console.error('Submit error for task {{ $task->id }}:', xhr);
                        showAlert{{ $task->id }}('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                    }
                });
            });
        @endforeach
});
</script>