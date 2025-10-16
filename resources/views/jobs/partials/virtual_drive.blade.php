{{-- jobs/partials/virtual_drive.blade.php --}}
@php
    $tasks = \App\Models\Task::where('job_id', $jobId)->get()->groupBy('task_id');
@endphp

<div class="container py-3">
    @forelse($tasks as $taskId => $group)
        @php
            $mainTask = $group->first();
            $fileList = collect($group)->pluck('file_url')->filter()->implode('|');
            $files = collect(explode('|', $fileList))->filter()->unique();
        @endphp

        <div class="card mb-3 shadow-sm virtual-task" data-task-id="{{ $mainTask->id }}">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div><strong>{{ $mainTask->title }}</strong><i> #{{ $mainTask->task_id }}</i></div>
                <small class="text-muted">{{ $files->count() }} file</small>
            </div>

            <div class="card-body">
                @if($files->isEmpty())
                    <div class="text-muted fst-italic">Chưa có file nào được nộp.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($files as $file)
                            @php
                                $basename = basename($file);
                                $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                                $icon = match($ext) {
                                    'rar', 'zip' => 'file-earmark-zip',
                                    'jpg', 'jpeg', 'png', 'gif', 'webp' => 'file-earmark-image',
                                    default => 'file-earmark',
                                };
                                $iconColor = match($ext) {
                                    'rar', 'zip' => 'text-primary',
                                    'jpg', 'jpeg', 'png', 'gif', 'webp' => 'text-success',
                                    default => 'text-muted',
                                };
                                $extUpper = $ext ? strtoupper($ext) : 'FILE';
                            @endphp
                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="bi bi-{{ $icon }} me-2 {{ $iconColor }}"></i>
                                    <a href="{{ $file }}" download="{{ $basename }}" class="text-decoration-none text-dark">{{ $basename }}</a>
                                </div>
                                <span class="badge bg-primary-subtle text-primary">{{ $extUpper }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-light border">Không có task nào trong công việc này.</div>
    @endforelse
</div>