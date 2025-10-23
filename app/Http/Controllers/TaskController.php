<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
     public function store(Request $r)
    {
        $data = $r->validate([
            'job_id'                 => 'required|integer|exists:jobs,job_id',
            'assignee_account_ids'   => 'required|array|min:1',
            'assignee_account_ids.*' => 'integer|exists:accounts,account_id',
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string|max:5000',
            'start_date'             => 'nullable|date',
            'due_date'               => 'nullable|date|after_or_equal:start_date',
        ]);

        $me  = $r->user();
        $job = DB::table('jobs')->select('job_id','account_id','escrow_status')
                ->where('job_id', $data['job_id'])->first();

        if (!$job || (int)$job->account_id !== (int)$me->account_id) {
            return $this->jsonOrBack($r, 403, 'Bạn không có quyền với job này.');
        }
        if (($job->escrow_status ?? 'pending') !== 'funded') {
            return $this->jsonOrBack($r, 422, 'Cần thanh toán cọc trước khi giao task.');
        }

        // chỉ cho người đã được nhận
        $acceptedIds   = DB::table('job_apply')
            ->where('job_id', $job->job_id)->where('status', 2)
            ->pluck('user_id')->all();

        $assignees       = array_values(array_unique($data['assignee_account_ids']));
        $validAssignees  = array_values(array_intersect($assignees, $acceptedIds));
        if (empty($validAssignees)) {
            return $this->jsonOrBack($r, 422, 'Chỉ có thể giao cho ứng viên đã được chấp nhận.');
        }

        // task_id kế tiếp trong job
        $nextTaskId = (int) DB::table('tasks')->where('job_id',$job->job_id)->max('task_id');
        $nextTaskId = $nextTaskId ? $nextTaskId + 1 : 1;

        $now = now();
        $common = [
            'job_id'      => $job->job_id,
            'task_id'     => $nextTaskId,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => 'in_progress',
            'start_date'  => $data['start_date'] ?? $now->toDateString(),
            'due_date'    => $data['due_date'] ?? null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];

        $rows = [];
        foreach ($validAssignees as $uid) {
            $rows[] = $common + ['assigned_to' => $uid];
        }
        DB::table('tasks')->insert($rows);

        // JSON payload để client cập nhật UI ngay
        $payload = [
            'task_id'   => $nextTaskId,
            'job_id'    => (int)$job->job_id,
            'assignees' => $validAssignees,
            'tasks'     => $rows,  // mỗi phần tử có assigned_to
        ];

        return $this->jsonOrBack(
            $r,
            200,
            "Đã giao task #{$nextTaskId} cho ".count($validAssignees)." người.",
            $payload
        );
    }

    public function extendDueDate(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'job_id' => [
                'required','integer',
                Rule::exists('jobs','job_id')->where(fn($q) => $q->where('account_id',$user->account_id)),
            ],
            'assignee_account_id' => ['required','integer', Rule::exists('accounts','account_id')],
            'task_id'      => ['required','integer'],
            'new_due_date' => ['required','date','after:today'],
        ]);

        $jobId      = (int)$data['job_id'];
        $assigneeId = (int)$data['assignee_account_id'];
        $taskId     = (int)$data['task_id'];
        $dueDate    = Carbon::parse($data['new_due_date'])->toDateString();

        $task = DB::table('tasks')
            ->where('task_id',$taskId)
            ->where('job_id',$jobId)
            ->where('assigned_to',$assigneeId)
            ->first();
        if (!$task) {
            return $this->jsonOrBack($request, 422, 'Task không thuộc job/assignee đã chọn.');
        }

        $isAccepted = DB::table('job_apply')
            ->where('job_id',$jobId)->where('user_id',$assigneeId)->where('status',2)
            ->exists();
        if (!$isAccepted) {
            return $this->jsonOrBack($request, 422, 'Freelancer này chưa được nhận cho job.');
        }

        DB::table('tasks')
            ->where('task_id',$taskId)->where('job_id',$jobId)->where('assigned_to',$assigneeId)
            ->update(['due_date'=>$dueDate,'updated_at'=>now()]);

        return $this->jsonOrBack(
            $request, 200,
            'Đã gia hạn task thành công.',
            ['task_id'=>$taskId,'job_id'=>$jobId,'assignee_account_id'=>$assigneeId,'new_due_date'=>$dueDate]
        );
    }

    // Helper: nếu request mong JSON (AJAX) thì trả JSON, ngược lại redirect kèm flash
    private function jsonOrBack(Request $r, int $status, string $message, array $payload = null)
    {
        if ($r->expectsJson() || $r->ajax()) {
            return response()->json([
                'ok' => $status === 200,
                'message' => $message,
                'data' => $payload,
            ], $status === 200 ? 200 : $status);
        }
        return $status === 200
            ? back()->with('success', $message)
            : back()->withErrors(['msg' => $message])->withInput();
    }
}