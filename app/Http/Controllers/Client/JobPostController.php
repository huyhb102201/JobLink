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
        $user = $request->user()->loadMissing('type'); // = Account hiện đang đăng nhập

        $autoApprove = (bool) ($user->type->auto_approve_job_posts ?? false);

        $data = $request->validated();
        $data['account_id'] = $user->account_id;
        $data['status'] = $autoApprove ? 'open' : 'pending'; // open = tự duyệt, pending = chờ duyệt

        Job::create($data);

        return redirect()
            ->route('client.jobs.create')
            ->with('success', $autoApprove ? 'Đăng job thành công!' : 'Đã gửi, bài đang chờ duyệt.');
    }

    public function choose()
    {
        return view('client.jobs.choose');
    }
}
