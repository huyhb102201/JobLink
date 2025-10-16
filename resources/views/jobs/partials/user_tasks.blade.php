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
                $inTimeRange = $start && $end ? $now->between($start, $end) : true;

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
                {{-- Thông tin chính --}}
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
                        @if($start) Bắt đầu: {{ $start->translatedFormat('d F, Y H:i') }} @endif
                        @if($end) · Hạn: {{ $end->translatedFormat('d F, Y H:i') }} @endif
                        @if($timeLeft)
                            · Còn lại: {{ $timeLeft }}
                        @elseif($end && $end->lessThan($now))
                            · Quá hạn
                        @endif
                    </div>
                    <style>
                        .task-description {
                            width: 65%;
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: normal;
                            transition: all 0.3s ease-in-out;
                        }

                        @media (max-width: 768px) {
                            .task-description {
                                width: 100%;
                            }
                        }

                        /* Khi hover vào icon => mở rộng toàn bộ mô tả */
                        .task-description:hover {
                            -webkit-line-clamp: unset;
                            overflow: visible;
                            background-color: var(--bs-light);
                            /* dùng màu nền nhạt của Bootstrap */
                            padding: 0.25rem 0.5rem;
                            border-radius: 0.25rem;
                            box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
                        }
                    </style>

                    <div class="small text-muted mt-1 d-flex align-items-start">
                        <i class="bi bi-info-circle me-1 mt-1"></i>
                        <div class="task-description">
                            @if($task->description)
                                {{ $task->description }}
                            @else
                                Không có mô tả thêm
                            @endif
                        </div>
                    </div>



                    {{-- Người cùng làm --}}
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

                {{-- Badge & nút --}}
                <div class="d-flex flex-column align-items-md-end align-items-start gap-2 mt-2 mt-md-0 text-md-end">
                    <div>@include('jobs.partials.task_badge', ['task' => $task])</div>

                    <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                        {{-- Nút mở thư mục ảo (modal mức Job) --}}
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal"
                            data-bs-target="#virtualDriveModal-{{ $task->job_id }}">
                            <i class="bi bi-folder me-1"></i> Mở thư mục
                        </button>

                        {{-- Nút mở modal nộp file --}}
                        <button type="button" class="btn btn-sm btn-primary task-submit-btn" data-bs-toggle="modal"
                            data-bs-target="#submitTaskModal-{{ $task->id }}" data-task-id="{{ $task->id }}">
                            <i class="bi bi-upload me-1"></i>
                            {{ $task->file_url ? 'Cập nhật' : 'Nộp file' }}  {{-- Dùng file_url thay file_path --}}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal nộp file --}}
            <div class="modal fade" id="submitTaskModal-{{ $task->id }}" tabindex="-1"
                aria-labelledby="submitTaskLabel-{{ $task->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content shadow-lg border-0">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="submitTaskLabel-{{ $task->id }}">
                                <i class="bi bi-upload text-primary me-2"></i>
                                {{ $task->file_url ? 'Cập nhật file' : 'Nộp file' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div id="alertBox-{{ $task->id }}"></div>

                            <form id="taskSubmitForm{{ $task->id }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">

                                <div class="dropzone border border-2 border-dashed rounded p-4 text-center position-relative"
                                    id="dropzone-{{ $task->id }}" role="button" tabindex="0"
                                    style="min-height: 200px; cursor: pointer;">
                                    <div class="dz-message">
                                        <i class="bi bi-cloud-upload text-muted" style="font-size: 2rem;"></i>
                                        <p class="mt-2">Kéo & thả tệp vào đây hoặc nhấn để chọn</p>
                                        <p class="small text-muted">Tối đa 16MB/tệp; cho phép: .rar, .zip, .jpg, .jpeg, .png,
                                            .gif, .webp</p>
                                    </div>
                                    <input type="file" name="files[]" id="fileInput{{ $task->id }}" multiple
                                        accept=".rar,.zip,.jpg,.jpeg,.png,.gif,.webp"
                                        style="position:absolute; inset:0; opacity:0; cursor:pointer; z-index: 10;">
                                </div>

                                <div class="form-text text-muted mt-1">Chọn tối đa 10 tệp.</div>

                                <div class="mt-3" id="filePreview-{{ $task->id }}">
                                    <h6 class="fw-semibold mb-2">Danh sách file:</h6>
                                    <div class="d-flex flex-wrap gap-2 unified-file-preview"
                                        id="unifiedFilePreview-{{ $task->id }}">
                                    </div>
                                </div>
                            </form>

                            {{-- Inline confirm UI (ẩn ban đầu) --}}
                            <div id="inlineConfirm-{{ $task->id }}"
                                class="alert alert-warning d-none mt-3 p-4 text-center border-0" role="alert">
                                <i class="bi bi-exclamation-triangle mb-3 d-block" style="font-size: 2rem;"></i>
                                <p class="mb-4">Bạn có file mới chưa nộp. Đóng sẽ mất file tạm. Tiếp tục?</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-outline-secondary confirm-no">Hủy</button>
                                    <button type="button" class="btn btn-danger confirm-yes">Tiếp tục</button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="closeModalBtn-{{ $task->id }}">Đóng</button>
                            <button type="button" class="btn btn-primary" id="submitTask-{{ $task->id }}">
                                {{ $task->file_url ? 'Cập nhật' : 'Nộp file' }}
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
                        @if($start) · Bắt đầu: {{ $start->format('d/m/Y H:i') }} @endif
                        @if($end) · Hạn: {{ $end->format('d/m/Y H:i') }} @endif
                    </div>
                </div>
                <div class="mt-2 mt-md-0 text-md-end d-flex flex-column align-items-md-end align-items-start gap-2">
                    @include('jobs.partials.task_badge', ['task' => $task])
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Modal thư mục ảo mức Job --}}
@php
    $jobIdForDrive = isset($job) ? $job->id : ($jobId ?? ($userTasks->first()['main_task']->job_id ?? null));
