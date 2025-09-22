<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class JobController extends Controller
{
public function index(Request $request)
{
    $jobs = Job::with('account','jobCategory')
        ->orderBy('created_at','desc')
        ->paginate(6);

    if ($request->ajax()) {
        // Đây phải là partial chứa #jobs-list và #pagination-wrapper
        return view('jobs.partials.jobs-list', compact('jobs'))->render();
    }

    return view('jobs.index', compact('jobs'));
}





    public function show(Job $job)
    {
        $job->load('jobDetails');

        return view('jobs.show', compact('job'));
    }
}
