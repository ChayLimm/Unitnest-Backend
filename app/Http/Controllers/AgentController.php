<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OllamaService;
use App\Services\GeminiService;
use App\Services\AgentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use App\Models\Telegrambot;
use App\Models\Setting;
use App\Models\Building;
use App\Models\Room;
use App\Services\TelegramBotService;

class AgentController extends Controller
{
    protected $ollamaService;
    protected $agentService;
    protected $telegramBotService;

    public function __construct(OllamaService $ollamaService, AgentService $agentService, TelegramBotService $telegramBotService)
    {
        $this->ollamaService = $ollamaService;
        $this->agentService = $agentService;
        $this->telegramBotService = $telegramBotService;
    }

    public function generateResponse(Request $request)
    {
        $prompt = $request->input('prompt');
        $model = $request->input('model', null);

        if (!$prompt) {
            return response()->json(['error' => 'Prompt is required'], 400);
        }

        $response = $this->ollamaService->generate($prompt, $model);
        return response()->json($response);
    }

    public function setUpWebhook(Request $request)
    {
        Log::info('=== setUpWebhook METHOD CALLED ===', [
            'all_data' => $request->all(),
        ]);

        $validated = $request->validate([
            'token' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $token = $validated['token'];
        $landlordId = $validated['user_id'];

        // Get bot info
        $botInfo = $this->telegramBotService->getBotInfo($token);
        $botData = $botInfo['result'] ?? [];

        if (!isset($botInfo['result']['id'])) {
            return response()->json(['error' => 'Invalid bot token'], 400);
        }

        try {
            // save bot to db 
            $bot = $this->telegramBotService->storeBot($token, $landlordId, $botData);

            // set up webhook
            $responseWebhook = $this->telegramBotService->setWebhook($token);

            Log::info('Set up Telegram Webhook successfully', [
                'user_id' => $landlordId,
                'token' => substr($token, 0, 8),
                'bot_id' => $bot->id,
            ]);

            return response()->json([
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
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 409);    // conflict error - already exists
        }
    }

    // hanlde telegram webhook - incoming messages
    public function handleWebhook(Request $request, $token)
    {
        Log::info('webhook triggered for token: ' . $token);

        // check bot by token
        $bot = Telegrambot::where('token', $token)->first();
        Log::info('Queried Telegrambot:', ['bot' => $bot]);

        if (!$bot) {
            Log::warning("Telegram Bot not found for token: {$token}");
            return response('ok', 200);
        }

        $update = $request->all();
        Log::info('webhook payload:', ['payload' => $update]);

        // check message or callback_query
        if (isset($update['message']['text'])) {
            $this->agentService->handleTextMessage($bot, $update['message']);
            return response('ok', 200);
        }

        if (isset($update['callback_query'])) {
            $this->agentService->handleCallbackQuery($bot, $update['callback_query']);
            return response('ok', 200);
        }

        // unknown update type
        Log::info('unknown update type:', ['update' => $update]);
        return response('ok', 200);
    }


    // handle inline button with ai reponse text 
}