@endphp
@if($jobIdForDrive)
    <div class="modal fade" id="virtualDriveModal-{{ $jobIdForDrive }}" tabindex="-1"
        aria-labelledby="virtualDriveLabel-{{ $jobIdForDrive }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="virtualDriveLabel-{{ $jobIdForDrive }}">
                        <i class="bi bi-folder2-open text-warning me-2"></i>
                        Thư mục công việc #{{ $jobIdForDrive }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="virtualDriveContent-{{ $jobIdForDrive }}">
                        @include('jobs.partials.virtual_drive', ['jobId' => $jobIdForDrive])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Styles --}}
<style>
    .dropzone {
        border-style: dashed;
        border-color: #ccc;
        transition: all .25s ease;
        user-select: none;
    }

    .dropzone.dragover {
        background-color: #f8f9fa;
        border-color: #0d6efd;
        box-shadow: 0 0 .5rem rgba(13, 110, 253, .3);
    }

    .unified-file-preview .thumb {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: .5rem;
        overflow: hidden;
        border: 2px solid transparent;
        transition: all .2s ease;
        cursor: pointer;
    }

    .unified-file-preview .thumb.existing {
        border-color: #6c757d;
        /* Màu xám cho file đã upload */
    }

    .unified-file-preview .thumb.new {
        border-color: #0d6efd;
        /* Màu xanh cho file mới */
    }

    .unified-file-preview .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .unified-file-preview .thumb:hover {
        transform: scale(1.03);
        box-shadow: 0 0 .5rem rgba(0, 0, 0, .1);
    }

    .unified-file-preview .thumb .file-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, .7);
        color: white;
        padding: 4px 6px;
        font-size: 0.75rem;
        text-align: center;
        line-height: 1.2;
    }

    .unified-file-preview .thumb .file-badge {
        position: absolute;
        top: 4px;
        left: 4px;
        background: rgba(255, 255, 255, .8);
        color: #000;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 0.7rem;
        font-weight: bold;
    }

    .unified-file-preview .thumb.existing .file-badge {
        background: #6c757d;
        color: white;
    }

    .unified-file-preview .thumb.new .file-badge {
        background: #0d6efd;
        color: white;
    }

    /* Nút download mới cho existing files */
    .unified-file-preview .thumb .download-btn {
        position: absolute;
        bottom: 4px;
        left: 4px;
        background: rgba(25, 135, 84, .8);  /* Xanh lá cho download */
        color: #fff;
        border: none;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 12px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all .2s ease;
        cursor: pointer;
        z-index: 3;
    }

    .unified-file-preview .thumb:hover .download-btn {
        opacity: 1;
    }

    .unified-file-preview .thumb .remove-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(220, 53, 69, .8);
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
        transition: all .2s ease;
        cursor: pointer;
        z-index: 3;
    }

    .unified-file-preview .thumb:hover .remove-btn {
        opacity: 1;
    }

    /* Hiệu ứng loading cho remove-btn */
    .unified-file-preview .thumb .remove-btn.loading {
        background: rgba(108, 117, 125, .8);
        cursor: not-allowed;
        opacity: 1;
    }

    .unified-file-preview .thumb .remove-btn.loading::after {
        content: '';
        position: absolute;
        width: 12px;
        height: 12px;
        border: 2px solid transparent;
        border-top: 2px solid #fff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .unified-file-preview .thumb.deleting {
        opacity: 0.5;
        transition: opacity .2s ease;
    }

    .unified-file-preview .thumb .icon-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        padding: 10px;
        text-align: center;
        background: #f8f9fa;
        color: #6c757d;
    }

    .unified-file-preview .thumb .icon-placeholder i {
        font-size: 24px;
        margin-bottom: 4px;
    }

    .unified-file-preview .thumb .icon-placeholder .name {
        font-size: 0.8rem;
        font-weight: 500;
        margin-bottom: 2px;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        max-width: 90px;
    }

    .unified-file-preview .thumb .icon-placeholder .size {
        font-size: 0.7rem;
    }

    .has-unsaved-files .modal-header {
        border-bottom: 2px solid #ffc107;
    }

    .unsaved-indicator {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ffc107;
        color: #000;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: bold;
    }

    /* Toast cho thông báo global đẹp */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1090;
    }

    .toast {
        min-width: 300px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15);
        border: none;
        border-radius: 8px;
    }

    /* Inline confirm style */
    [id^="inlineConfirm-"] {
        border-left: 4px solid #ffc107;
        background: #fff3cd;
    }

    [id^="inlineConfirm-"] .confirm-yes,
    [id^="inlineConfirm-"] .confirm-no {
        min-width: 80px;
    }

    /* Alert in modal top */
    .modal-body>[id^="alertBox-"] .alert {
        border-radius: 8px;
        margin-bottom: 1rem;
    }
