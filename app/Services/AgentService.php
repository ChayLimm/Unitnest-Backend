<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;
use App\Services\TelegramBotService;
use App\Services\FormService;
use App\Models\User;
use App\Models\Telegrambot;
use App\Models\Setting;
use App\Models\Building;
use App\Models\Room;
use App\Models\Notification;
Use App\Enums\NotificationType;
use App\Enums\NotificationStatus;

class AgentService
{
    protected $geminiService;
    protected $telegramBotService;
    protected $formService;

    public function __construct(GeminiService $geminiService, TelegramBotService $telegramBotService, FormService $formService)
    {
        $this->geminiService = $geminiService;
        $this->telegramBotService = $telegramBotService;
        $this->formService = $formService;
    }

    // ============ Handle Messages ============
    public function handleTextMessage($bot, $message){
        $text = $message['text'] ?? null;
        $chatId = $message['chat']['id'] ?? null;
        $landlordId = $bot->user_id ?? null;    // owner of telegrambot - landlord (user_id)

        if (!$text || !$chatId) {
            Log::info('Message text or chat ID found.');
            return;
        }

        Log::info("Text Message: {$text}, Chat: {$chatId}");

        // Handle /start command
        if ($text === '/start') {
            $this->welcomeMessage($bot, $chatId);
            return;
        }

        // testing button mini app with pay
        if ($text === '/pay') {
            $buttons = $this->telegramBotService->getWebAppButton();
            $this->telegramBotService->sendMessage($bot, $chatId, 'Click below to process:', $buttons);
            return;
        }

        $normalizeText = $this->normalizeUserMessage($text);
        $prompt = $this->buildPrompt($landlordId, $chatId, $normalizeText);

        // Call Gemini AI service
        $aiResponse = $this->geminiService->generate($prompt);
        Log::info('Gemini Response:', ['response' => $aiResponse]);

        // Normalize Gemini response
        $formatResponse = $this->geminiService->normalizeResponse($aiResponse);
        Log::info('Parsed Response:', ['formatResponse' => $formatResponse]);

        // handle reponse event/null 
        if (!empty($formatResponse['event'])) {
            Log::info('Calling handleEvent: ' . $formatResponse['event']);
            $reply = $this->handleEvent($landlordId, $chatId, $formatResponse['event']);
        } else {
            Log::info('Using Gemini text response - event is null or empty');
            $reply = $formatResponse['text'] ?? "Sorry, I could not understand!";
        }

        // send reply back to Telegram
        if ($reply) {
            $this->telegramBotService->sendMessage($bot, $chatId, $reply);
        }
    }

    public function handleCallbackQuery($bot, $callbackQuery){
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $data = $callbackQuery['data'] ?? null;
        $callbackId = $callbackQuery['id'] ?? null;
        $reply = null;

        // owner of telegrambot / landlord - user_id
        $landlordId = $bot->user_id ?? null;

        Log::info('Callback Query:', ['data' => $data, 'chatId' => $chatId]);
        if (!$data || !$chatId) {
            Log::info('Callback data or chat ID found.');
            return;
        }

        $this->telegramBotService->answerCallbackQuery($bot, $callbackId);

        switch ($data) {
            case 'general_rules':
                $reply = $this->getGeneralRule($landlordId, $chatId);
                break;

            case 'contract_rules':
                $reply = $this->getContractRule($landlordId, $chatId);
                break;

            case 'available_rooms':
                $reply = $this->getAvailableRoom($landlordId, $chatId);
                break;

            case 'registration':
                $reply = $this->getRegistration($landlordId, $chatId);
                break;

            case 'make_payment':
                $reply = $this->makePayment($landlordId, $chatId);
                break;
            
            case 'contact_landlord':
                $reply = $this->getLandlordContact($landlordId, $chatId);
                break;

            default:
                $reply = "Unknown action, please try again!.";
        }

        // send reply back to Telegram
        if ($reply) {
            $this->telegramBotService->sendMessage($bot, $chatId, $reply);
        }
    }

    // ============ Handle Match Functions (Event) ============
    private function handleEvent($landlordId, $chatId, $event){
        switch ($event) {
            case 'checkGeneralRule':
                return $this->getGeneralRule($landlordId, $chatId);

            case 'checkContractRule':
                return $this->getContractRule($landlordId, $chatId);

            case 'checkAvailableRoom':
                return $this->getAvailableRoom($landlordId, $chatId);

            case 'makePayment':
                return $this->makePayment($landlordId, $chatId);

            case 'registration':
                return $this->getRegistration($landlordId, $chatId);

            default:
                Log::warning("Unknown event: {$event}");
                return "Sorry, I couldn't process the request!";
        }
    }

    // ============ Business Logic ============
    private function getGeneralRule($landlordId, $chatId){
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
            $generalRule = "No general rules found for this property!.";
            // $generalRule = "🏠 Rental Guidelines:\n" .
            //     "• Rent is due on the 1st of each month 📅\n" .
            //     "• Late payment penalty applies after 5 days ⚠️\n";
        }

