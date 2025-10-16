<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
            return response()->json(['success' => false, 'message' => 'Bạn không được phép nộp task này.'], 403);
        }

        $now = now();
        $start = $task->start_date ? Carbon::parse($task->start_date) : null;
        $end = $task->due_date ? Carbon::parse($task->due_date) : null;

        if ($start && $end && !$now->between($start, $end)) {
            return response()->json(['success' => false, 'message' => 'Task chưa đến thời gian nộp hoặc đã hết hạn.']);
        }

        // Lấy existing file paths từ DB
        $existingFilePaths = $task->file_path ? explode('|', $task->file_path) : [];
        $existingBasenames = array_map(function ($path) {
            return basename($path);
        }, $existingFilePaths);

        // Xử lý file mới: chỉ lấy tên, không lưu vật lý
        $newFileNames = [];
        $toReplace = []; // Tên file cần thay thế (nếu duplicate)

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $newFileNames[] = $originalName;

                // Kiểm tra duplicate và đánh dấu thay thế
                if (in_array($originalName, $existingBasenames)) {
                    $toReplace[] = $originalName;
                }
            }
        }

        // Xóa các file cũ bị thay thế
        if (!empty($toReplace)) {
            $updatedExisting = [];
            foreach ($existingFilePaths as $path) {
                $name = basename($path);
                if (!in_array($name, $toReplace)) {
                    $updatedExisting[] = $path;
                }
            }
            $existingFilePaths = $updatedExisting;
        }

        // Append tên mới vào danh sách (không ghi đè toàn bộ)
        $allFilePaths = array_merge($existingFilePaths, $newFileNames);
        $fileList = implode('|', $allFilePaths);

        // Cập nhật toàn bộ nhóm task cùng job_id + task_id
        Task::where('job_id', $task->job_id)
            ->where('task_id', $task->task_id)
            ->update(['file_path' => $fileList]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật danh sách file cho nhóm task!',
            'new_paths' => $newFileNames, // Tên mới để FE biết
            'updated_paths' => $allFilePaths // Full list sau update để FE sync cache
        ]);
    }

    /**
     * Xóa file khỏi danh sách (chỉ xóa tên, không xóa vật lý)
     */
    public function deleteFile(Request $request, Task $task)
    {
        $userId = Auth::id();

        if ($task->assigned_to != $userId) {
            return response()->json(['success' => false, 'message' => 'Bạn không được phép xóa file của task này.'], 403);
        }

        $fileToDelete = $request->input('file');
        if (!$fileToDelete) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy file để xóa.']);
        }

        // Lấy existing file paths
        $existingFilePaths = $task->file_path ? explode('|', $task->file_path) : [];
        $updatedFilePaths = array_filter($existingFilePaths, function ($path) use ($fileToDelete) {
            return basename($path) !== basename($fileToDelete) && $path !== $fileToDelete;
        });

        $fileList = implode('|', $updatedFilePaths);

        // Cập nhật toàn bộ nhóm task cùng job_id + task_id
        Task::where('job_id', $task->job_id)
            ->where('task_id', $task->task_id)
            ->update(['file_path' => $fileList]);

        return response()->json([
            'success' => true,
            'message' => 'File đã được xóa khỏi danh sách.',
            'updated_paths' => array_values($updatedFilePaths)
        ]);
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