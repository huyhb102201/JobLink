<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;

class PortfolioController extends Controller
{
    public function index()
    {
        return view('portfolios.index');
    }

    public function show($username)
    {
        $profile = Profile::with('account')
            ->where('username', $username)
            ->firstOrFail();

        $account = $profile->account;

        // Lấy tất cả jobs (sắp xếp mới nhất trước)
        $jobs = $account->jobs()->latest()->get();

        $stats = [
            'total_jobs' => $jobs->count(),
            'completed_jobs' => $jobs->where('status', 'completed')->count(),
            'ongoing_jobs' => $jobs->where('status', 'ongoing')->count(),
        ];

        return view('portfolios.index', compact('profile', 'account', 'jobs', 'stats'));
    }

}
