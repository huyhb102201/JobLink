<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class JobController extends Controller
{
    public function index()
    {
        // Lấy tất cả job (hoặc theo user)
        $jobs = Job::with('account')->orderBy('created_at', 'desc')->get();

        return view('jobs.index', compact('jobs'));
    }
}
