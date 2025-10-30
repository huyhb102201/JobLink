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
        if ($request->boolean('reset')) {
            session()->forget([
                'chat_history','last_category_id','last_category_name',
                'last_terms','last_raw_query','last_title_phrase'
            ]);
            return response()->json(['reply' => "<div class='text-muted'>Đã làm mới hội thoại.</div>"]);
        }

        $question = trim((string) $request->input('message', ''));
        $messages = session('chat_history', []);

        /* ========== ⚡ QUICK INTENT: job đăng theo thời gian + số lượng ========== */
        if ($qi = $this->detectQuickRecentIntent($question)) {
            $tz = 'Asia/Ho_Chi_Minh';
            switch ($qi['type']) {
                case 'today':
                    $start = Carbon::today($tz); $end = Carbon::tomorrow($tz);
                    $title = ($qi['limit'] ? $qi['limit'].' ' : '').'công việc đăng hôm nay'; break;
                case 'yesterday':
                    $start = Carbon::yesterday($tz); $end = Carbon::today($tz);
                    $title = ($qi['limit'] ? $qi['limit'].' ' : '').'công việc đăng hôm qua'; break;
                case 'this_week':
                    $start = Carbon::now($tz)->startOfWeek(); $end = Carbon::now($tz)->endOfWeek()->addSecond();
                    $title = ($qi['limit'] ? $qi['limit'].' ' : '').'công việc đăng tuần này'; break;
                case 'this_month':
                    $start = Carbon::now($tz)->startOfMonth(); $end = Carbon::now($tz)->endOfMonth()->addSecond();
                    $title = ($qi['limit'] ? $qi['limit'].' ' : '').'công việc đăng tháng này'; break;
                default:
                    $start = Carbon::today($tz); $end = Carbon::tomorrow($tz);
                    $title = ($qi['limit'] ? $qi['limit'].' ' : '').'công việc đăng hôm nay';
            }
            return $this->replyRecentByRange($start, $end, $title, (int)($qi['limit'] ?? 0));
        }
        /* ========== END QUICK INTENT ========== */

        // 🧠 Câu nối tiếp theo ngữ cảnh?
        $isFollowUp = $this->detectFollowUp($question);

        // 🚦 Prompt hệ thống
        $systemPrompt = "Bạn là chuyên viên tư vấn việc làm (tiếng Việt, giọng tự nhiên, tích cực).
