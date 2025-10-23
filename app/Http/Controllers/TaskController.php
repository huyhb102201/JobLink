<?php

// app/Http/Controllers/TaskController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
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