        return $header . $seperator . $generalRule . "\n" . $seperator . $footer;
    }

    private function getContractRule($landlordId, $chatId){
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
            $contractRule = "No contract rules found for this property!.";
            // $contractRule = "⚖️ Legal Terms:\n" .
            //     "• Contract duration: 12 months minimum 📆\n";
        }

        return $header . $seperator . $contractRule . "\n" . $seperator . $footer;
    }

    private function getAvailableRoom($landlordId, $chatId){
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

        return $header . $seperator . $body . "\n" . $seperator . $footer;
    }

    private function getRegistration($landlordId, $chatId){

        //
        $header = "📝 Registration Process\n" . "Please fill out the registration form here:\n";
        $footer = "We'll notify your landlord after you submit 😊";
        $seperator = "━━━━━━━━━━━━━━━━━━━━\n";
        
        // mention linnk 
        $link = $this->formService->getPrefillLink($landlordId, $chatId);
        $mention = "<a href=\"{$link}\">Registration Form</a>";

        // Check if user already registered
        $notification = Notification::Where('chat_id', $chatId)
            ->where('landlord_id', $landlordId)
            ->where('notification_type', NotificationType::REGISTRATION->value)
            ->orderByDesc('created_at')
            ->first();
        
        if ($notification) {
            switch ($notification->status){
                case NotificationStatus::PENDING->value:
                    return "🕒 Registration is Processing...\n".$seperator."Your registration is pending for landlord approval, Please wait for confirmation.";
                
                case NotificationStatus::APPROVED->value:
                    return "✅ You are already registered!\n".$seperator."If you need to update your info, please contact your landlord.";
                
                case NotificationStatus::REJECTED->value:
                    // allow to re-register
                    return "❌ Your previous registration was rejected.\n\n" .
                        $header . $seperator . $mention . "\n" . $seperator . $footer;
                
            }
        }

        return $header . $seperator . $mention . "\n" . $seperator . $footer;
    }

    private function makePayment($landlordId, $chatId){
        // mock

        return "💳 Payment Process\n\n" .
            "To make your rental payment, pls select methods:\n" .
            "1. Send me a picture of your electricity meter and water meter.\n" .
            "2. Visit: link\n";
    }

    private function getLandlordContact($landlordId, $chatId){
        if ($landlordId) {
            $landlord = User::find($landlordId);
            if ($landlord && !empty($landlord->phonenumber)) {
                $phone = $landlord->phonenumber;
                return "📞 Contact Landlord: {$phone}";
            }
        }
        return "Sorry, landlord contact information is not available.";
    }

    // ============ Helpers Function ============
    private function welcomeMessage($bot, $chatId)
    {
        $message = "🏠 Welcome to Lomnov Agent!\n\n" .
            "I'm here to help you with:\n" .
            "🏠 Available rooms\n" .
            "📋 Property Rules\n" .
            "💬 Rental Questions\n\n" .
            "How can I assist you today?";

        $btn = $this->telegramBotService->getButtons();
        $buttons = [
            [$btn['generalRuleBtn'], $btn['contractRuleBtn']],
            [$btn['availableRoomBtn'],$btn['contactLandlordBtn']],
            [$btn['registrationBtn']],
        ];

        $this->telegramBotService->sendMessage($bot, $chatId, $message, $buttons);
    }

    // normalize user input text message for gemini ai
    private function normalizeUserMessage($text){

        $text = trim($text);
        $text = mb_strtolower($text, 'UTF-8'); // convert to lowercase

        // remove emojis, non-standard characters, extra spaces
        $text = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return $text;
    }

    // handle build prompt for gemini
    private function buildPrompt($landlordId, $chatId, $message)
    {
        // 1. Landlord's rental data
        // 2. Agent behavior  
        // 3. Available functions
        $landlordPhone = $this->getLandlordContact($landlordId, $chatId) ?? "N/A";

        return "ROLE:
                You are a property rental assistant. Help users with property services quickly and professionally.

                CONTEXT:
                - Landlord Contact: {$landlordPhone}

                TASK:
                Classify the user's request into one event and provide a brief helpful reply.

                EVENTS:
                - checkGeneralRule: rules/property policies, what are the rules
                - checkContractRule: contract/agreement rule, terms
                - checkAvailableRoom: rooms, available
                - registration: register, apply, sign up, become tenant, get registration form
                - null: greetings (hi/hello), help/menu (what can you do), unclear, off-topic

                OUTPUT FORMAT:
                {\"event\": \"eventName or null\", \"text\": \"reply\"}

                RULES:
                - Keep reply short (max 3 lines).
                - Use \\n for breaks, • for lists, emojis for tone
                - For general questions: event=null, provide helpful answer
                - For help/menu: event=null, list all services
                - If unsure: event=null, polite response
                - Never repeat user input
                - Never include markdown or extra text outside JSON.

                EXAMPLES:
                User Message: \"hi\"
                {\"event\": null, \"text\": \"Hello! 👋 How can I help you today?\"}
                
                USER MESSAGE: \"{$message}\"
                Respond with JSON only.
            ";
    }
}
