<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
    protected $agentService;
    protected $telegramBotService;

    public function __construct(AgentService $agentService, TelegramBotService $telegramBotService)
    {
        $this->agentService = $agentService;
        $this->telegramBotService = $telegramBotService;
    }

    public function setUpWebhook(Request $request)
    {
        Log::info('=== setUpWebhook METHOD CALLED ===', [
            'all_data' => $request->all(),
        ]);

        try {
            $validated = $request->validate([
                'token' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $message = 'Validation failed';
            if (isset($errors['user_id'])) {
                $message = 'Invalid user_id: user does not exist or is not valid';
            }
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        $token = $validated['token'];
        $landlordId = $validated['user_id'];

        try {
            $result = $this->telegramBotService->setUpBot($token, $landlordId);
            return response()->json($result, 200);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 409);  // conflict error - already exists
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
