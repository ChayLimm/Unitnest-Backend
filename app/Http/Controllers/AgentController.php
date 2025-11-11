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


class AgentController extends Controller
{
    protected $ollamaService;
    protected $geminiService;

    public function __construct(OllamaService $ollamaService, GeminiService $geminiService)
    {
        $this->ollamaService = $ollamaService;
        $this->geminiService = $geminiService;
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

    public function handleWebhook(Request $request, $token)
    {
        Log::info('webhook triggered for token: ' . $token);

        // check bot by token
        $bot = Telegrambot::where('token', $token)->first();
        Log::info('Queried Telegrambot:', ['bot' => $bot]);

        if (!$bot) {
            Log::warning("No Telegrambot found for token: {$token}");
        }

        // owner of telegrambot / landlord - user_id
        $landlordId = $bot->user_id ?? null;

        //
        $update = $request->all(); 
        Log::info('webhook payload:', ['payload' => $update]);

        $message = $update['message']['text'] ?? null;
        $chatId = $update['message']['chat']['id'] ?? null;

        if (!$message || !$chatId) {
            Log::info('No message or chat ID found');
            return response('ok', 200);
        }

        Log::info("Message: {$message}, Chat: {$chatId}");

        // Handle /start
        if ($message === '/start') {
            $welcomeMessage = "🏠 Welcome to Lomnov Agent!\n\n" .
                              "I'm here to help you with:\n" .
                              "💳 Payments\n" .
                              "📋 Property Rules\n" .
                              "💬 Rental Questions\n\n" .
                              "How can I assist you today?";

            $this->sendMessage($bot, $chatId, $welcomeMessage);
            return response('ok', 200);
        }

        $prompt = $this->buildPrompt($landlordId, $chatId, $message);

        // Call Gemini AI service
        $aiResponse = $this->geminiService->generate($prompt);
        Log::info('Gemini Response:', ['response' => $aiResponse]);

        // Parse Gemini response
        $formatResponse = $this->parseGeminiResponse($aiResponse);
        Log::info('Parsed Response:', ['formatResponse' => $formatResponse]);

        // handle reponse event 
        if (!empty($formatResponse['event'])) {
            Log::info('Calling handleFunction for event: ' . $formatResponse['event']);
            $reply = $this->handleFunction( $landlordId, $chatId, $formatResponse['event']);

        } else {
            Log::info('Using Gemini text response - event is null or empty');
            $reply = $formatResponse['text'] ?? "Sorry, I could not understand!";
        }

        // send reply back to Telegram
        $this->sendMessage($bot, $chatId, $reply);

        return response('ok', 200);
    }

    private function parseGeminiResponse($response)
    {
        Log::info('=== Starting parseGeminiResponse ===');
        Log::info('Original Gemini response:', ['response' => $response]);
        
        // 1: Remove markdown wrappers
        $cleaned = preg_replace('/```json\s*|\s*```/', '', $response);
        $cleaned = trim($cleaned);
        
        Log::info('Cleaned response:', ['cleaned' => $cleaned]);
        
        // 2: Decode JSON
        $decoded = json_decode($cleaned, true);
        
        // 3: Check if decoded, JSON with event and text
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['text'])) {
            Log::info('✅ JSON parsed successfully', $decoded);
            return [
                'event' => $decoded['event'] ?? null,
                'text' => $decoded['text'] ?? 'Processing...'   
            ];
        }
        
        // 4: JSON failed - use fallback
        Log::warning('JSON parse failed, using fallback', ['error' => json_last_error_msg()]);
        
        if (preg_match('/"text"\s*:\s*"([^"]+)"/', $response, $matches)) {
            return [
                'event' => null,
                'text' => $matches[1]
            ];
        }
        
