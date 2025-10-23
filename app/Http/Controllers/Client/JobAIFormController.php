<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\GeminiClient;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JobAIFormController extends Controller
{
    /** 
     * Trang nhập mô tả và form AI
     */
    public function page()
    {
        return view('client.jobs.ai_form');
    }

    /** 
     * API: Nhận mô tả thô → Trả JSON điền form
     */
    public function build(Request $req, GeminiClient $gemini)
    {
        $req->validate([
            'draft' => 'required|string|min:10',
        ]);

        $userNeed = $req->string('draft');

        // Prompt chi tiết cho Gemini
        $prompt = <<<PROMPT
Bạn là trợ lý đăng job chuyên nghiệp.  
Hãy đọc yêu cầu của người dùng và TRẢ VỀ DUY NHẤT MỘT ĐỐI TƯỢNG JSON theo đúng schema này (không kèm văn bản giải thích):

{
  "title": "string (<= 120 ký tự, ngắn gọn, chuyên nghiệp, mô tả chính xác công việc)",
  "category_id": "integer",
  "category_name": "string",
  "payment_type": "fixed | hourly",
    "budget": "string",               // Ngân sách mỗi freelancer (nếu người dùng chỉ ghi 1 freelancer thì budget = total_budget)
  "quantity": "integer",            // Số lượng freelancer cần tuyển
  "total_budget": "string",         // Nếu người dùng nhập giá tổng (VD: 2 người giá 50000) → total_budget = 50000, budget = total_budget / quantity
  "deadline": "string",             // Ngày kết thúc (mặc định = hôm nay + 7 ngày, định dạng YYYY-MM-DD)
  "description": "string"           // Mô tả Markdown: ## Mục tiêu, ## Phạm vi, ## Kỹ năng, ## Thời gian & Ngân sách, ## Cách nộp đề xuất. Ngân sách này nếu 2 người giá 50000 thì ngân sách sẽ là 25000/ nhân viên, chia đều ra
}

Các danh mục hợp lệ:
1 - Web Development  
2 - Mobile Development  
3 - System Admin  
4 - Design  
5 - Marketing  
6 - Content Writing  
7 - AI/ML  
8 - Software Development  
9 - Data Analysis  
10 - Others  
11 - Web maintenance  
12 - Điện toán đám mây  

Yêu cầu:
- Chỉ trả JSON hợp lệ, không kèm text.
- Nếu thiếu total_budget → tính = budget × quantity.
- Nếu thiếu deadline → mặc định hôm nay + 7 ngày.
- Mô tả phải rõ ràng, có cấu trúc Markdown, trình bày đẹp.
Nội dung người dùng:
{$userNeed}
PROMPT;

        try {
            $json = $gemini->generate($prompt);

            // ✅ Chuẩn hoá dữ liệu
            $json['quantity'] = max(1, (int) ($json['quantity'] ?? 1));
            $json['budget'] = (float) ($json['budget'] ?? 0);
            // Nếu AI không phân biệt rõ, ta tự suy luận lại: 
// Nếu budget > 0 và quantity > 1 mà total_budget rỗng → coi budget là tổng, chia ngược cho mỗi người
            if (empty($json['total_budget']) && !empty($json['budget']) && $json['quantity'] > 1) {
                $json['total_budget'] = (float) $json['budget'];
                $json['budget'] = round($json['total_budget'] / $json['quantity'], 2);
            } else {
                // Ngược lại, coi budget là mỗi người
                $json['total_budget'] = (float) $json['budget'];
                $json['budget'] = round($json['total_budget'] / $json['quantity'], 2);
            }


            // ✅ Deadline: nếu trống hoặc sai → +7 ngày
            try {
                $deadline = isset($json['deadline']) ? Carbon::parse($json['deadline']) : now()->addDays(7);
                if ($deadline->lt(now()) || $deadline->gt(now()->addMonths(6))) {
                    $deadline = now()->addDays(7);
                }
                $json['deadline'] = $deadline->toDateString();
            } catch (\Exception $e) {
                $json['deadline'] = now()->addDays(7)->toDateString();
            }

            return response()->json(['ok' => true, 'data' => $json]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok' => false,
                'error' => 'AI tạm thời không phản hồi.'
            ], 500);
        }
    }

    /** 
     * Submit form → Lưu job
     */
    public function submit(Request $r)
    {
        $r->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20',
            'category_id' => 'nullable|integer|exists:job_categories,category_id',
            'budget' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1|max:100',
            'total_budget' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:fixed,hourly',
            'deadline' => 'nullable|date',
        ]);

        $user = $r->user()->loadMissing('type');
        $autoApprove = (bool) optional($user->type)->auto_approve_job_posts;
        $status = $autoApprove ? 'open' : 'pending';

        DB::beginTransaction();
        try {
            $budget = (float) $r->input('budget', 0);
            $quantity = (int) $r->input('quantity', 1);
            $totalBudget = $r->input('total_budget') ?: $budget * $quantity;

            $job = Job::create([
                'account_id' => $user->account_id,
                'category_id' => $r->input('category_id'),
                'title' => $r->input('title'),
                'description' => $r->input('description'),
                'budget' => $budget,             // mỗi freelancer
                'quantity' => $quantity,
                'total_budget' => $totalBudget,  // tổng tiền
                'payment_type' => $r->input('payment_type'),
                'deadline' => $r->filled('deadline')
                    ? Carbon::parse($r->input('deadline'))
                    : now()->addDays(14),
                'status' => $status,
            ]);

            // ✅ Ghi chi tiết (nếu có bảng job_details)
            if (method_exists($job, 'jobDetails')) {
                $job->jobDetails()->create([
                    'content' => $r->input('description'),
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('client.jobs.mine')
                ->with(
                    'success',
                    $autoApprove
                    ? '🎉 Đăng job thành công!'
                    : '✅ Job đã gửi, đang chờ xét duyệt.'
                );

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors([
                'msg' => 'Không thể tạo job: ' . $e->getMessage(),
            ]);
        }
    }
}
