<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\GeminiClient;
use Illuminate\Http\Request;

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
  "skills": ["string", "..."],  // tối đa 8 kỹ năng
  "payment_type": "fixed | hourly",
  "budget": "string",           // ví dụ "$500", "$15-20/h"
  "deadline": "string",         // ví dụ "2 tuần", hoặc ISO "2025-11-30"
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
}
