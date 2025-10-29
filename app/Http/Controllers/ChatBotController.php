<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Carbon\Carbon;

class ChatBotController extends Controller
{
    public function handle(Request $request)
    {
        // Cho phép reset nhanh từ frontend
        if ($request->boolean('reset')) {
            session()->forget('chat_history');
            return response()->json(['reply' => "<div class='text-muted'>Đã làm mới hội thoại.</div>"]);
        }

        $question = trim((string) $request->input('message', ''));
        $messages = session('chat_history', []);

        // 💬 Lời nhắc hệ thống (system prompt)
        if (empty($messages)) {
            $messages[] = [
                'role' => 'system',
                'content' => "Bạn là chuyên viên tư vấn việc làm chuyên nghiệp, giao tiếp thân thiện, tự nhiên bằng tiếng Việt.
                Khi người dùng mô tả kinh nghiệm, kỹ năng (VD: Laravel, React, 12 năm...), hãy phân tích để tư vấn ngắn gọn (2-3 câu).
                Sau đó, nếu có dữ liệu việc làm phù hợp từ database, hãy gợi ý danh sách công việc tương ứng.
                Luôn trả lời bằng văn phong tự nhiên, tích cực và chuyên nghiệp."
            ];
        }

        if ($question !== '') {
            $messages[] = ['role' => 'user', 'content' => $question];
        }

        // 🧠 Tìm lĩnh vực (dựa trên job_categories)
        $categories = DB::table('job_categories')
            ->where('isDeleted', 0)
            ->select('category_id', 'name', 'img_url')
            ->get();

        $matchedCategory = null;
        foreach ($categories as $cat) {
            if (Str::contains(Str::lower($question), Str::lower($cat->name))) {
                $matchedCategory = $cat;
                break;
            }
        }

        // Nếu chưa xác định được, thử từ khóa phụ
        if (!$matchedCategory) {
            $keywordsMap = [
                'web' => 'Lập trình web',
                'mobile' => 'Lập trình di động',
                'di động' => 'Lập trình di động',
                'laravel' => 'Lập trình web',
                'php' => 'Lập trình web',
                'content' => 'Viết nội dung',
                'viết' => 'Viết nội dung',
                'thiết kế' => 'Thiết kế đồ họa',
                'ai' => 'Trí tuệ nhân tạo',
                'machine learning' => 'Trí tuệ nhân tạo',
                'phân tích' => 'Phân tích dữ liệu',
                'dữ liệu' => 'Phân tích dữ liệu',
                'cloud' => 'Điện toán đám mây',
                'đám mây' => 'Điện toán đám mây',
            ];
            foreach ($keywordsMap as $k => $v) {
                if (Str::contains(Str::lower($question), $k)) {
                    $matchedCategory = $categories->firstWhere('name', $v);
                    break;
                }
            }
        }

        // 🔎 Trích keyword để tìm theo TIÊU ĐỀ / MÔ TẢ công việc
        $terms = $this->extractSearchTerms($question); // mảng các từ khóa ≥3 ký tự, đã loại stopwords

        // 🌐 Gọi AI để tư vấn (OpenRouter) — phiên bản có log chi tiết
        $advice = '';
        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) {
            $advice = "⚠️ Chưa cấu hình OPENROUTER_API_KEY. Vui lòng thêm vào .env.";
        } else {
            try {
                $client = new Client([
                    'base_uri' => 'https://openrouter.ai',
                    'timeout' => 20,
                    'connect_timeout' => 10,
                    'http_errors' => true, // 4xx/5xx sẽ ném RequestException
                ]);

                $resp = $client->post('/api/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'HTTP-Referer' => 'https://www.vanda.id.vn/',
                        'X-Title' => 'Laravel Job Assistant',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'anthropic/claude-3-haiku',
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 200,
                    ],
                ]);

                $data = json_decode((string) $resp->getBody(), true);
                $content = $data['choices'][0]['message']['content'] ?? '';
                if (is_array($content)) {
                    $content = collect($content)->map(fn($p) => is_string($p) ? $p : ($p['text'] ?? ''))->implode('');
                }
                $advice = trim($content) ?: "Mình đã đọc yêu cầu của bạn và sẽ gợi ý vài hướng đi cùng các job liên quan ngay bên dưới nhé.";
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
                $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : null;

