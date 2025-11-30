<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Telegrambot;

class TelegramBotService
{

    // ============ Inline Buttons ============
    public function getButtons()
    {
        return [
            'generalRuleBtn' => [
                'text' => '📋 General Rules', 
                'callback_data' => 'general_rules'
            ],
            'contractRuleBtn' => [
                'text' => '📄 Contract Rules', 
                'callback_data' => 'contract_rules'
            ],
            'availableRoomBtn'=> [
                'text' => '🏠 Available Rooms',
                'callback_data' => 'available_rooms'
            ],
            'registrationBtn'=> [
                'text'=> '📝 Registration',
                'callback_data'=> 'registration'
            ],
            'PaymentBtn' => [
                'text' => '💰 Pay Now',
                'callback_data'=> 'make_payment'
            ],
            'contactLandlordBtn' => [
                'text' => '📞 Contact Landlord',
                'callback_data' => 'contact_landlord'
            ],
        ];
    }

    // ============ Telegram API Call ============

    public function setWebhook($token)
    {
        $baseUrl = env('BASE_URL');
        // $baseUrl = config('app.url');
        $webhookUrl = "{$baseUrl}/api/agent/webhook/{$token}";
        $botToken = $token;

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        return $response->json();
    }

    public function sendMessage($bot, $chatId, $text, $buttons = null)
    {
        // $token = env('AGENT_BOT_TOKEN');
        $token = $bot->token;
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $formatText = str_replace('\\n', "\n", $text);

        $payload = [
            'chat_id' => $chatId,
            'text' => $formatText,
            'parse_mode' => 'HTML',
        ];

        if ($buttons !== null && !empty($buttons)) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $buttons
            ]);
        }

        try {
            $response = Http::post($url, $payload);

            // 
            $responseData = $response->json();
            Log::info('Telegram API Response:', ['response' => $responseData]);
            Log::info('Message sent', [
                'chat_id' => $chatId,
                'buttons' => $buttons !== null
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send message: " . $e->getMessage());
        }
    }

    public function answerCallbackQuery($bot, $callbackId)
    {
        $token = $bot->token;
        $url = "https://api.telegram.org/bot{$token}/answerCallbackQuery";

        try {
            Http::post($url, [
                'callback_query_id' => $callbackId,
            ]);
            Log::info('Callback answered', context: ['callback_id' => $callbackId]);
        } catch (\Exception $e) {
            Log::error("Failed to answer callback: " . $e->getMessage());
        }
    }

    public function getBotInfo($token)
    {
        $url = "https://api.telegram.org/bot{$token}/getMe";

        try {
            $response = Http::get($url);
            return $response->json();

        } catch (\Exception $e) {
            Log::error("Failed to get bot info: " . $e->getMessage());
            return null;
        }
    }
    
    public function storeBot($token, $userId, $botData)
    {
        // Check if bot already exists
        $existingBot = Telegrambot::where('token', $token)->first();
        
        if ($existingBot) {
            throw new \Exception('Bot with this token is already registered');
        }

        // Create new bot
        return Telegrambot::create([
            'token' => $token,
            'user_id' => $userId,
            'bot_id' => $botData['id'] ?? null,
            'username' => $botData['username'] ?? null,
            // 'first_name' => $botData['first_name'] ?? null,
        ]);
    }

    // handle set up bot
    public function setUpBot($token, $landlordId){

        // Get bot info
        $botInfo = $this->getBotInfo($token);
        $botData = $botInfo['result'] ?? [];

        if (!isset($botInfo['result']['id'])) {
            throw new \Exception('Invalid bot token');
        }

        // Save bot to db 
        $bot = $this->storeBot($token, $landlordId, $botData);

        // Set up webhook
        $responseWebhook = $this->setWebhook($token);

        Log::info('Set up Telegram Webhook successfully', [
            'user_id' => $landlordId,
            'token' => substr($token, 0, 8),
            'bot_id' => $bot->id,
        ]);

        return [
            'success' => true,
            'message' => 'Bot registered successfully',
            'webhook_response' => $responseWebhook,
            'bot_info' => $botInfo,
            'bot' => [
                'id' => $bot->id,
                'token' => $bot->token,
                'bot_id' => $bot->bot_id,
                'username' => $bot->username,
                'user_id' => $bot->user_id,
            ],
        ];
    }

}
