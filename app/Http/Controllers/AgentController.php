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
        Log::info("Processing: {$message} for chat: {$chatId}");

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

        $prompt = $this->buildPrompt($chatId, $message);

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
        Log::info('Original response:', ['response' => $response]);
        
        // 1: remove markdown and fix control characters
        $cleanResponse = trim($response);
        $cleanResponse = str_replace(['```json', '```'], '', $cleanResponse);
        
        // 2: fix control characters and newlines
        $cleanResponse = preg_replace('/\s+/', ' ', $cleanResponse);
        $cleanResponse = str_replace(['\n', '\r', '\t'], '', $cleanResponse); 
        $cleanResponse = trim($cleanResponse);
        
        Log::info('Cleaned response:', ['cleaned' => $cleanResponse]);
        
        // 3: decode JSON
        $json = json_decode($cleanResponse, true);
        
        // 4: Check if decoded
        if ($json && isset($json['event'])) {
            Log::info('✅ JSON parsed successfully!', ['event' => $json['event'], 'text' => $json['text']]);
            return [
                'event' => $json['event'],
                'text' => $json['text'] ?? 'Processing...'
            ];
        }
        
        // 5: show log & use fallback if failed
        Log::info('❌ JSON parsing failed - using fallback');
        Log::info('JSON error:', ['error' => json_last_error_msg()]);
        
        return [
            'event' => null,
            'text' => $this->cleanGeminiResponse($response)
        ];
    }

    // method to clean Gemini response 
    private function cleanGeminiResponse($response)
    {
        $clean = str_replace(['```json', '```', '{', '}', '"event":', '"text":', 'null,', '"', 'normalResponse', 'normalResponse:'], '', $response);
        $clean = str_replace(['"', ',', ':'], '', $clean);
        $clean = trim($clean);
        
        if (empty($clean) || strlen($clean) < 10) {
            return "I can help you with property management questions! 😊";
        }
        
        return $clean;
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
                return $this->makePayment($bot = null,$chatId);

            case 'registration':
                return $this->registration($bot = null, $chatId);
                
            default:
                Log::warning("Unknown event: {$event}");
                return "Sorry, I couldn't process the request!";
        }
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
        return response()->json($result);
    }

    // handle build prompt for gemini
    private function buildPrompt($chatId, $message) 
    {
        // 1. Landlord's rental data
        // 2. Agent behavior  
        // 3. Available functions
        
        //sample mock data
        $availableRoom = "3 rooms available in the city center";
        
        return "system_name: PropertyAgentAI
                version: 1.0
                language: English
                domain: Property Management
                mode: assistant
                politeness: high
                default_format: json
                confirm_sensitive_actions: true

                ---

                You are **PropertyAgentAI**, an intelligent assistant representing our **Property Management Company**.  
                Your purpose is to assist clients **only** with topics related to **property management**, such as room availability, rental rules, and contracts.  
                Always reply **respectfully**, **professionally**, and stay **on-topic** — ignore unrelated requests.

                **IMPORTANT**: Keep responses SHORT and CONCISE. Always complete your sentences but be brief to save tokens. Never cut sentences short. Use emojis and line breaks for better readability.

                ---

                ### Data Context:
                - availableRoom: {$availableRoom}

                ### Available Functions:
                - `checkGeneralRule` 
                - `checkContractRule` 
                - `checkAvailableRoom`

                ### Event Selection Rules:
                - `checkGeneralRule` when user asks about general/property rules
                - `checkContractRule` when user asks about contract rules
                - `checkAvailableRoom` when user asks about room availability
                - `null` for: greetings (hi, hello), help requests (what can you do, help me, help, any info), general questions,rental questions, when user asks 'check rule' without specifying type, property-related questions not covered by specific functions, or off-topic requests

                ---

                ### Response Rules:
                1. All responses **must** be in JSON format.  
                2. Use the `\"event\"` field to indicate the function you used (if any).  
                3. Use the `\"text\"` field for your human-readable response message.  
                4. If no function is required, set `\"event\": null`.
                5. **Always complete your sentences** - don't cut them off.
                6. Use emojis and line breaks (\n) to make responses more engaging but don't overuse them.
                7. Be helpful and provide clear guidance to users.

                ### When event is null, provide helpful responses:
                - **For greetings**: Welcome them warmly and mention services
                - **For help requests**: List available functions: General Rules, Contract Rules, Room Availability
                - **For general questions**: Provide relevant property management guidance
                - **For off-topic**: Politely redirect to property management topics
                ---

                ### Example Responses:

                ```json
                {
                    \"event\": \"checkAvailableRoom\",
                    \"text\": \"🏠 Great!! \n\n📍 We have 3 rooms in the city center.\n\nWould you like more details? 😊\"
                }
                ```
                ---

                User message: {$message}
                Respond in JSON format only.";
    }

}