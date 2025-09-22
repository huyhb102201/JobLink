<?php
// app/Http/Controllers/Client/JobPostController.php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class JobPostController extends Controller
{
    public function create()
    {
        return view('client.jobs.create');
    }

    public function store(StoreJobRequest $request)
    {
        $data = $request->validated();
        $data['account_id'] = Auth::id(); // id client đăng job
        $data['status'] = 'open';

        Job::create($data);

        return redirect()->route('client.jobs.create')->with('success', 'Đăng job thành công!');
    }
    public function choose()
    {
        return view('client.jobs.choose');
    }
}
