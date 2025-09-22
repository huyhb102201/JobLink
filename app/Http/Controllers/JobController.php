<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class JobController extends Controller
{
    public function index()
    {
        $jobs = Job::with('account', 'jobCategory')
            ->orderBy('created_at', 'desc')
            ->paginate(6); // mỗi trang 6 job

        return view('jobs.index', compact('jobs'));
    }

    public function show(Job $job)
    {
        $job->load('jobDetails');

        return view('jobs.show', compact('job'));
    }
}