        return [
            'event' => null,
            'text' => 'I can help you with property management questions! 😊'
        ];
    }

    private function handleFunction($landlordId ,$chatId, $event)
    {
        switch ($event) {
            case 'checkGeneralRule':
                return $this->checkGeneralRule($landlordId, $chatId);
                
            case 'checkContractRule':
                return $this->checkContractRule($landlordId, $chatId);
                
            case 'checkAvailableRoom':
                return $this->checkAvailableRoom($landlordId, $chatId);

            case 'makePayment':
                return $this->makePayment($landlordId,$chatId);

            case 'registration':
                return $this->registration($landlordId, $chatId);
                
            default:
                Log::warning("Unknown event: {$event}");
                return "Sorry, I couldn't process the request!";
        }
    }

    // handle callback queries
    private function handleCallbackQuery($landlordId ,$chatId, $data)
    {
        //
    }


    private function registration($chatId)
    {
        return "📝 Registration Process\n\n";
    }


    private function makePayment($chatId)
    {
        return "💳 Payment Process\n\n" .
               "To make your rental payment, pls select methods:\n" .
               "1. Send me a picture of your electricity meter and water meter.\n" .
               "2. Visit: link\n";
    }


    private function checkGeneralRule($landlordId, $chatId)
    {
        // query settings table for general_rules by user_id
        $generalRule = null;
        if ($landlordId) {
            $setting = Setting::where('user_id', $landlordId)->first();
            if ($setting && !empty($setting->general_rules)) {
                $generalRule = trim((string) $setting->general_rules);
            }
        }
        //
        $header = "📋 Property General Rules\n";
        $footer = "Need more information? Just ask! 😊";
        $seperator = "━━━━━━━━━━━━━━━━━━━━\n";
        //
        if (empty($generalRule)) {
            // $generalRule = "No general rules found for this property!.";
            $generalRule = "🏠 Rental Guidelines:\n" .
               "• Rent is due on the 1st of each month 📅\n" .
               "• Late payment penalty applies after 5 days ⚠️\n";

        }

        return $header . $seperator . $generalRule ."\n". $seperator .$footer;
    }


    private function checkContractRule($landlordId, $chatId)
    {   
        // query settings table for contract_rules by user_id
        $contractRule = null;
        if ($landlordId) {
            $setting = Setting::where('user_id', $landlordId)->first();
            if ($setting && !empty($setting->contract_rules)) {
                $contractRule = trim((string) $setting->contract_rules);
            }
        }
        //
        $header = "📄 Contract Rules\n";
        $footer = "Have questions about your contract? Please contact us! 🤝";
        $seperator = "━━━━━━━━━━━━━━━━━━━━\n";
        //
        if (empty($contractRule)) {
            // $contractRule = "No contract rules found for this property!.";
            $contractRule = "⚖️ Legal Terms:\n" .
               "• Contract duration: 12 months minimum 📆\n";
        }

        return $header . $seperator . $contractRule ."\n". $seperator .$footer;
    }


    private function checkAvailableRoom($landlordId, $chatId)
    {   
        // user (id) -> buidling (landlord_id-fk, id-pk) -> rooms (building_id-fk, status-available)
        $availableRooms = [];
        $buildings = Building::where('landlord_id', $landlordId)->get();
        foreach ($buildings as $building) {
            $availableCount = Room::where('building_id', $building->id)
                                       ->where('status', 'available')
                                       ->count();
            if ($availableCount > 0) {
                $availableRooms[] = "• {$building->name}: {$availableCount} rooms available\n";
            }
        }
        // 
        $header = "🏠 Available Rooms\n";
        $footer = "For more details or to book a room, please contact your landlord. 😊";
        $seperator = "━━━━━━━━━━━━━━━━━━━━\n"; 
        //
        $body = !empty($availableRooms)
                ? implode("\n", $availableRooms)
                : "No rooms are currently available!.";

        return $header . $seperator . $body ."\n". $seperator .$footer;
    }

    private function sendMessage($bot, $chatId, $text)
    {
        // $token = env('AGENT_BOT_TOKEN');
        $token = $bot->token;
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $formattedText = str_replace('\\n', "\n", $text);

        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $formattedText,
                'parse_mode' => 'HTML',
            ]);
            
            // 
            $responseData = $response->json();
            Log::info('Telegram API Response:', ['response' => $responseData]);
            // Log::info("Message sent successfully to chat: {$chatId}");
            
        } catch (\Exception $e) {
            Log::error("Failed to send message: " . $e->getMessage());
        }
    }

    public function setUpWebhook(Request $request, $token, AgentService $service)
    {
        $result = $service->setWebhook($token);
        Log::info('Set Webhook Result:', ['result' => $result]);

        return response()->json($result);
    }

    // handle build prompt for gemini
    private function buildPrompt($landlordId, $chatId, $message) 
    {
        // 1. Landlord's rental data
        // 2. Agent behavior  
        // 3. Available functions
        $landlordPhone = "N/A";
        
        return "You are a property rental assistant. Answer briefly (max 3 lines).

                AVAILABLE DATA:
                - Contact: {$landlordPhone}

                FUNCTIONS (choose event based on user question):
                - checkGeneralRule → property rules
                - checkContractRule → contract terms
                - checkAvailableRoom → room availability

                RESPONSE RULES:
                1. Always respond in this JSON format:
                {\"event\": \"functionName or null\", \"text\": \"your short message\"}

                2. When to use each event:
                - \"checkGeneralRule\" → questions about rules, regulations, property guidelines
                - \"checkContractRule\" → questions about contract, agreement, legal terms
                - \"checkAvailableRoom\" → questions about rooms, vacancy, availability
                - null → greetings (hi/hello), help requests, general question related, off-topic

                3. Use \\n for line breaks. Use bullet points (•) for lists.
                4. Add emojis for friendliness: 🏠 📋 📄 💳 👋 😊
                5. If you don't have information about something, politely say 'I don't have that information yet'.
                
                HELP/MENU REQUESTS:
                If the user asks for help, what you can do, menu, options, features, how you can help, or similar:
                Respond with a list of all available functions above, using bullet points or emojis.

                USER QUESTION: {$message}
                Respond in JSON only.";
    }

}