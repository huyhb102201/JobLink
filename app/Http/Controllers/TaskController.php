<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * Nộp file (chỉ lưu tên file, không lưu vật lý)
     */
    public function submit(Request $request, Task $task)
    {
        $userId = Auth::id();

        if ($task->assigned_to != $userId) {
            abort(403, 'Bạn không được phép nộp task này.');
        }

        $now = now();
        $start = $task->start_date ? Carbon::parse($task->start_date) : null;
        $end = $task->due_date ? Carbon::parse($task->due_date) : null;

        if ($start && $end && !$now->between($start, $end)) {
            return redirect()->back()->with('error', 'Task chưa đến thời gian nộp hoặc đã hết hạn.');
        }

        // Lưu tên file
        if ($request->hasFile('files')) {
            $fileNames = [];
            foreach ($request->file('files') as $file) {
                $fileNames[] = $file->getClientOriginalName();
            }

            $fileList = implode('|', $fileNames);

            // Cập nhật toàn bộ nhóm task cùng job_id + task_id
            Task::where('job_id', $task->job_id)
                ->where('task_id', $task->task_id)
                ->update(['file_path' => $fileList]);

            return redirect()->back()->with('success', 'Đã cập nhật danh sách file cho nhóm task!');
        }

        return redirect()->back()->with('error', 'Vui lòng chọn file để nộp.');
    }

    /**
     * “Thư mục ảo” hiển thị tất cả file của job đó
     */
    public function getVirtualDrive($jobId, $taskId = null)
    {
        $query = Task::where('job_id', $jobId);

        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        $tasks = $query->get();
        $fileList = collect($tasks)->pluck('file_path')->filter()->implode('|');
        $files = collect(explode('|', $fileList))->filter()->unique();

        return response()->json([
            'files' => $files->values(),
            'taskId' => $taskId,
            'jobId' => $jobId,
        ]);
    }

}
