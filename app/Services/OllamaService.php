<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaService
{
    public function generate($prompt, $model = null, $stream = false)
    {
        $model = $model ?: env('OLLAMA_DEFAULT_MODEL');

        
        $response = Http::timeout(60)
            ->post(env('OLLAMA_HOST') . '/api/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => $stream
            ]);

        return $response->json();
    }
}