                \Log::error('OpenRouter error', [
                    'status' => $status,
                    'body' => $body,
                    'message' => $e->getMessage(),
                ]);

                if (config('app.debug')) {
                    $advice = "❌ OpenRouter lỗi (status {$status}). Chi tiết: " . Str::limit($body ?? $e->getMessage(), 500);
                } else {
                    $advice = "Hiện tại hệ thống AI đang tạm bận. Tôi sẽ tự động gợi ý công việc cho bạn nhé.";
                }
            } catch (\Throwable $e) {
                \Log::error('OpenRouter unexpected error', ['message' => $e->getMessage()]);
                $advice = "Hiện tại hệ thống AI đang tạm bận. Tôi sẽ tự động gợi ý công việc cho bạn nhé.";
            }
        }

        // 📋 Gợi ý công việc từ DB
        $jobListHtml = '';

        // 1) Gợi ý theo LĨNH VỰC (nếu có)
        if ($matchedCategory) {
            $jobsByCategory = DB::table('jobs')
                ->where('status', 'open')
                ->where('category_id', $matchedCategory->category_id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            if ($jobsByCategory->count()) {
                $jobListHtml .= $this->renderJobsBlock(
                    "Việc làm phù hợp trong lĩnh vực " . e($matchedCategory->name),
                    $jobsByCategory,
                    $matchedCategory->img_url
                );
            }
        }

        // 2) Gợi ý theo TIÊU ĐỀ/MÔ TẢ — ƯU TIÊN CHÍNH XÁC → CHỨA CỤM → TỪ KHÓA
        if ($question !== '' || !empty($terms)) {

            $phrase = trim($question);
            $phraseLower = $this->normalizeStr($phrase);

            // 2.1 Khớp CHÍNH XÁC (tiêu đề == cả câu người dùng)
            $exactTitle = collect();
            if ($phraseLower !== '') {
                $exactTitle = DB::table('jobs')
                    ->where('status', 'open')
                    ->whereRaw('LOWER(title) = ?', [$phraseLower])
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get();
            }

            // 2.2 Khớp GẦN ĐÚNG (tiêu đề có chứa cả câu)
            $titleContainsPhrase = collect();
            if ($phraseLower !== '') {
                $titleContainsPhrase = DB::table('jobs')
                    ->where('status', 'open')
                    ->whereRaw('LOWER(title) LIKE ?', ['%' . $phraseLower . '%'])
                    ->orderByDesc('created_at')
                    ->limit(12)
                    ->get();
            }

            // 2.3 Khớp theo TỪ KHÓA (title/description chứa bất kỳ term nào)
            $titleOrDescLikeTerms = collect();
            if (!empty($terms)) {
                $query = DB::table('jobs')->where('status', 'open');
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $t) {
                        $q->orWhere('title', 'like', '%' . $t . '%')
                            ->orWhere('description', 'like', '%' . $t . '%');
                    }
                });
                $titleOrDescLikeTerms = $query->orderByDesc('created_at')->limit(12)->get();
            }

            // Gộp & loại trùng để tính tập đã hiển thị
            $merged = $this->mergeJobsUnique([$exactTitle, $titleContainsPhrase, $titleOrDescLikeTerms], 50);

            // Render từng block theo thứ tự ưu tiên
            if ($exactTitle->count()) {
                $jobListHtml .= $this->renderJobsBlock(
                    "Khớp CHÍNH XÁC theo tiêu đề",
                    $exactTitle,
                    null
                );
            }

            // Chỉ render “chứa cụm từ” phần chưa nằm trong exact
            $containsButNotExact = $titleContainsPhrase->filter(function ($row) use ($exactTitle) {
                return !$this->inCollectionById($row, $exactTitle);
            });

            if ($containsButNotExact->count()) {
                $jobListHtml .= $this->renderJobsBlock(
                    "Khớp gần đúng theo tiêu đề",
                    $containsButNotExact,
                    null
                );
            }

            // Render theo từ khóa (bỏ hết những gì đã xuất hiện)
            $alreadyIds = $merged->pluck('job_id')->take(
                $exactTitle->count() + $titleContainsPhrase->count()
            )->all();

            $termsButNotPrev = $titleOrDescLikeTerms->filter(function ($row) use ($alreadyIds) {
                return !in_array($row->job_id, $alreadyIds, true);
            });

            if ($termsButNotPrev->count()) {
                $jobListHtml .= $this->renderJobsBlock(
                    "Khớp theo từ khóa trong tiêu đề/mô tả",
                    $termsButNotPrev,
                    null
                );
            }
        }

        // Nếu vẫn chưa có gì:
        if ($jobListHtml === '') {
            if ($matchedCategory) {
                $jobListHtml .= "
                    <div class='mt-2 text-muted'>
                        <i class='bi bi-info-circle'></i> Hiện chưa có việc nào mới thuộc lĩnh vực này. Bạn có thể thử mô tả chi tiết hơn nhu cầu/budget/thời gian.
                    </div>
                ";
            } else {
                $jobListHtml .= "
                    <div class='mt-2 text-muted'>
                        <i class='bi bi-question-circle'></i> Mình chưa xác định được lĩnh vực bạn quan tâm hoặc chưa tìm thấy job khớp tiêu đề.
                        Bạn thử gõ cụ thể hơn (VD: 'Thiết kế logo shop thời trang, ngân sách 1-2 triệu, cần trong 1 tuần').
                    </div>
                ";
            }
        }

        // 💡 Gợi ý nhanh (quick replies) để người dùng bấm
        $quickHtml = $this->renderQuickSuggestions($matchedCategory, $categories);

        // ✨ Gộp kết quả: tư vấn + danh sách công việc + gợi ý nhanh
        $reply = e($advice) . "<br>" . $jobListHtml . $quickHtml;

        // 🔁 Lưu lịch sử hội thoại
        $messages[] = ['role' => 'assistant', 'content' => strip_tags($reply)];
        session(['chat_history' => $messages]);

        return response()->json(['reply' => $reply]);
    }

    public function reset(Request $request)
    {
        session()->forget('chat_history');
        return response()->json(['status' => 'reset']);
    }

    /**
     * Tách từ khóa tìm kiếm: loại ký tự đặc biệt, lower, bỏ stopwords, giữ từ >= 3 ký tự.
     */
    protected function extractSearchTerms(string $text): array
    {
        $text = Str::lower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);

        $raw = preg_split('/\s+/u', (string) $text, -1, PREG_SPLIT_NO_EMPTY);
        $raw = array_map('trim', $raw);

        $stop = [
            'tôi',
            'toi',
            'là',
            'la',
            'cần',
            'can',
            'muốn',
            'muon',
            'việc',
            'viec',
            'làm',
            'lam',
            'job',
            'công',
            'cong',
            'việc',
            'viec',
            'ở',
            'o',
            'tại',
            'tai',
            'cho',
            'và',
            'va',
            'hoặc',
            'hoac',
            'thì',
            'thi',
            'nữa',
            'nua',
            'nhé',
            'nhe',
            'đi',
            'di',
            'giúp',
            'giup',
            'có',
            'co',
            'không',
            'khong',
            'một',
            'mot',
            'hai',
            'ba',
            'những',
            'nhung',
            'các',
            'cac',
            'trong',
            'khi',
            'với',
            'voi',
            'đến',
            'den'
        ];

        $terms = [];
        foreach ($raw as $w) {
            if (mb_strlen($w) >= 3 && !in_array($w, $stop, true)) {
                $terms[] = $w;
            }
        }
        return array_values(array_unique($terms));
    }

    /**
     * Render 1 block danh sách job.
     */
    protected function renderJobsBlock(string $title, $jobs, ?string $iconUrl = null): string
    {
        $titleSafe = e($title);
        $icon = $iconUrl
            ? "<img src='" . e($iconUrl) . "' style='width:36px;height:36px;border-radius:6px;margin-right:8px;'>"
            : "<i class='bi bi-briefcase-fill me-2'></i>";

        $html = "
        <div class='mt-3'>
          <div class='d-flex align-items-center mb-2'>
            {$icon}
            <strong>{$titleSafe}:</strong>
          </div>
          <ul class='list-unstyled'>
        ";

        foreach ($jobs as $job) {
            $salary = number_format((int) ($job->budget ?? 0), 0, ',', '.');
            $desc = e(Str::limit(strip_tags((string) ($job->description ?? '')), 100));
            $title = e((string) $job->title);
            $jobId = e((string) $job->job_id);

            // Xử lý deadline sang múi giờ Việt Nam
            if (!empty($job->deadline)) {
                try {
                    $deadline = Carbon::parse($job->deadline)
                        ->setTimezone('Asia/Ho_Chi_Minh')
                        ->format('H:i d/m/Y');
                } catch (\Exception $e) {
                    $deadline = 'N/A';
                }
            } else {
                $deadline = 'N/A';
            }

            $deadline = e($deadline);

            $html .= "
    <li class='p-2 mb-2 border rounded bg-white shadow-sm'>
      <b>{$title}</b><br>
      <small class='text-muted'>{$desc}</small><br>
      <i class='bi bi-cash-coin'></i> <b>{$salary}đ</b> &nbsp;
      <i class='bi bi-clock-history'></i> {$deadline}<br>
      <a href='/jobs/{$jobId}' class='text-primary' target='_blank'>
        <i class='bi bi-box-arrow-up-right'></i> Xem chi tiết
      </a>
    </li>";
        }


        $html .= "</ul></div>";
        return $html;
    }

    /**
     * Render quick suggestions (nút bấm gợi ý) bám theo category nếu có.
     */
    protected function renderQuickSuggestions($matchedCategory, $categories): string
    {
        // gợi ý chung
        $suggestions = [
            'Tôi có 2 năm làm Laravel + React, tìm job freelance',
            'Viết content SEO về du lịch, ngân sách 2-3 triệu',
            'Thiết kế logo cho shop thời trang, cần trong 1 tuần',
            'Cần dev mobile Flutter, làm remote, trả theo giờ',
            'Data analyst bán thời gian, báo giá giúp mình',
        ];

        // gợi ý theo category cụ thể (nếu có)
        if ($matchedCategory) {
            $c = $matchedCategory->name;
            $suggestions = array_merge([
                "Tôi muốn việc trong lĩnh vực {$c}, ngân sách 5-10 triệu",
                "Tôi có kinh nghiệm {$c}, có job nào mới không?",
                "Cần job {$c} làm từ xa, deadline linh hoạt",
            ], $suggestions);
        } else {
            // pick 3 category bất kỳ để gợi ý
            $pick = $categories->take(3)->pluck('name')->all();
            foreach ($pick as $cname) {
                $suggestions[] = "Tôi đang quan tâm lĩnh vực {$cname}";
            }
        }

        // render nút
        $btns = array_map(function ($text) {
            $t = e($text);
            return "<button type='button' class='btn btn-sm btn-outline-primary me-2 mb-2 quick-suggest' data-text=\"{$t}\"><i class='bi bi-lightning-charge'></i> {$t}</button>";
        }, $suggestions);

        return "<div class='mt-3'><div class='text-muted mb-1'><i class='bi bi-stars'></i> Gợi ý nhanh:</div>" . implode('', $btns) . "</div>";
    }

    /**
     * Gộp nhiều tập job theo thứ tự ưu tiên, loại trùng theo job_id, giới hạn tổng.
     */
    protected function mergeJobsUnique(array $lists, int $limit = 20)
    {
        $seen = [];
        $out = collect();

        foreach ($lists as $list) {
            foreach ($list as $row) {
                $id = (string) ($row->job_id ?? '');
                if ($id !== '' && !isset($seen[$id])) {
                    $seen[$id] = true;
                    $out->push($row);
                    if ($out->count() >= $limit) {
                        return $out;
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Kiểm tra 1 row có nằm trong collection khác theo job_id.
     */
    protected function inCollectionById($row, $collection): bool
    {
        $id = (string) ($row->job_id ?? '');
        if ($id === '')
            return false;
        foreach ($collection as $r) {
            if ((string) ($r->job_id ?? '') === $id)
                return true;
        }
        return false;
    }

    /**
     * Chuẩn hoá chuỗi để so sánh (lower + rút gọn khoảng trắng).
     * Nếu cần bỏ dấu tiếng Việt triệt để, cân nhắc thêm cột title_search (không dấu) để so sánh.
     */
    protected function normalizeStr(string $s): string
    {
        $s = Str::lower(trim($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    }
}
