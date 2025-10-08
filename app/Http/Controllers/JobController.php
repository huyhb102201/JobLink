<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class JobController extends Controller
{
    public function index(Request $r)
    {
        $ownerId = $r->user()->account_id;

        $jobs = Job::where('account_id', $ownerId)
            ->withCount('applies') // đếm số ứng viên
            ->with([
                // danh sách apply mới nhất trước
                'applies' => fn($q) => $q->latest(),

                // account của ứng viên (chỉ cần vài cột)
                'applies.user:account_id,name,email',

                // profile của ứng viên
                'applies.user.profile:profile_id,account_id,fullname,username,skill,description',
            ])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('client.jobs.mine', compact('jobs'));
    }


    public function show(Job $job)
    {
        $job->load('jobDetails');

        return view('jobs.show', compact('job'));
    }
}
