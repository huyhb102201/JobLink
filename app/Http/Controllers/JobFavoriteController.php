<?php

namespace App\Http\Controllers;

use App\Models\JobFavorite;
use App\Models\Job;
use Illuminate\Http\Request;

class JobFavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = \Auth::user();
        $userId = $user?->account_id ?? \Auth::id();

        $q = trim($request->get('q', ''));
        $payment = $request->get('payment'); // fixed|hourly|null

        $favorites = Job::query()
            ->select('jobs.*')
            ->join('job_favorites', 'job_favorites.job_id', '=', 'jobs.job_id')
            ->where('job_favorites.user_id', $userId)
            ->when($q, function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('jobs.title', 'like', "%{$q}%")
                        ->orWhere('jobs.description', 'like', "%{$q}%");
                });
            })
            ->when(in_array($payment, ['fixed', 'hourly'], true), function ($qr) use ($payment) {
                $qr->where('jobs.payment_type', $payment);
            })
            ->latest('job_favorites.created_at')
            ->paginate(9)
            ->withQueryString();

        return view('jobFavorites.index', compact('favorites', 'q', 'payment'));
    }

    public function toggle(Request $request, int $jobId)
    {
        $user = \Auth::user();
        $userId = $user?->account_id ?? \Auth::id();

        $job = Job::where('job_id', $jobId)->firstOrFail();

        $fav = JobFavorite::where('user_id', $userId)->where('job_id', $jobId)->first();

        if ($fav) {
            $fav->delete();
            $status = 'removed';
        } else {
            JobFavorite::create(['user_id' => $userId, 'job_id' => $jobId]);
            $status = 'added';
        }

        return response()->json(['status' => $status]);
    }

}

