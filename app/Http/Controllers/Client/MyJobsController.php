<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class MyJobsController extends Controller
{
    public function index()
    {
        $jobs = Job::with(['categoryRef'])
            ->where('account_id', Auth::id())
            ->latest('job_id')
            ->paginate(10);

        return view('client.jobs.mine', compact('jobs'));
    }
}
