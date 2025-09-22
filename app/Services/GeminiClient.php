<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class GeminiClient
{
    private Client $http;
    private string $key;

    public function __construct()
    {
        $this->http = new Client(['base_uri' => 'https://generativelanguage.googleapis.com/']);
        $this->key  = env('GEMINI_API_KEY');
    }

    public function generate(string $prompt, string $model = 'gemini-2.0-flash'): array
    {
        $res = $this->http->post("v1beta/models/{$model}:generateContent", [
            'query' => ['key' => $this->key],
            'json'  => [
                'contents' => [[ 'parts' => [ ['text' => $prompt] ] ]],
                'generationConfig' => [
                    'temperature' => 0.6,
                    'maxOutputTokens' => 900,
                ],
            ],
            'timeout' => 25,
        ]);

        $data = json_decode($res->getBody()->getContents(), true);
        $text = Arr::get($data, 'candidates.0.content.parts.0.text', '');

        // cố gắng bóc JSON từ câu trả lời
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) return $json;
        }
        return ['_raw' => $text]; // fallback
    }
}
