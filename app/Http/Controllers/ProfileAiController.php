<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiClient; // service bạn đã có
use Illuminate\Support\Str;
class ProfileAiController extends Controller
{
    /**
     * Sinh nội dung "Giới thiệu" (CV-like) cho hồ sơ người dùng.
     * Request: { tone?, years?, skills?, roles?, highlights?, language? }
     */
    public function buildAbout(Request $req, GeminiClient $gemini)
    {
        $req->validate([
            'tone'       => 'nullable|string|max:30',
            'years'      => 'nullable|integer|min:0|max:50',
            'skills'     => 'nullable|string|max:400',
            'roles'      => 'nullable|string|max:200',
            'highlights' => 'nullable|string|max:400',
            'language'   => 'nullable|in:vi,en',
        ]);

        $tone       = $req->input('tone', 'chuyên nghiệp, rõ ràng');
        $years      = $req->input('years');
        $skills     = $req->input('skills');
        $roles      = $req->input('roles');
        $highlights = $req->input('highlights');
        $language   = $req->input('language', 'vi');

        $prompt = <<<PROMPT
Bạn là trợ lý viết CV. Viết phần "Giới thiệu" ngắn gọn (120–180 từ) dưới dạng Markdown, đúng NGÔN NGỮ: {$language}.
Giọng văn: {$tone}.
Bố cục 4 đoạn ngắn, mỗi đoạn 1–2 câu (không tiêu đề):
1) Tự giới thiệu ngắn + vai trò chính ({$roles}).
2) Kinh nghiệm tổng quan (nếu có: {$years} năm) + vài công nghệ then chốt ({$skills}).
3) Điểm mạnh/cách làm việc, phương pháp, quy trình.
4) Thành tựu (nếu có: {$highlights}) + lời mời hợp tác.

YÊU CẦU:
- Không bullet list, không emoji.
- Không kèm giải thích hay JSON. Chỉ in ra NỘI DUNG MARKDOWN.
PROMPT;

        try {
            $out = $gemini->generate($prompt); // có thể trả string hoặc array

            // Lấy text thuần từ kết quả
            $text = is_array($out)
                ? ($out['text'] ?? $out['markdown'] ?? $out['content'] ?? json_encode($out, JSON_UNESCAPED_UNICODE))
                : (string) $out;

            // Nếu model vẫn lỡ bọc JSON -> bóc
            if (Str::startsWith(trim($text), '{')) {
                $tmp = json_decode($text, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $text = $tmp['text'] ?? $tmp['markdown'] ?? $tmp['_raw'] ?? $text;
                }
            }

            // --- Convert Markdown -> HTML ---
            // Khuyến nghị cài package: composer require league/commonmark
            if (class_exists(\League\CommonMark\GithubFlavoredMarkdownConverter::class)) {
                $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]);
                $html = (string) $converter->convert($text);
            } else {
                // Fallback nhẹ nếu chưa cài commonmark: xuống dòng -> <p>/<br>
                $escaped = e($text);
                $html = collect(preg_split("/\r?\n\r?\n/", $escaped))
                    ->map(fn($p) => '<p>'.preg_replace("/\r?\n/", '<br>', $p).'</p>')
                    ->implode("\n");
            }

            // (tuỳ chọn) sanitize mạnh hơn nếu muốn: requires mews/purifier
            // $html = \Purifier::clean($html);

            return response()->json([
                'ok'   => true,
                'html' => $html,     // => gửi HTML thật cho Jodit
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok'    => false,
                'error' => 'AI tạm thời không phản hồi. Vui lòng thử lại sau.',
            ], 500);
        }
    }
}
