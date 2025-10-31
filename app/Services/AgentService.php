<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class AgentService
{
    public function setWebhook($token)
    {
        $baseUrl = env('BASE_URL');
        // $baseUrl = config('app.url');
        $webhookUrl = "{$baseUrl}/api/agent/{$token}";
        $botToken = $token;

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        return $response->json();
    }
}