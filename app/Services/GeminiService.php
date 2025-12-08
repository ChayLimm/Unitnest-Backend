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
            'gemini-2.0-flash',
            'gemini-2.0-flash-lite',
        ];

        $this->timeout = 10; // seconds
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
        $start = microtime(true); // start timing 
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
                    'maxOutputTokens' => 1024, // balance for JSON response 
                    'temperature' => 0.5,
                ]
            ];

            //
            $response = Http::timeout($this->timeout)->post($url, $payload);

            $duration = microtime(true) - $start; // end timing of gemini call
            Log::info("Gemini [{$model}] response time: {$duration} seconds");

            if (!$response->successful()) {
                Log::error("Model {$model} API failed", ['status' => $response->status()]);
                return null;
            }

            $result = $response->json();
            // Log::info("{$model} response:", ['result' => $result]);
            // log token usage
            if (isset($result['usageMetadata'])) {
                Log::info("Token usage", [
                    'model' => $model,
                    'input_tokens' => $result['usageMetadata']['promptTokenCount'] ?? 0,
                    'output_tokens' => $result['usageMetadata']['candidatesTokenCount'] ?? 0,
                    'total_tokens' => $result['usageMetadata']['totalTokenCount'] ?? 0,
                ]);
            }

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
            Log::error("Model {$model} request error: " . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error("Model {$model} error: " . $e->getMessage());
            return null;
        }
    }

    // handle clean reponse from ai - output normalization
    public function normalizeResponse($response){

        // log raw response
        Log::info("Gemini Raw Response:", ['response' => $response]);

        // remove markdown wrappers
        $removeMarkdown = preg_replace('/```json\s*|\s*```/', '', $response);
        $clean = trim ($removeMarkdown);

        Log::info("Gemini Clean Response:", ['clean' => $clean]);

        // decode json response clean
        $data = json_decode($clean, true);

        // check if json decode success
        if (json_last_error() === JSON_ERROR_NONE && isset($data['text'])) {
            Log::info('Gemini response parsed as JSON successfully:', $data);
            return [
                'event' => isset($data['event']) ? trim($data['event']) : null,
                'text' => $data['text'] ?? 'Processing...',
            ];
        }

        // if not valid json, return text only
        if (preg_match('/"text"\s*:\s*"([^"]+)"/', $response, $matches)) {
            return [
                'event' => null,
                'text' => $matches[1]
            ];
        }

        // if failed all, manual return
        Log::error('Gemini response cannot be parsed');
        return [
            'event' => null,
            'text' => 'Sorry, I am unable to process your request. Please try again in a moment!',
        ];

    }
}