</style>

{{-- Toast container global --}}
<div class="toast-container">
</div>

{{-- JS kéo-thả + preview + submit (per-task) --}}
<script>
    $(function () {
        const MAX_FILES = 10;
        const MAX_SIZE = 16 * 1024 * 1024; // 16MB
        const ALLOWED_EXT = ['rar', 'zip', 'jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Global cache cho files existing per task (không reload trang, update từ AJAX) - lưu full URL
        window.taskFilesCache = window.taskFilesCache || {};
        // Init cache từ server data (sử dụng file_url thay vì file_path)
        @foreach($userTasks as $group)
            @php
                $task = $group['main_task'];
                $existingFiles = $task->file_url ? explode('|', $task->file_url) : [];
            @endphp
            window.taskFilesCache[{{ $task->id }}] = @json($existingFiles);
            console.log('Init cache for task {{ $task->id }}:', window.taskFilesCache[{{ $task->id }}]);  // Debug
        @endforeach

            // Hàm update cache và re-render virtual drive nếu đang mở
            function updateVirtualDriveCache(taskId, newFiles, deletedFile = null) {
                let currentFiles = window.taskFilesCache[taskId] || [];

                if (deletedFile) {
                    currentFiles = currentFiles.filter(f => f !== deletedFile);
                }
                if (newFiles && newFiles.length > 0) {
                    currentFiles = currentFiles.concat(newFiles);
                }
                window.taskFilesCache[taskId] = currentFiles;
                console.log('Updated cache for task', taskId, ':', currentFiles);  // Debug

                // Re-render virtual drive nếu modal đang mở
                const $virtualModal = $(`#virtualDriveModal-{{ $jobIdForDrive ?? 0 }}`);
                if ($virtualModal.hasClass('show')) {
                    // Trigger custom event để partial virtual_drive listen và re-render
                    $(document).trigger('taskFilesUpdated', { taskId: taskId, files: currentFiles });
                }

                // Cập nhật count badge nếu có
                const $btnOpen = $(`.task-submit-btn[data-task-id="${taskId}"]`);
                const hasFiles = currentFiles.length > 0;
                $btnOpen.html(`<i class="bi bi-upload me-1"></i>${hasFiles ? 'Cập nhật' : 'Nộp file'}`);
            }

        // Global toast function thay alert
        function showToast(message, type = 'danger') {
            const bgClass = type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning' : type === 'info' ? 'bg-info' : 'bg-danger';
            const icon = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'info' ? 'info-circle' : 'x-circle';
            const toastHtml = `
                <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center"><i class="bi bi-${icon} me-2"></i>${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            $('.toast-container').append(toastHtml);
            const toastEl = $('.toast-container .toast:last-child')[0];
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }

        function isAllowed(file) {
            const name = file.name || '';
            const ext = (name.split('.').pop() || '').toLowerCase();
            if (!ALLOWED_EXT.includes(ext)) return false;
            if (file.size > MAX_SIZE) return false;
            return true;
        }

        function humanSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        // Helper để lưu/load buffer từ localStorage
        function getStorageKey(taskId) {
            return `task_files_buffer_${taskId}`;
        }

        function saveBuffer(taskId, filesBuf) {
            const data = filesBuf.map(f => ({
                name: f.name,
                size: f.size,
                type: f.type,
                lastModified: f.lastModified
            }));
            localStorage.setItem(getStorageKey(taskId), JSON.stringify(data));
        }

        function loadBuffer(taskId) {
            const key = getStorageKey(taskId);
            const data = localStorage.getItem(key);
            if (!data) return [];

            try {
                const fileData = JSON.parse(data);
                return fileData.map(d => {
                    const file = new File([], d.name, { type: d.type, lastModified: d.lastModified });
                    file.size = d.size; // Set size manually
                    return file;
                });
            } catch (e) {
                console.warn('Invalid buffer data for task', taskId);
                localStorage.removeItem(key);
                return [];
            }
        }

        function clearBuffer(taskId) {
            localStorage.removeItem(getStorageKey(taskId));
        }

        // Global clear all buffers on page unload (F5 or close tab)
        function clearAllBuffers() {
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('task_files_buffer_')) {
                    localStorage.removeItem(key);
                }
            });
        }

        window.addEventListener('beforeunload', clearAllBuffers);

        function basename(path) {
            return path.split('/').pop();
        }

        function isImage(ext) {
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        }

        @foreach($userTasks as $group)
            @php
                $task = $group['main_task'];
                $jobStatus = $task->job->status ?? null;
                $start = $task->start_date ? Carbon::parse($task->start_date) : null;
                $end = $task->due_date ? Carbon::parse($task->due_date) : null;
                $now = Carbon::now();
                $inTimeRange = ($start && $end) ? $now->between($start, $end) : true;
                $jobCanSubmit = in_array($jobStatus, ['open', 'in_progress']);
                $canSubmit = $jobCanSubmit && $inTimeRange;
                $existingFiles = $task->file_url ? explode('|', $task->file_url) : [];
                $existingBasenames = array_map(fn($f) => basename($f), $existingFiles);
            @endphp

                (function setupTask{{ $task->id }}() {
                    const taskId = {{ $task->id }};
                    let existingFiles = window.taskFilesCache[taskId] || []; // Lấy từ global cache (full URLs)
                    let existingBasenames = existingFiles.map(basename);
                    const canSubmit = {{ $canSubmit ? 'true' : 'false' }};

                    const $modal = $('#submitTaskModal-{{ $task->id }}');
                    const $dropzone = $('#dropzone-{{ $task->id }}');
                    const $input = $('#fileInput{{ $task->id }}');
                    const $preview = $('#unifiedFilePreview-{{ $task->id }}');
                    const $alertBox = $('#alertBox-{{ $task->id }}');
                    const $btnOpen = $('.task-submit-btn[data-task-id="{{ $task->id }}"]');
                    const $btnSubmit = $('#submitTask-{{ $task->id }}');
                    const $btnClose = $('#closeModalBtn-{{ $task->id }}');
                    const $modalTitle = $('#submitTaskLabel-{{ $task->id }}');
                    const $modalHeader = $modal.find('.modal-header');
                    const $inlineConfirm = $('#inlineConfirm-{{ $task->id }}');

                    let filesBuf = loadBuffer(taskId); // Load từ storage ngay từ đầu
                    let isDirty = filesBuf.length > 0; // Đánh dấu nếu có buffer
                    let isSubmitting = false; // Flag cho đang submit
                    let hideConfirmed = false; // Flag mới để tránh loop confirm
                    let lastToastTime = 0; // Timestamp để tránh spam toast trong cùng lần thao tác

                    function updateExistingBasenames() {
                        existingBasenames = existingFiles.map(basename);
                    }

                    function updateButtonTexts(hasExisting) {
                        const text = hasExisting ? 'Cập nhật' : 'Nộp file';
                        const titleText = hasExisting ? 'Cập nhật file' : 'Nộp file';
                        $btnOpen.html(`<i class="bi bi-upload me-1"></i>${text}`);
                        $btnSubmit.text(text);
                        $modalTitle.html(`<i class="bi bi-upload text-primary me-2"></i>${titleText}`);
                    }

                    function showAlert(msg, type = 'danger') {
                        clearAlert(); // Clear trước để tránh chồng
                        $alertBox.html(`<div class="alert alert-${type} py-2 mb-2 d-flex align-items-center"><i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'info' ? 'info-circle' : 'x-circle'} me-2"></i>${msg}</div>`);
                        $alertBox[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }

                    function clearAlert() {
                        $alertBox.empty();
                    }

                    function updateUnsavedIndicator() {
                        if (isDirty) {
                            $modalHeader.addClass('has-unsaved-files').append('<span class="unsaved-indicator">•</span>');
                        } else {
                            $modalHeader.removeClass('has-unsaved-files').find('.unsaved-indicator').remove();
                        }
                    }

                    function showInlineConfirm(onConfirm, onCancel) {
                        $inlineConfirm.removeClass('d-none').off('click').on('click', '.confirm-yes', function () {
                            $inlineConfirm.addClass('d-none');
                            onConfirm();
                        }).on('click', '.confirm-no', function () {
                            $inlineConfirm.addClass('d-none');
                            if (onCancel) onCancel();
                        });
                        $modal[0].scrollTo({ top: 0, behavior: 'smooth' });
                    }

                    function safeShowToast(message, type = 'warning') {
                        const now = Date.now();
                        if (now - lastToastTime < 500) return; // Tránh spam trong 500ms
                        lastToastTime = now;
                        showToast(message, type);
                    }

                    function removeFromBufferIfDuplicate(deletedName) {
                        const initialLength = filesBuf.length;
                        filesBuf = filesBuf.filter(f => f.name !== deletedName);
                        if (filesBuf.length < initialLength) {
                            saveBuffer(taskId, filesBuf);
                            isDirty = filesBuf.length > 0;
                            safeShowToast(`File tạm "${deletedName}" trong buffer đã được xóa tự động để tránh trùng lặp.`, 'info');
                            renderUnifiedPreview();
                            updateUnsavedIndicator();
                        }
                    }

                    function renderUnifiedPreview() {
                        $preview.empty();
                        // Render existing từ cache (full URLs)
                        existingFiles.forEach((url, i) => {
                            const name = basename(url);
                            const ext = (name.split('.').pop() || '').toLowerCase();
                            const $thumb = $('<div class="thumb existing"></div>');
                            $thumb.addClass(isImage(ext) ? '' : 'd-flex align-items-center justify-content-center');

                            if (isImage(ext)) {
                                $thumb.append(`<img src="${url}" alt="${name}" onerror="this.src='{{ asset('assets/img/defaultavatar.jpg') }}'">`);
                            } else {
                                $thumb.append(`
                                        <div class="icon-placeholder">
                                            <i class="bi bi-file-earmark-${ext === 'zip' || ext === 'rar' ? 'zip' : 'text'}"></i>
                                            <div class="name">${name}</div>
                                            <div class="size">Đã upload</div>
                                        </div>
                                    `);
                            }

                            $thumb.append(`<span class="file-badge">Cũ</span>`);

                            // Nút download cho existing (chỉ hiện khi hover)
                            const $dlBtn = $(`<button type="button" class="download-btn" title="Tải file"><i class="bi bi-download"></i></button>`);
                            $dlBtn.on('click', (e) => {
                                e.stopPropagation();
                                // Direct download từ Cloudinary URL
                                window.open(url, '_blank');  // Hoặc dùng route nếu có: window.location.href = '{{ route("tasks.files.download", ":filename") }}'.replace(':filename', name);
                            });
                            $thumb.append($dlBtn);

                            // Nút xóa
                            const $rm = $(`<button type="button" class="remove-btn" title="Xoá file cũ">&times;</button>`);
                            $rm.on('click', (e) => {
                                e.stopPropagation();
                                if (confirm(`Xoá file "${name}" đã upload?`)) {
                                    const deletedUrl = url;
                                    const deletedName = name;
                                    // Hiệu ứng loading
                                    $thumb.addClass('deleting');
                                    $rm.addClass('loading').prop('disabled', true).html('');

                                    // Gọi delete ajax (gửi full URL)
                                    $.ajax({
                                        url: '{{ route('tasks.files.delete', $task->id) }}',
                                        type: 'DELETE',
                                        data: {
                                            _token: $('meta[name="csrf-token"]').attr('content'),
                                            file: deletedName  // Gửi name thay vì full URL, vì Controller dùng name để filter
                                        },
                                        success: function (res) {
                                            $thumb.removeClass('deleting');
                                            $rm.removeClass('loading').prop('disabled', false).html('&times;');
                                            if (res.success) {
                                                // Sync cache từ server full list (full URLs)
                                                existingFiles = res.updated_urls || [];
                                                updateExistingBasenames();
                                                // Xóa khỏi buffer nếu có trùng tên
                                                removeFromBufferIfDuplicate(deletedName);
                                                renderUnifiedPreview();
                                                updateButtonTexts(existingFiles.length > 0);
                                                // Trigger virtual drive update
                                                $(document).trigger('taskFilesUpdated', { taskId: taskId, files: existingFiles });
                                                showToast('File đã được xoá thành công!', 'success');
                                            } else {
                                                showToast(res.message || 'Không thể xoá file.', 'danger');
                                            }
                                        },
                                        error: function (xhr) {
                                            $thumb.removeClass('deleting');
                                            $rm.removeClass('loading').prop('disabled', false).html('&times;');
                                            const msg = xhr?.responseJSON?.message || 'Có lỗi xảy ra khi xoá file.';
                                            showToast(msg, 'danger');
                                        }
                                    });
                                }
                            });
                            $thumb.append($rm);
                            if (isImage(ext)) {
                                $thumb.append(`<div class="file-info"><small>${name}</small></div>`);
                            }
                            $preview.append($thumb);
                        });

                        // Render new files (không có download btn)
                        filesBuf.forEach((file, i) => {
                            const ext = (file.name.split('.').pop() || '').toLowerCase();
                            const $thumb = $('<div class="thumb new"></div>');
                            $thumb.addClass(isImage(ext) ? '' : 'd-flex align-items-center justify-content-center');

                            if (isImage(ext)) {
                                const url = URL.createObjectURL(file);
                                $thumb.append(`<img src="${url}" alt="${file.name}">`);
                            } else {
                                $thumb.append(`
                                        <div class="icon-placeholder">
                                            <i class="bi bi-file-earmark-${ext === 'zip' || ext === 'rar' ? 'zip' : 'text'}"></i>
                                            <div class="name">${file.name}</div>
                                            <div class="size">${humanSize(file.size)}</div>
                                        </div>
                                    `);
                            }

                            $thumb.append(`<span class="file-badge">Mới</span>`);
                            const $rm = $(`<button type="button" class="remove-btn" title="Xoá file mới">&times;</button>`);
                            $rm.on('click', (e) => {
                                e.stopPropagation();
                                if (confirm(`Xoá file "${file.name}" khỏi danh sách?`)) {
                                    filesBuf.splice(i, 1);
                                    saveBuffer(taskId, filesBuf);
                                    isDirty = filesBuf.length > 0;
                                    renderUnifiedPreview();
                                    updateUnsavedIndicator();
                                }
                            });
                            $thumb.append($rm);
                            if (isImage(ext)) {
                                $thumb.append(`<div class="file-info"><small>${file.name}</small></div>`);
                            }
                            $preview.append($thumb);
                        });

                        // Check total
                        const total = existingFiles.length + filesBuf.length;
                        if (total > MAX_FILES) {
                            showAlert(`Tổng số file vượt quá ${MAX_FILES}. Vui lòng xoá bớt.`, 'warning');
                        } else {
                            clearAlert();
                        }

                        updateUnsavedIndicator();
                    }

                    async function addFiles(list) {
                        const incoming = Array.from(list);
                        let invalids = { badExt: 0, bigSize: 0 };
                        let bufferDups = [];
                        let overwriteCandidates = [];
                        const valid = [];

                        // Compute current basenames
                        updateExistingBasenames();

                        for (const f of incoming) {
                            if (!isAllowed(f)) {
                                const ext = (f.name.split('.').pop() || '').toLowerCase();
                                if (!ALLOWED_EXT.includes(ext)) invalids.badExt++;
                                else if (f.size > MAX_SIZE) invalids.bigSize++;
                                continue;
                            }

                            const lowerName = f.name.toLowerCase();
                            if (filesBuf.some(buf => buf.name.toLowerCase() === lowerName)) {
                                bufferDups.push(f.name);
                                continue;
                            } else if (existingBasenames.some(exist => exist.toLowerCase() === lowerName)) {
                                overwriteCandidates.push(f);
                                continue;
                            }

                            valid.push(f);
                        }

                        // Combine warnings for invalids and buffer dups into one toast
                        let warningMsgParts = [];
                        if (invalids.badExt || invalids.bigSize) {
                            let invalidMsg = [];
                            if (invalids.badExt) invalidMsg.push(`${invalids.badExt} tệp sai định dạng`);
                            if (invalids.bigSize) invalidMsg.push(`${invalids.bigSize} tệp vượt 16MB`);
                            warningMsgParts.push(`Các tệp không hợp lệ bị bỏ qua: ${invalidMsg.join(', ')}`);
                        }
                        if (bufferDups.length > 0) {
                            const dupMsg = bufferDups.length === 1 ? `File "${bufferDups[0]}" bị bỏ qua do trùng tên trong buffer.` : `Các file ${bufferDups.slice(0, 3).join(', ')}${bufferDups.length > 3 ? ` và ${bufferDups.length - 3} file khác` : ''} bị bỏ qua do trùng tên trong buffer.`;
                            warningMsgParts.push(dupMsg);
                        }
                        if (warningMsgParts.length > 0) {
                            safeShowToast(warningMsgParts.join('; '), 'warning');
                        }

                        // Handle overwrite confirm for existing dups
                        if (overwriteCandidates.length > 0) {
                            const overNames = overwriteCandidates.map(f => f.name).join(', ');
                            const overMsg = `Các file sau sẽ ghi đè file hiện có trong cơ sở dữ liệu: ${overNames}. Bạn có muốn tiếp tục?`;
                            if (confirm(overMsg)) {
                                valid = valid.concat(overwriteCandidates);
                            }
                            // else skip
                        }

                        // Add valid
                        if (existingFiles.length + filesBuf.length + valid.length > MAX_FILES) {
                            showAlert(`Tổng số file sẽ vượt quá ${MAX_FILES}. Vui lòng xoá bớt trước.`);
                            return;
                        }

                        filesBuf = filesBuf.concat(valid);
                        saveBuffer(taskId, filesBuf);
                        isDirty = true;
                        renderUnifiedPreview();
                    }

                    // Vô hiệu hóa nếu !canSubmit
                    if (!canSubmit) {
                        $btnOpen.prop('disabled', true).addClass('disabled')
                            .attr('title', 'Chưa đến thời gian nộp hoặc job không cho phép.');
                        $dropzone.addClass('opacity-50').css('pointer-events', 'none');
                    } else {
                        $dropzone.removeClass('opacity-50').css('pointer-events', '');
                    }

                    // Khi mở modal
                    $btnOpen.on('click', function () {
                        console.log('Mở modal cho task', taskId, 'cache:', existingFiles);  // Debug
                        if (!canSubmit) return;
                        filesBuf = loadBuffer(taskId); // Reload để chắc
                        existingFiles = window.taskFilesCache[taskId] || []; // Reload từ cache (full URLs)
                        updateExistingBasenames();
                        isSubmitting = false; // Reset flag
                        isDirty = filesBuf.length > 0;
                        hideConfirmed = false; // Reset flag confirm
                        lastToastTime = 0; // Reset toast throttle
                        updateButtonTexts(existingFiles.length > 0);
                        renderUnifiedPreview();
                        clearAlert();
                        $inlineConfirm.addClass('d-none'); // Ẩn confirm nếu có
                    });

                    // Bootstrap modal events cho cảnh báo đóng - dùng inline confirm
                    $modal.on('hide.bs.modal', function (e) {
                        if (!canSubmit) return;

                        if (isSubmitting) {
                            // Đang submit, hỏi có muốn đóng không
                            if (!hideConfirmed) {
                                showInlineConfirm(
                                    function () { // Yes: đóng modal
                                        hideConfirmed = true;
                                        showToast('Quá trình nộp file đang tiếp tục ngầm...', 'warning');
                                        $modal.modal('hide');
                                    },
                                    function () { // No: hủy đóng
                                        e.preventDefault();
                                    }
                                );
                                e.preventDefault();
                                return;
                            }
                        }

                        if (isDirty && !hideConfirmed) {
                            // Có file chưa nộp, show inline confirm
                            showInlineConfirm(
                                function () { // Yes: clear và đóng
                                    clearBuffer(taskId);
                                    filesBuf = [];
                                    isDirty = false;
                                    hideConfirmed = true;
                                    $modal.modal('hide');
                                },
                                function () { // No: hủy đóng
                                    e.preventDefault();
                                }
                            );
                            e.preventDefault();
                            return;
                        }
                        // Không có gì, đóng bình thường
                        hideConfirmed = false; // Reset cho lần sau
                    });

                    $btnClose.on('click', function () {
                        $modal.modal('hide');
                    });

                    // Drag & drop
                    $dropzone.on('dragover', (e) => {
                        if (!canSubmit || isSubmitting) return;
                        e.preventDefault();
                        e.stopPropagation();
                        $dropzone.addClass('dragover');
                    });
                    $dropzone.on('dragleave', (e) => {
                        if (!canSubmit || isSubmitting) return;
                        e.preventDefault();
                        e.stopPropagation();
                        $dropzone.removeClass('dragover');
                    });
                    $dropzone.on('drop', (e) => {
                        if (!canSubmit || isSubmitting) return;
                        e.preventDefault();
                        e.stopPropagation();
                        $dropzone.removeClass('dragover');
                        addFiles(e.originalEvent.dataTransfer.files);
                    });

                    // Click chọn file
                    $dropzone.on('click', (e) => {
                        if (!canSubmit || isSubmitting) return;
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        $input[0].focus();
                        $input[0].click();
                    });

                    // Change input
                    $input.on('change', function (e) {
                        if (!canSubmit || isSubmitting) return;
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        addFiles(this.files);
                        this.value = '';
                    });

                    $input[0].addEventListener('click', function (e) {
                        e.stopPropagation();
                    }, true);

                    // Submit - logic chống double click và handle abort
                    $btnSubmit.on('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (!canSubmit) {
                            showAlert('Chưa đến thời gian nộp hoặc job không cho phép.', 'warning');
                            return;
                        }
                        if (isSubmitting) return; // Tránh double submit
                        if (filesBuf.length === 0 && existingFiles.length === 0) {
                            showAlert('Vui lòng chọn ít nhất một tệp trước khi nộp.');
                            return;
                        }

                        if (filesBuf.length === 0) {
                            showToast('Không có file mới để cập nhật. Modal sẽ đóng.', 'info');
                            $modal.modal('hide');
                            return;
                        }

                        // Bắt đầu submit
                        isSubmitting = true;
                        $btnSubmit.prop('disabled', true).text('Đang nộp...');
                        showAlert('Đang gửi file mới...', 'info');

                        const formData = new FormData();
                        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                        formData.append('task_id', taskId);
                        filesBuf.forEach(f => formData.append('files[]', f));

                        const xhr = $.ajax({
                            url: '{{ route('tasks.submit', $task->id) }}',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (res) {
                                console.log('Submit response:', res);  // Debug
                                isSubmitting = false;
                                if (res.success) {
                                    showAlert('File đã được cập nhật thành công!', 'success');
                                    clearBuffer(taskId);
                                    filesBuf = [];
                                    isDirty = false;
                                    // Sync cache từ server full list (full URLs từ file_url)
                                    existingFiles = res.updated_urls || [];
                                    updateExistingBasenames();
                                    window.taskFilesCache[taskId] = existingFiles;  // Update global cache
                                    updateButtonTexts(existingFiles.length > 0);
                                    // Trigger virtual drive update
                                    $(document).trigger('taskFilesUpdated', { taskId: taskId, files: existingFiles });
                                    setTimeout(() => {
                                        $modal.modal('hide');
                                        // Không reload, chỉ update UI từ cache
                                        renderUnifiedPreview();
                                    }, 1500);
                                } else {
                                    $btnSubmit.prop('disabled', false).text(existingFiles.length > 0 ? 'Cập nhật' : 'Nộp file');
                                    showAlert(res.message || 'Không thể nộp file.');
                                }
                            },
                            error: function (xhr) {
                                isSubmitting = false;
                                $btnSubmit.prop('disabled', false).text(existingFiles.length > 0 ? 'Cập nhật' : 'Nộp file');
                                const status = xhr.status;
                                let msg = xhr?.responseJSON?.message || 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
                                if (status === 419) {
                                    msg = 'Phiên làm việc đã hết hạn. Vui lòng làm mới trang và thử lại.';
                                }
                                showAlert(msg);
                            }
                        });

                        // Nếu đóng modal trong lúc submit, có thể abort nếu muốn, nhưng để ngầm
                        $modal.one('hide.bs.modal', function (e) { // Use .one để tránh multiple
                            if (isSubmitting && !e.isDefaultPrevented()) {
                                // Không abort, chỉ warn
                                showToast('Quá trình nộp file đang tiếp tục ngầm...', 'warning');
                            }
                        });
                    });

                    // Init render khi load page nếu modal mở (rare case)
                    renderUnifiedPreview();
                    updateButtonTexts(existingFiles.length > 0);
                })();
        @endforeach

        // Listen event để update virtual drive partial (nếu modal đang mở)
        $(document).on('taskFilesUpdated', function (e, data) {
            console.log('Virtual drive updated:', data);  // Debug
            // Target per task card trong virtual drive
            const $taskEl = $(`#virtualDriveContent-{{ $jobIdForDrive ?? 0 }} .virtual-task[data-task-id="${data.taskId}"]`);
            if ($taskEl.length) {
                // Re-render list files từ data.files (full URLs)
                let html = '';
                if (data.files.length === 0) {
                    html = '<div class="text-muted fst-italic">Chưa có file nào được nộp.</div>';
                } else {
                    html = '<ul class="list-group list-group-flush">';
                    data.files.forEach(file => {
                        const name = basename(file);
                        html += `
                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                <div><i class="bi bi-file-earmark-text me-2 text-secondary"></i>${name}</div>
                                <span class="badge bg-secondary-subtle text-secondary">Ảo</span>
                            </li>
                        `;
                    });
                    html += '</ul>';
                }
                $taskEl.find('.card-body').html(html);
                $taskEl.find('.card-header small').text(`${data.files.length} file`);
            }
        });

        // Khi mở virtual drive modal, nếu cache có data, có thể load từ cache, nhưng vì partial server-render, event sẽ handle update sau
        $(document).on('shown.bs.modal', '#virtualDriveModal-{{ $jobIdForDrive ?? 0 }}', function () {
            // Trigger một event dummy để sync nếu cần, nhưng không cần vì PHP render fresh
        });
    });
</script>