<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wizard\Step1Request;
use App\Http\Requests\Wizard\Step2Request;
use App\Http\Requests\Wizard\Step3Request;
use App\Http\Requests\Wizard\Step4Request;
use App\Models\Job;
use App\Models\JobDetail;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobWizardController extends Controller
{
    private string $bag = 'job_wizard';
    private int $totalSteps = 5;

    private function data(): array
    {
        return session($this->bag, []);
    }
    private function put(array $arr): void
    {
        session([$this->bag => array_merge($this->data(), $arr)]);
    }

    private function guardStep(int $n)
    {
        $d = $this->data();
        if ($n > 1 && empty($d['title']))
            return redirect()->route('client.jobs.wizard.step', 1)->send();
        if ($n > 2 && empty($d['description']))
            return redirect()->route('client.jobs.wizard.step', 2)->send();
        if ($n > 3 && (empty($d['payment_type']) || !array_key_exists('budget', $d))) {
            return redirect()->route('client.jobs.wizard.step', 3)->send();
        }
    }

    public function show(Request $r, int $n)
    {
        $n = max(1, min($this->totalSteps, $n));
        $this->guardStep($n);

        $view = match ($n) {
            1 => 'client.jobs.wizard.step1',
            2 => 'client.jobs.wizard.step2',
            3 => 'client.jobs.wizard.step3',
            4 => 'client.jobs.wizard.step4',
            5 => 'client.jobs.wizard.review',
        };

        $extra = [];
        if ($n === 2) {
            $extra['categories'] = JobCategory::orderBy('name')->get(['category_id', 'name']);
        }

        return view($view, array_merge([
            'n' => $n,
            'total' => $this->totalSteps,
            'd' => $this->data(),
        ], $extra));
    }

    public function store(Request $r, int $n)
    {
        switch ($n) {
            case 1:
                $v = app(Step1Request::class)->validated();
                $this->put($v);
                break;

            case 2:
                $v = app(Step2Request::class)->validated();

                $catName = null;
                if (!empty($v['category_id'])) {
                    $catName = JobCategory::where('category_id', $v['category_id'])->value('name');
                }

                $this->put([
                    'description' => $v['description'],          // -> jobs.description
                    'category_id' => $v['category_id'] ?? null,
                    'category_name' => $catName,
                    'content' => $v['content'] ?? null,      // -> job_detail.content
                ]);
                break;

            case 3:
                $v = app(Step3Request::class)->validated();
                $this->put($v);
                break;

            case 4:
                $v = app(Step4Request::class)->validated();
                $this->put($v);
                break;

            default:
                abort(404);
        }

        return redirect()->route('client.jobs.wizard.step', $n + 1);
    }

    public function submit(Request $r)
    {
        $d = $this->data();

        $user = $r->user()->loadMissing('type');                 // Account hiện tại + AccountType
        $autoApprove = (bool) optional($user->type)->auto_approve_job_posts;
        $status = $autoApprove ? 'open' : 'pending';

        DB::beginTransaction();
        try {
            // 1) Tạo JOB (mô tả cơ bản)
            $job = Job::create([
                'account_id' => $user->account_id,            // hoặc Auth::id() nếu guard trả về account_id
                'title' => $d['title'],
                'description' => $d['description'],            // plain text
                'category_id' => $d['category_id'] ?? null,
                'budget' => $d['budget'] ?? null,
                'payment_type' => $d['payment_type'],
                'deadline' => $d['deadline'] ?? null,
                'status' => $status,                      // <-- quyết định ở đây
            ]);

            // 2) Tạo JOB_DETAIL (nội dung định dạng)
            if (!empty($d['content'])) {
                $job->jobDetails()->create([
                    'content' => $d['content'],              // HTML từ editor
                    'notes' => $d['notes'] ?? null,
                    'created_at' => now(),                      // vì $timestamps=false trong model
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Không thể tạo job: ' . $e->getMessage()]);
        }

        session()->forget($this->bag);

        return redirect()
            ->route('client.jobs.mine')
            ->with('success', $autoApprove
                ? 'Đăng job thành công!'
                : 'Đã gửi job, đang chờ xét duyệt.');
    }
}
