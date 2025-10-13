<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\GeminiClient;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
class JobAIFormController extends Controller
{
    // Trang nhập mô tả và có form
    public function page()
    {
        return view('client.jobs.ai_form');
    }

    // API: nhận mô tả thô -> trả về JSON field để điền form
    public function build(Request $req, GeminiClient $gemini)
    {
        $req->validate([
            'draft' => 'required|string|min:10',
        ]);

        $userNeed = $req->string('draft');

        // Prompt yêu cầu trả JSON đúng schema
        $prompt = <<<PROMPT
Bạn là trợ lý đăng job. Hãy phân tích yêu cầu sau và TRẢ VỀ JSON DUY NHẤT theo schema:

{
  "title": "string (<= 120 ký tự)",
  "category": "string (ví dụ: Web Development / Mobile Development / AI/ML / Design...)",
  "payment_type": "fixed | hourly",
  "budget": "string",           // ví dụ "500",
  "deadline": "string",         // ISO "2025-11-30"
  "description": "string"       // mô tả bằng Markdown, có các mục: Mục tiêu, Phạm vi & Đầu việc, Kỹ năng, Timeline & Ngân sách, Cách nộp đề xuất
}

Yêu cầu đảm bảo:
- Không giải thích dài dòng. Chỉ in ra JSON hợp lệ.
- Nếu thiếu thông tin, tự đề xuất giá trị hợp lý.

Nội dung người dùng:
{$userNeed}
PROMPT;

        try {
            $json = $gemini->generate($prompt); // trả mảng field
            return response()->json(['ok' => true, 'data' => $json]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => 'AI tạm thời không phản hồi.'], 500);
        }
    }

    public function submit(Request $r)
{
    $r->validate([
        'title'         => 'required|string|max:255',
        'description'   => 'required|string|min:20',
        'category'      => 'nullable|string|max:255',
        'budget'        => 'nullable|string|max:100',
        'payment_type'  => 'required|in:fixed,hourly',
        'deadline'      => 'nullable|string|max:100',
    ]);

    $d = $r->all();
    $user = $r->user()->loadMissing('type');

    $autoApprove = (bool) optional($user->type)->auto_approve_job_posts;
    $status = $autoApprove ? 'open' : 'pending';

    DB::beginTransaction();
    try {
        // ✅ 1. Tạo job
        $job = Job::create([
            'account_id'   => $user->account_id,
            'title'        => $d['title'],
            'description'  => $d['description'],
            'budget'       => $d['budget'] ?? 0,
            'payment_type' => $d['payment_type'],
            'deadline'     => now()->addDays(14), // tạm thời gán default nếu rỗng
            'status'       => $status,
        ]);

        // ✅ 2. Lưu mô tả chi tiết
        $job->jobDetails()->create([
            'content'     => $d['description'],  // có thể lưu HTML hoặc Markdown
            'notes'       => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors(['msg' => 'Không thể tạo job: ' . $e->getMessage()]);
    }

    return redirect()->route('client.jobs.mine')->with('success',
        $autoApprove ? 'Đăng job thành công!' : 'Đã gửi job, đang chờ xét duyệt.');
}
}
