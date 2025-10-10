<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\JobApply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Skill;
class MyJobsController extends Controller
{
public function index(\Illuminate\Http\Request $r)
{
    $ownerId = $r->user()->account_id;

    $jobs = \App\Models\Job::where('account_id', $ownerId)
        ->withCount('applicants')
        ->with([
            'categoryRef',
            'applicants' => fn($q) =>
                $q->select('accounts.account_id','accounts.name','accounts.email','accounts.avatar_url')
                  ->orderBy('job_apply.created_at','desc'),
            'applicants.profile:profile_id,account_id,fullname,username,skill,description',
        ])
        ->latest('job_id')
        ->paginate(10);

    // Lấy map id => name một lần
    $skillMap = Skill::pluck('name', 'skill_id');

    return view('client.jobs.mine', compact('jobs', 'skillMap'));
}


public function update(Request $request, int $job_id, int $user_id)
{
    $ownerId = $request->user()->account_id;

    $job = \App\Models\Job::whereKey($job_id)->firstOrFail();
    abort_unless($job->account_id === $ownerId, 403);

    $status = (int) $request->input('status', 2); // 2 = chấp nhận

    DB::transaction(function () use ($job, $user_id, $status) {

        // 1) cập nhật status ứng viên
        \App\Models\JobApply::where('job_id', $job->job_id)
            ->where('user_id', $user_id)
            ->update(['status' => $status, 'updated_at' => now()]);

        // 2) nếu chấp nhận => set job in_progress
        if ($status === 2 && $job->status !== 'in_progress') {
            $job->status = 'in_progress';
            $job->save();
        }

        // 3) tính lại apply_id = danh sách user_id đã được chọn (status=2)
        $acceptedIds = \App\Models\JobApply::where('job_id', $job->job_id)
            ->where('status', 2)
            ->pluck('user_id')
            ->unique()->values()->all();

        $job->apply_id = empty($acceptedIds) ? null : implode(',', $acceptedIds);
        $job->save();

        // 4) NẾU duyệt (status=2) thì tạo box_chat nếu CHƯA tồn tại cho job này
        if ($status === 2) {
            $exists = DB::table('box_chat')->where('job_id', $job->job_id)->exists();

            if (!$exists) {
                DB::table('box_chat')->insert([
                    'name'       => 'Nhóm ' . Str::limit($job->title, 230), // tránh tràn cột nếu name varchar(255)
                    'type'       => 2,          // 3 = nhóm chat (theo bảng của bạn)
                    'receiver_id'=> null,
                    'sender_id'  => null,
                    'job_id'     => $job->job_id,
                    'org_id'     => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    });

    return back()->with('success', 'Cập nhật ứng viên, trạng thái job và phòng chat thành công.');
}


}
