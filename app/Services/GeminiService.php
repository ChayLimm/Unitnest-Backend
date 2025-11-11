<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class GeminiService
{
    private $apiKey;
    private $endpoint;
    private $primaryModel;     
    private $backupModels; 
    private $timeout;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->endpoint = env('GEMINI_ENDPOINT');

        $this->primaryModel = 'gemini-2.5-flash';
        $this->backupModels = [
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash',
        ];

        $this->timeout = 30; // seconds
    }

    /**
     * Generate AI response from prompt
     * - Using primary model first
     * - If fails, try backup models one by one
     */
    public function generate(string $prompt): string
    {
        if (empty($this->apiKey) || empty($this->endpoint)) {
            return 'Sorry, AI service is not available right now.';
        }

        Log::info("Using primary model: {$this->primaryModel}");
        //
        $response = $this->callGeminiAPI($this->primaryModel, $prompt);
        if ($response !== null) {
            return $response;
        }

        Log::warning("Primary model {$this->primaryModel} failed, try backup models.");

        // try backup models
        foreach ($this->backupModels as $model) {
            $response = $this->callGeminiAPI($model, $prompt);
            if ($response !== null) {
                return $response;
            }
        }

        // models failed
        Log::error('All Gemini models failed to generate content.');

        return 'Sorry, I am unable to process your request. Please try again in a moment!';
    }

    // call gemini API with specific model
    private function callGeminiAPI (string $model, string $prompt): ?string
    {
        try {
            $url = "{$this->endpoint}/models/{$model}:generateContent?key={$this->apiKey}";
            
            //
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

            //
            $response = Http::timeout($this->timeout)->post($url, $payload);

            if (!$response->successful()) {
                Log::error("Model {$model} API failed", ['status' => $response->status()]);
                return null;
            }

            $result = $response->json();

            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $text = trim($result['candidates'][0]['content']['parts'][0]['text']);

                if (!empty($text)) {
                    return $text;
                }
            }  
            if (isset($result['candidates'][0]['finishReason']) && 
                $result['candidates'][0]['finishReason'] === 'SAFETY') {
                return "Sorry, I cannot respond to that request due to content guidelines.";
            }

            return null;
    
        } catch (ConnectionException $e) { // timeout or connection issues
            Log::error("Model {$modelName} request error: " . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error("Model {$modelName} error: " . $e->getMessage());
            return null;
        }
    }
}