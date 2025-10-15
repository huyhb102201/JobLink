@php
    $tasks = \App\Models\Task::where('job_id', $jobId)->get()->groupBy('task_id');
@endphp

<div class="container py-3">
    @forelse($tasks as $taskId => $group)
        @php
            $mainTask = $group->first();
            $fileList = collect($group)->pluck('file_path')->filter()->implode('|');
            $files = collect(explode('|', $fileList))->filter()->unique();
        @endphp

        <div class="card mb-3 shadow-sm">
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
                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                <div><i class="bi bi-file-earmark-text me-2 text-secondary"></i>{{ $file }}</div>
                                <span class="badge bg-secondary-subtle text-secondary">Ảo</span>
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
