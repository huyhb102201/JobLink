<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Cloudinary\Api\Upload\UploadApi;

class TaskController extends Controller
{
    /**
     * Upload file lên Cloudinary và lưu tên + URL (cách nhau bằng |)
     */
    public function submit(Request $request, Task $task)
    {
        $userId = Auth::id();

        if ($task->assigned_to != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không được phép nộp task này.'
            ], 403);
        }

        $now = now();
        $start = $task->start_date ? Carbon::parse($task->start_date) : null;
        $end = $task->due_date ? Carbon::parse($task->due_date) : null;

        if ($start && $end && !$now->between($start, $end)) {
            return response()->json([
                'success' => false,
                'message' => 'Task chưa đến thời gian nộp hoặc đã hết hạn.'
            ]);
        }

        $uploadApi = new UploadApi();

        // Lấy danh sách hiện tại từ DB
        $existingNames = $task->file_path ? explode('|', $task->file_path) : [];
        $existingUrls = $task->file_url ? explode('|', $task->file_url) : [];

        $newNames = [];
        $newUrls = [];
        $replaceList = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                $ext = $file->getClientOriginalExtension();

                // Nếu trùng tên → ghi đè
                if (in_array($originalName, $existingNames)) {
                    $replaceList[] = $originalName;
                }

                // ✅ Upload lên Cloudinary
                $res = $uploadApi->upload(
                    $file->getRealPath(),
                    [
                        'resource_type' => 'raw',
                        'public_id' => "uploads/files/{$nameOnly}",
                        'format' => $ext,
                        'overwrite' => true,
                    ]
                );

                $fileUrl = $res['secure_url'] ?? null;

                $newNames[] = $originalName;
                $newUrls[] = $fileUrl;
            }
        }

        // Xóa file cũ trùng tên
        if (!empty($replaceList)) {
            $filteredNames = [];
            $filteredUrls = [];
            foreach ($existingNames as $i => $name) {
                if (!in_array($name, $replaceList)) {
                    $filteredNames[] = $name;
                    $filteredUrls[] = $existingUrls[$i] ?? null;
                }
            }
            $existingNames = $filteredNames;
            $existingUrls = $filteredUrls;
        }

        // Gộp file cũ + mới
        $finalNames = array_merge($existingNames, $newNames);
        $finalUrls = array_merge($existingUrls, $newUrls);

        // ✅ Cập nhật DB
        Task::where('job_id', $task->job_id)
            ->where('task_id', $task->task_id)
            ->update([
                'file_path' => implode('|', $finalNames),
                'file_url' => implode('|', $finalUrls),
            ]);

        // Reload task để lấy data fresh (đảm bảo sync)
        $updatedTask = Task::where('job_id', $task->job_id)
            ->where('task_id', $task->task_id)
            ->first();

        $updatedUrls = $updatedTask->file_url ? explode('|', $updatedTask->file_url) : [];

        return response()->json([
            'success' => true,
            'message' => 'Upload thành công lên Cloudinary!',
            'uploaded_files' => array_map(function ($n, $u) {
                return ['file_name' => $n, 'url' => $u];
            }, $newNames, $newUrls),
            'updated_urls' => $updatedUrls,  // Full array URLs sau update
        ]);
    }

    /**
     * Xóa file khỏi danh sách (chỉ trong DB)
     */
    public function deleteFile(Request $request, Task $task)
    {
        $userId = Auth::id();

        if ($task->assigned_to != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa file.'
            ], 403);
        }

        $fileToDelete = $request->input('file');
        if (!$fileToDelete) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy file cần xóa.']);
        }

        $names = $task->file_path ? explode('|', $task->file_path) : [];
        $urls = $task->file_url ? explode('|', $task->file_url) : [];

        $filteredNames = [];
        $filteredUrls = [];

        foreach ($names as $i => $name) {
            if ($name !== $fileToDelete) {
                $filteredNames[] = $name;
                $filteredUrls[] = $urls[$i] ?? null;
            }
        }

        Task::where('job_id', $task->job_id)
            ->where('task_id', $task->task_id)
            ->update([
                'file_path' => implode('|', $filteredNames),
                'file_url' => implode('|', $filteredUrls),
            ]);

        // Reload task để lấy data fresh
        $updatedTask = Task::where('job_id', $task->job_id)
            ->where('task_id', $task->task_id)
            ->first();

        $updatedUrls = $updatedTask->file_url ? explode('|', $updatedTask->file_url) : [];

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa file khỏi task.',
            'updated_urls' => $updatedUrls,  // Full array URLs sau delete
        ]);
    }

    /**
     * Lấy danh sách file (tên + URL) cho job/task
     */
    public function getVirtualDrive($jobId, $taskId = null)
    {
        $query = Task::where('job_id', $jobId);
        if ($taskId)
            $query->where('task_id', $taskId);

        $tasks = $query->get();

        $files = [];
        foreach ($tasks as $t) {
            $names = $t->file_path ? explode('|', $t->file_path) : [];
            $urls = $t->file_url ? explode('|', $t->file_url) : [];
            foreach ($names as $i => $n) {
                $files[] = [
                    'file_name' => $n,
                    'url' => $urls[$i] ?? null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'jobId' => $jobId,
            'taskId' => $taskId,
            'files' => $files,
        ]);
    }

    /**
     * Download file từ Cloudinary URL (force download nếu cần)
     */
    public function downloadFile($filename)
    {
        // Giả sử bạn lưu full URL ở đâu đó, hoặc query từ DB
        // Ví dụ: lấy URL từ task gần nhất có file đó
        $task = Task::where('file_path', 'LIKE', '%' . $filename . '%')->first();
        if (!$task || !$task->file_url) {
            abort(404);
        }

        $urls = explode('|', $task->file_url);
        $url = null;
        foreach ($urls as $u) {
            if (basename($u) === $filename) {
                $url = $u;
                break;
            }
        }

        if (!$url)
            abort(404);

        // Redirect đến Cloudinary URL (nó sẽ auto download nếu là raw file)
        return redirect($url);
    }
}