- Chỉ dựa vào CÂU HỎI HIỆN TẠI; nếu khác chủ đề trước thì nói ngắn: 'đã chuyển sang lĩnh vực mới'.
- Trả lời 3 ý ngắn: (1) tóm tắt nhu cầu; (2) gợi ý hồ sơ/hướng tìm; (3) bước tiếp theo.
- Không khẳng định có danh sách; danh sách (nếu có) do hệ thống render bên dưới.
- Nếu câu ngắn/thiếu ngữ cảnh (vd: 'còn không?'), hãy suy luận từ ngữ cảnh trước và nêu rõ là đang hiểu theo ngữ cảnh đó.";

        $aiMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        if ($question !== '') {
            $aiMessages[] = ['role' => 'user', 'content' => $question];
        }

        $lastCatName  = session('last_category_name');
        $lastTerms    = session('last_terms', []);
        $lastTitle    = session('last_title_phrase');

        if ($isFollowUp && ($lastCatName || $lastTerms || $lastTitle)) {
            $ctx = "Ngữ cảnh trước: ".
                ($lastCatName ? "lĩnh vực: {$lastCatName}. " : "").
                (!empty($lastTerms) ? "từ khóa: ".implode(', ', $lastTerms).". " : "").
                ($lastTitle ? "tiêu đề tìm gần nhất: '{$lastTitle}'." : "");
            $aiMessages[] = ['role' => 'system', 'content' => $ctx];
        }

        // 🗂️ Categories
        $categories = DB::table('job_categories')
            ->where('isDeleted', 0)
            ->select('category_id','name','img_url')
            ->orderBy('name')
            ->get();

        // 🔎 Suy luận category & domain khách sạn
        $normalizedQuestion = $this->normalizeStr($question);
        $matchedCategory = $this->inferCategory($question, $normalizedQuestion, $categories);
        $mustHotel = $this->shouldForceHotel($question, $normalizedQuestion);

        // 🧭 Phát hiện đổi chủ đề
        $prevCatId   = session('last_category_id');
        $prevCatName = session('last_category_name');
        $topicSwitched = $matchedCategory && $prevCatId && $matchedCategory->category_id !== $prevCatId;

        // 🌐 Gọi AI
        $advice = $this->callOpenRouterAdvice($aiMessages, $topicSwitched, $matchedCategory?->name);

        // 📌 Lưu context
        $curTerms = $this->extractSearchTerms($this->normalizeStr($question));
        session([
            'chat_history'       => array_merge($messages, [['role' => 'assistant', 'content' => strip_tags($advice)]]),
            'last_category_id'   => $matchedCategory?->category_id,
            'last_category_name' => $matchedCategory?->name,
            'last_terms'         => !empty($curTerms) ? $curTerms : $lastTerms,
            'last_raw_query'     => $question,
        ]);

        // ⚙️ Lọc phủ định theo lĩnh vực
        $negativeForMobile = ['web','website','wordpress','shopify','woocommerce'];
        $negativeForWeb    = ['android','ios','flutter','react native','react-native','kotlin','swift'];

        // 📚 Chiến lược tìm
        $blocks = [];
        $scopeCategoryId = $matchedCategory->category_id ?? null;

        // 🔍 0) Ý định “tìm theo tên job”
        $titleIntent = $this->detectTitleSearchIntent($question);
        if ($titleIntent && $titleIntent['phrase'] !== '') {
            $titlePhrase = $titleIntent['phrase'];
            session(['last_title_phrase' => $titlePhrase]);

            $titleResults = $this->searchByJobTitle($titlePhrase, $scopeCategoryId, $negativeForMobile, $negativeForWeb, $mustHotel);
            if ($titleResults->count()) {
                $blocks[] = [
                    'title' => "Tìm theo tên job: “{$titlePhrase}”",
                    'jobs'  => $titleResults,
                    'icon'  => null
                ];
            }
        }

        // 1) Khớp CHÍNH XÁC tiêu đề = cả câu
        $exactTitle = collect();
        if ($normalizedQuestion !== '') {
            $q = DB::table('jobs')->where('status','open');
            if ($scopeCategoryId) $q->where('category_id', $scopeCategoryId);
            $q->whereRaw('LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, "\r"," "),"\n"," "),"  "," "),"\t"," "), "  ", " "))) = ?', [$normalizedQuestion]);
            $q = $this->applyNegativeFilter($q, $scopeCategoryId, $negativeForMobile, $negativeForWeb);
            $q = $this->applyDomainHotelGuard($q, $mustHotel);
            $exactTitle = $q->orderByDesc('created_at')->limit(8)->get();
            if ($exactTitle->count()) {
                $blocks[] = ['title'=>'Khớp CHÍNH XÁC theo tiêu đề','jobs'=>$exactTitle,'icon'=>null];
            }
        }

        // 2) Khớp GẦN ĐÚNG: tiêu đề chứa cả câu
        $titleContainsPhrase = collect();
        if ($normalizedQuestion !== '' && mb_strlen($normalizedQuestion) >= 4) {
            $q = DB::table('jobs')->where('status','open');
            if ($scopeCategoryId) $q->where('category_id', $scopeCategoryId);
            $q->whereRaw('LOWER(title) LIKE ?', ['%'.$normalizedQuestion.'%']);
            $q = $this->applyNegativeFilter($q, $scopeCategoryId, $negativeForMobile, $negativeForWeb);
            $q = $this->applyDomainHotelGuard($q, $mustHotel);
            $titleContainsPhrase = $q->orderByDesc('created_at')->limit(12)->get();
            $titleContainsPhrase = $titleContainsPhrase->filter(fn($r)=>!$this->inCollectionById($r,$exactTitle));
            if ($titleContainsPhrase->count()) {
                $blocks[] = ['title'=>'Khớp gần đúng theo tiêu đề','jobs'=>$titleContainsPhrase,'icon'=>null];
            }
        }

        // 3) Tìm THEO TỪ KHÓA (title/description)
        $byTerms = collect();
        $terms = $curTerms ?: session('last_terms', []);
        if (!empty($terms)) {
            $q = DB::table('jobs')->where('status','open');
            if ($scopeCategoryId) $q->where('category_id', $scopeCategoryId);
            $q->where(function($qq) use ($terms) {
                foreach ($terms as $t) {
                    $qq->orWhereRaw('LOWER(title) LIKE ?', ['%'.$t.'%'])
                       ->orWhereRaw('LOWER(description) LIKE ?', ['%'.$t.'%']);
                }
            });
            $q = $this->applyNegativeFilter($q, $scopeCategoryId, $negativeForMobile, $negativeForWeb);
            $q = $this->applyDomainHotelGuard($q, $mustHotel);
            $byTerms = $q->orderByDesc('created_at')->limit(20)->get();
            $byTerms = $byTerms->filter(fn($r)=>!$this->inCollectionById($r,$exactTitle) && !$this->inCollectionById($r,$titleContainsPhrase));
            if ($byTerms->count()) {
                $blocks[] = ['title'=>'Khớp theo từ khóa trong tiêu đề/mô tả','jobs'=>$byTerms,'icon'=>null];
            }
        }

        // 4) Theo LĨNH VỰC (nếu có)
        if ($scopeCategoryId) {
            $mergedShown = $this->mergeJobsUnique([$exactTitle,$titleContainsPhrase,$byTerms], 200);
            $q = DB::table('jobs')->where('status','open')->where('category_id',$scopeCategoryId);
            $q = $this->applyNegativeFilter($q, $scopeCategoryId, $negativeForMobile, $negativeForWeb);
            $q = $this->applyDomainHotelGuard($q, $mustHotel);
            $byCategory = $q->orderByDesc('created_at')->limit(12)->get();
            $byCategory = $byCategory->filter(fn($r)=>!$this->inCollectionById($r,$mergedShown));
            if ($byCategory->count()) {
                $blocks[] = ['title'=>'Việc làm phù hợp trong lĩnh vực '.$matchedCategory->name,'jobs'=>$byCategory,'icon'=>$matchedCategory->img_url];
            }
        }

        // 🧮 Câu hỏi đếm số lượng
        $countNote = '';
        if ($scopeCategoryId && $this->looksLikeCountQuestion($this->normalizeStr($question))) {
            $total = DB::table('jobs')->where('status','open')->where('category_id',$scopeCategoryId)->count();
            $countNote = "<div class='mt-2'><i class='bi bi-info-circle'></i> Hiện có <b>".number_format($total,0,',','.')."</b> công việc mở trong lĩnh vực <b>".e($matchedCategory->name)."</b>.</div>";
        }

        // 🧩 Render + khử trùng lặp theo tiêu đề
        $jobListHtml = '';
        foreach ($blocks as $b) {
            $dedup = $this->dedupByNormalizedTitle($b['jobs']);
            $jobListHtml .= $this->renderJobsBlock($b['title'], $dedup, $b['icon']);
        }
        if ($jobListHtml === '') {
            $jobListHtml .= $this->renderNoResultNote((bool)$scopeCategoryId);
        }
        $quickHtml = $this->renderQuickSuggestions($matchedCategory, $categories);

        $reply = e($advice)
            . ($topicSwitched ? "<div class='small text-muted mt-1'><i class='bi bi-arrow-repeat'></i> Đã chuyển chủ đề sang <b>".e($matchedCategory?->name ?? 'khác')."</b>.</div>" : "")
            . ($isFollowUp ? "<div class='small text-muted'><i class='bi bi-link-45deg'></i> Hiểu là câu nối tiếp dựa trên ngữ cảnh trước.</div>" : "")
            . ($countNote ?: '')
            . "<br>" . $jobListHtml . $quickHtml;

        return response()->json(['reply' => $reply]);
    }

    public function reset(Request $request)
    {
        session()->forget(['chat_history','last_category_id','last_category_name','last_terms','last_raw_query','last_title_phrase']);
        return response()->json(['status' => 'reset']);
    }

    /* ========================= AI Advice ========================= */

    protected function callOpenRouterAdvice(array $messages, bool $topicSwitched, ?string $catName): string
    {
        $apiKey = env('OPENROUTER_API_KEY');
        if (!$apiKey) {
            return $this->fallbackAdvice($topicSwitched, $catName);
        }

        try {
            $client = new Client([
                'base_uri' => 'https://openrouter.ai',
                'timeout' => 18,
                'connect_timeout' => 8,
                'http_errors' => true,
            ]);

            $resp = $client->post('/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'HTTP-Referer'  => config('app.url', 'https://example.com/'),
                    'X-Title'       => 'Laravel Job Assistant',
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => env('OPENROUTER_MODEL','anthropic/claude-3-haiku'),
                    'messages'    => $messages,
                    'temperature' => (float) env('OPENROUTER_TEMPERATURE', 0.6),
                    'max_tokens'  => (int) env('OPENROUTER_MAXTOKENS', 220),
                ],
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            $content = $data['choices'][0]['message']['content'] ?? '';
            if (is_array($content)) {
                $content = collect($content)->map(fn($p)=>is_string($p)?$p:($p['text']??''))->implode('');
            }
            $content = trim((string) $content);
            return $content !== '' ? $content : $this->fallbackAdvice($topicSwitched, $catName);
        } catch (\Throwable $e) {
            \Log::warning('OpenRouter advice error', ['msg'=>$e->getMessage()]);
            return $this->fallbackAdvice($topicSwitched, $catName);
        }
    }

    protected function fallbackAdvice(bool $topicSwitched, ?string $catName): string
    {
        $prefix = $topicSwitched && $catName ? "Mình thấy bạn vừa chuyển sang lĩnh vực {$catName}. " : "";
        return $prefix."Mình sẽ tư vấn ngắn gọn theo mô tả của bạn (3 ý), sau đó hệ thống sẽ gợi ý danh sách phù hợp ngay bên dưới.";
    }

    /* ========================= Category infer ========================= */

    protected function inferCategory(string $original, string $normalized, $categories)
    {
        if ($original === '' || $categories->isEmpty()) return null;

        foreach ($categories as $cat) {
            $nameNorm = $this->normalizeStr((string)$cat->name);
            if ($nameNorm !== '' && Str::contains($normalized, $nameNorm)) {
                return $cat;
            }
        }

        $map = [
            'web' => 'Lập trình web',
            'frontend' => 'Lập trình web', 'backend' => 'Lập trình web',
            'php' => 'Lập trình web', 'laravel' => 'Lập trình web', 'react' => 'Lập trình web', 'vue' => 'Lập trình web',
            'mobile' => 'Lập trình di động', 'flutter' => 'Lập trình di động', 'android' => 'Lập trình di động', 'ios' => 'Lập trình di động', 'react native' => 'Lập trình di động',
            'content' => 'Viết nội dung', 'seo' => 'Viết nội dung', 'copy' => 'Viết nội dung',
            'design' => 'Thiết kế đồ họa', 'logo' => 'Thiết kế đồ họa', 'thiết kế' => 'Thiết kế đồ họa',
            'ai ' => 'Trí tuệ nhân tạo', 'machine learning' => 'Trí tuệ nhân tạo',
            'data' => 'Phân tích dữ liệu', 'dữ liệu' => 'Phân tích dữ liệu',
            'cloud' => 'Điện toán đám mây', 'đám mây' => 'Điện toán đám mây',

            // Bổ sung cho Khách sạn (đổi tên theo DB của bạn)
            'hotel' => 'Khách sạn / Du lịch',
            'khach san' => 'Khách sạn / Du lịch',
            'resort' => 'Khách sạn / Du lịch',
            'hospitality' => 'Khách sạn / Du lịch',
            'đặt phòng' => 'Khách sạn / Du lịch',
            'lễ tân' => 'Khách sạn / Du lịch',
            'buồng phòng' => 'Khách sạn / Du lịch',
        ];
        foreach ($map as $k=>$v) {
            if (Str::contains(Str::lower($original), $k) || Str::contains($normalized, $this->normalizeStr($k))) {
                $found = $categories->firstWhere('name', $v);
                if ($found) return $found;
            }
        }
        return null;
    }

    /* ========================= Query helpers ========================= */

    protected function applyNegativeFilter($query, ?int $scopeCategoryId, array $negMobile, array $negWeb)
    {
        if ($scopeCategoryId) {
            $lastName = session('last_category_name', '');
            $norm = $this->normalizeStr($lastName);

            if (Str::contains($norm, 'di dong') || Str::contains($norm, 'mobile') || Str::contains($norm, 'android') || Str::contains($norm, 'ios')) {
                foreach ($negMobile as $kw) {
                    $query->whereRaw('LOWER(title) NOT LIKE ?', ['%'.$this->normalizeStr($kw).'%']);
                }
            }

            if (Str::contains($norm, 'lap trinh web') || Str::contains($norm, 'web')) {
                foreach ($negWeb as $kw) {
                    $query->whereRaw('LOWER(title) NOT LIKE ?', ['%'.$this->normalizeStr($kw).'%']);
                }
            }
        }
        return $query;
    }

    /* ========================= DOMAIN: KHÁCH SẠN ========================= */

    protected function shouldForceHotel(string $original, string $normalized): bool
    {
        $needles = [
            'khach san','khách sạn','hotel','resort','homestay','hostel',
            'hospitality','lễ tân','le tan','buong phong','buồng phòng',
            'dat phong','đặt phòng','receptionist','housekeeping','ban hang ks','f&b','nhà hàng khách sạn',
            'front desk','booking'
        ];
        $o = Str::lower($original).' '.$normalized;
        foreach ($needles as $n) {
            if (Str::contains($o, $this->normalizeStr($n)) || Str::contains($o, $n)) {
                return true;
            }
        }
        return false;
    }

    protected function applyDomainHotelGuard($query, bool $mustHotel)
    {
        if (!$mustHotel) return $query;

        $pos = [
            'khach san','khách sạn','hotel','resort','homestay','hostel',
            'hospitality','lễ tân','le tan','buong phong','buồng phòng',
            'dat phong','đặt phòng','receptionist','housekeeping','front desk',
            'booking','đặt bàn','nhà hàng khách sạn','f&b'
        ];
        $neg = [
            'quan ao','quần áo','thoi trang','thời trang','thu vien','thư viện',
            'shop','cua hang','cửa hàng','ban hang online','bán hàng online',
            'do an vat','đồ ăn vặt'
        ];

        $query->where(function ($qq) use ($pos) {
            foreach ($pos as $kw) {
                $norm = $this->normalizeStr($kw);
                $qq->orWhereRaw('LOWER(title) LIKE ?', ['%'.$norm.'%'])
                   ->orWhereRaw('LOWER(description) LIKE ?', ['%'.$norm.'%']);
            }
        });

        foreach ($neg as $n) {
            $nn = $this->normalizeStr($n);
            $query->whereRaw('LOWER(title) NOT LIKE ?', ['%'.$nn.'%'])
                  ->whereRaw('LOWER(description) NOT LIKE ?', ['%'.$nn.'%']);
        }

        $query->orderByRaw("
            (CASE
                WHEN LOWER(title) LIKE '%khach san%' OR LOWER(title) LIKE '%khách sạn%' THEN 100
                WHEN LOWER(title) LIKE '%hotel%' OR LOWER(title) LIKE '%resort%' THEN 90
                WHEN LOWER(description) LIKE '%khach san%' OR LOWER(description) LIKE '%khách sạn%' THEN 80
                WHEN LOWER(description) LIKE '%hotel%' OR LOWER(description) LIKE '%resort%' THEN 70
                ELSE 0
            END) DESC
        ");

        return $query;
    }

    protected function dedupByNormalizedTitle($collection)
    {
        $seen = [];
        return collect($collection)->filter(function($r) use (&$seen) {
            $t = $this->normalizeStr((string)($r->title ?? ''));
            if ($t === '') return true;
            if (isset($seen[$t])) return false;
            $seen[$t] = true;
            return true;
        })->values();
    }

    protected function looksLikeCountQuestion(string $normalized): bool
    {
        return (Str::contains($normalized,'bao nhieu')
             || Str::contains($normalized,'co may')
             || Str::contains($normalized,'how many'));
    }

    /* ========================= Rendering ========================= */

    protected function renderJobsBlock(string $title, $jobs, ?string $iconUrl = null): string
    {
        $titleSafe = e($title);
        $icon = $iconUrl
            ? "<img src='".e($iconUrl)."' style='width:36px;height:36px;border-radius:6px;margin-right:8px;'>"
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
            $salary = number_format((int)($job->budget ?? 0), 0, ',', '.');
            $desc   = e(Str::limit(strip_tags((string)($job->description ?? '')), 110));
            $title  = e((string)$job->title);
            $jobId  = e((string)$job->job_id);

            $created = 'N/A';
            try {
                $createdAt = $job->created_at instanceof \DateTimeInterface
                    ? Carbon::instance($job->created_at)
                    : Carbon::parse((string) $job->created_at);
                $created = $createdAt->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
            } catch (\Throwable $e) {
                $created = 'N/A';
            }
            $created = e($created);

            $html .= "
            <li class='p-2 mb-2 border rounded bg-white shadow-sm'>
              <b>{$title}</b><br>
              <small class='text-muted'>{$desc}</small><br>
              <i class='bi bi-cash-coin'></i> <b>{$salary}đ</b> &nbsp;
              <i class='bi bi-clock-history'></i> {$created}<br>
              <a href='/jobs/{$jobId}' class='text-primary' target='_blank'>
                <i class='bi bi-box-arrow-up-right'></i> Xem chi tiết
              </a>
            </li>";
        }

        $html .= "</ul></div>";
        return $html;
    }

    protected function renderNoResultNote(bool $hadCategory): string
    {
        if ($hadCategory) {
            return "
            <div class='mt-2 text-muted'>
                <i class='bi bi-info-circle'></i> Chưa thấy job thật sự khớp lĩnh vực này. 
                Bạn thử ghi rõ tiêu đề/kỹ năng/budget/thời gian nhé.
            </div>";
        }
        return "
        <div class='mt-2 text-muted'>
            <i class='bi bi-question-circle'></i> Mình chưa xác định được lĩnh vực bạn quan tâm hoặc chưa tìm thấy job khớp.
            Hãy mô tả cụ thể hơn (VD: 'Flutter remote theo giờ, ngân sách 200k/giờ').
        </div>";
    }

    protected function renderQuickSuggestions($matchedCategory, $categories): string
    {
        $suggestions = [
            'Tôi có 2 năm làm Laravel + React, tìm job freelance',
            'Viết content SEO về du lịch, ngân sách 2-3 triệu',
            'Thiết kế logo cho shop thời trang, cần trong 1 tuần',
            'Cần dev mobile Flutter, làm remote, trả theo giờ',
            'Data analyst bán thời gian, báo giá giúp mình',
        ];

        if ($matchedCategory) {
            $c = $matchedCategory->name;
            array_unshift(
                $suggestions,
                "Tôi muốn việc trong lĩnh vực {$c}, ngân sách 5-10 triệu",
                "Tôi có kinh nghiệm {$c}, có job nào mới không?",
                "Cần job {$c} làm từ xa, deadline linh hoạt"
            );
        } else {
            foreach ($categories->take(3)->pluck('name') as $cname) {
                $suggestions[] = "Tôi đang quan tâm lĩnh vực {$cname}";
            }
        }

        $btns = array_map(function ($text) {
            $t = e($text);
            return "<button type='button' class='btn btn-sm btn-outline-primary me-2 mb-2 quick-suggest' data-text=\"{$t}\"><i class='bi bi-lightning-charge'></i> {$t}</button>";
        }, $suggestions);

        return "<div class='mt-3'><div class='text-muted mb-1'><i class='bi bi-stars'></i> Gợi ý nhanh:</div>".implode('', $btns)."</div>";
    }

    /* ========================= Text utils ========================= */

    protected function extractSearchTerms(string $normalized): array
    {
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized);
        $raw = preg_split('/\s+/u', (string)$normalized, -1, PREG_SPLIT_NO_EMPTY);

        $stop = [
            'toi','la','can','muon','viec','lam','job','cong','o','tai','cho','va','hoac','thi',
            'nua','nhe','di','giup','co','khong','mot','hai','ba','nhung','cac','trong','khi',
            'voi','den','tim','giup','minh','nhu','hon','ban','toi',
        ];

        $terms = [];
        foreach ($raw as $w) {
            if (mb_strlen($w) >= 3 && !in_array($w, $stop, true)) {
                $terms[] = $w;
            }
        }
        return array_values(array_unique($terms));
    }

    protected function normalizeStr(string $s): string
    {
        $s = Str::lower(trim($s));
        $trans = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
            'đ'=>'d',
        ];
        $s = strtr($s, $trans);
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    }

    protected function inCollectionById($row, $collection): bool
    {
        $id = (string)($row->job_id ?? '');
        if ($id === '') return false;
        foreach ($collection as $r) {
            if ((string)($r->job_id ?? '') === $id) return true;
        }
        return false;
    }

    protected function mergeJobsUnique(array $lists, int $limit = 50)
    {
        $seen = []; $out = collect();
        foreach ($lists as $list) {
            foreach ($list as $row) {
                $id = (string)($row->job_id ?? '');
                if ($id !== '' && !isset($seen[$id])) {
                    $seen[$id] = true;
                    $out->push($row);
                    if ($out->count() >= $limit) return $out;
                }
            }
        }
        return $out;
    }

    /* ========================= QUICK INTENT HELPERS ========================= */

    protected function detectQuickRecentIntent(string $q): ?array
    {
        $qLower = Str::lower(trim($q));
        $qLower = str_replace(
            ['coogn', 'kím', 'kiêm', 'skyf', 'hôm nayf', 'hnay'],
            ['công',  'kiếm','kiếm','style','hôm nay', 'hôm nay'],
            $qLower
        );

        $limit = null;
        if (preg_match('/\b(top\s*)?(\d{1,3})\s*(công|job)\b/u', $qLower, $m)) {
            $limit = (int) $m[2];
            if ($limit < 1) $limit = 1;
            if ($limit > 50) $limit = 50;
        }

        if (preg_match('/(h[oô]m nay|today|trong ngày)/u', $qLower))  return ['type' => 'today', 'limit' => $limit];
        if (preg_match('/(h[oô]m qua|yesterday)/u', $qLower))        return ['type' => 'yesterday', 'limit' => $limit];
        if (preg_match('/(tu[aâ]n n[aà]y|this week)/u', $qLower))    return ['type' => 'this_week', 'limit' => $limit];
        if (preg_match('/(th[aá]ng n[aà]y|this month)/u', $qLower))  return ['type' => 'this_month', 'limit' => $limit];

        if (preg_match('/(công\s*việc|job).*(h[oô]m nay)/u', $qLower)) {
            return ['type' => 'today', 'limit' => $limit];
        }
        return null;
    }

    /**
     * Ý định “tìm theo tên job”: trả về ['phrase'=>string] hoặc null.
     */
    protected function detectTitleSearchIntent(string $q): ?array
    {
        $q = trim($q);
        if ($q === '') return null;

        if (preg_match('/["“](.+?)["”]/u', $q, $m)) {
            $phrase = trim($m[1]);
            if ($phrase !== '') return ['phrase' => $phrase];
        }

        $qLower = Str::lower($q);
        $patterns = [
            '/\b(tim|tìm|kiem|kiếm)\s+(job|viec|việc)\s+(.+)/u',
            '/\b(job|viec|việc)\s+(.+)/u',
            '/\b(tieu de|tiêu đề)\s+(.+)/u',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $qLower, $m)) {
                $tail = trim($m[count($m)-1]);
                $tail = preg_replace('/\b(di|voi|o|tai|o dau|tai dau|khong|ko)\b/u', '', $tail);
                $tail = trim($tail);
                if ($tail !== '') return ['phrase' => $tail];
            }
        }
        return null;
    }

    /**
     * Tìm theo tên job: ưu tiên FULLTEXT nếu có, fallback LIKE.
     * ĐÃ TÍCH HỢP domain guard Khách sạn.
     */
    protected function searchByJobTitle(string $phrase, ?int $scopeCategoryId, array $negMobile, array $negWeb, bool $mustHotel = false)
    {
        $phraseNorm = $this->normalizeStr($phrase);

        $canFulltext = false;
        try {
            DB::select("EXPLAIN SELECT id FROM jobs WHERE MATCH(title, description) AGAINST (? IN NATURAL LANGUAGE MODE) LIMIT 1", [$phrase]);
            $canFulltext = true;
        } catch (\Throwable $e) {
            $canFulltext = false;
        }

        if ($canFulltext) {
            $q = DB::table('jobs')->where('status','open');
            if ($scopeCategoryId) $q->where('category_id', $scopeCategoryId);
            $q->whereRaw("MATCH(title, description) AGAINST (? IN NATURAL LANGUAGE MODE)", [$phrase]);
            $q->orWhere(function($qq) use ($phraseNorm, $scopeCategoryId){
                $qq->where('status','open');
                if ($scopeCategoryId) $qq->where('category_id', $scopeCategoryId);
                $qq->whereRaw('LOWER(title) LIKE ?', ['%'.$phraseNorm.'%']);
            });
            $q = $this->applyNegativeFilter($q, $scopeCategoryId, $negMobile, $negWeb);
            $q = $this->applyDomainHotelGuard($q, $mustHotel);
            $q->orderByRaw("CASE 
                WHEN LOWER(title) = ? THEN 100
                WHEN LOWER(title) LIKE ? THEN 80
                ELSE 50 END DESC, created_at DESC",
                [$phraseNorm, '%'.$phraseNorm.'%']
            );
            return $q->limit(20)->get();
        }

        $q = DB::table('jobs')->where('status','open');
        if ($scopeCategoryId) $q->where('category_id', $scopeCategoryId);
        $q->whereRaw('LOWER(title) LIKE ?', ['%'.$phraseNorm.'%']);
        $q = $this->applyNegativeFilter($q, $scopeCategoryId, $negMobile, $negWeb);
        $q = $this->applyDomainHotelGuard($q, $mustHotel);
        $q->orderByRaw("CASE 
            WHEN LOWER(title) = ? THEN 100
            WHEN LOWER(title) LIKE ? THEN 80
            ELSE 50 END DESC, created_at DESC",
            [$phraseNorm, '%'.$phraseNorm.'%']
        );
        return $q->limit(20)->get();
    }

    /**
     * Câu nối tiếp?
     */
    protected function detectFollowUp(string $q): bool
    {
        $qTrim = trim($this->normalizeStr($q));
        if ($qTrim === '') return false;
        if (mb_strlen($qTrim) <= 3) return true;

        $hints = [
            'con khong','con ko','con nua','cho them','them di','nua khong','nua ko',
            'co khong','co ko','co nua khong','co nua ko','co remote khong','co lam tu xa khong',
            'co them khong','them job','them viec','co job nao khac'
        ];
        foreach ($hints as $h) {
            if (Str::contains($qTrim, $h)) return true;
        }
        return false;
    }

    /**
     * Query theo khoảng thời gian created_at (TZ Asia/Ho_Chi_Minh).
     */
    protected function replyRecentByRange(Carbon $startLocal, Carbon $endLocal, string $title, int $limit = 0)
    {
        $startUtc = $startLocal->copy()->timezone('UTC');
        $endUtc   = $endLocal->copy()->timezone('UTC');

        $q = DB::table('jobs')
            ->where('status', 'open')
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->orderByDesc('created_at');

        if ($limit > 0) $q->limit($limit);

        $rows = $q->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'reply' => "<div class='text-muted'><i class='bi bi-emoji-frown'></i> Chưa có công việc nào được đăng trong khoảng thời gian yêu cầu.</div>"
            ]);
        }

        if ($limit === 0 && $rows->count() > 8) {
            $rows = $rows->take(8);
        }

        $html = $this->renderJobsBlock($title, $rows->all());
        $note = "<div class='small text-muted mt-1'><i class='bi bi-info-circle'></i> Lấy theo <b>ngày đăng</b> (múi giờ Asia/Ho_Chi_Minh).</div>";

        return response()->json(['reply' => $html . $note]);
    }
}
