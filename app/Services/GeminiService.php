<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private $apiKey;
    private $endpoint;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->endpoint = env('GEMINI_ENDPOINT');
    }

    // POST request to Gemini
    public function generate(string $prompt): string
    {
        if (empty($this->apiKey)) {
            return 'AI service not configured';
        }

        try {
            $url = $this->endpoint.'/models/gemini-2.5-flash:generateContent?key='.$this->apiKey;

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 200, // optimize token usage
                    'temperature' => 0.7,
                ]
            ];

            $response = Http::post($url, $payload);

            if (!$response->successful()) {
                Log::error('Gemini API failed', ['status' => $response->status()]);
                return 'Sorry, AI service error';
            }

            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';

        } catch (\Exception $e) {
            Log::error('Gemini error: ' . $e->getMessage());
            return 'Sorry, something went wrong';
        }
    }
}