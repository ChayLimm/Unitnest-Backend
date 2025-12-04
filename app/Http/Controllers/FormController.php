<?php

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use App\Services\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;

class FormController extends Controller
{

    protected $formService;
    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }

    public function getFormPrefillLink(Request $request){

        $validated = $request->validate([
            'landlord_id' => 'required|integer',
            'chat_id' => 'required|integer',
        ]);

        if ($validated === null) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid input parameters'
            ], 422);
        }

        $landlordId = $validated['landlord_id'];
        $chatId = $validated['chat_id'];

        try {
            $link = $this->formService->getPrefillLink($landlordId,  $chatId);

            Log::info('Prefill link generated for: ', [
                'landlord_id' => $request->landlord_id,
                'chat_id' => $request->chat_id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'link' => $link
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // handle webhook from google form submission
    public function handleFormSubmission(Request $request){

        $data = $request->all();

        Log::info('Received form submission webhook:', ['data' => $data]);

        // extract data
        $fields = $data['fields'] ?? [];
        $timestamp = $data['timestamp'] ?? null;
        $responseId = $data['response_id'] ?? null;

        // extract data fields from "fields" object
        $name = $fields['Full Name'] ?? null;
        $phone = $fields['Phone Number'] ?? null;
        $chatId = $fields['Tenant Chat ID'] ?? null;
        $landlordId = $fields['Landlord ID'] ?? null;
        $identityCardIds = $fields['Identity Card ID'] ?? null;
        
        // Get file URLs (from itemResponses loop)
        $identityCardUrls = $fields['Identity Card ID_urls'] ?? [];
        $identityCardUrl = !empty($identityCardUrls) ? $identityCardUrls[0] : null;

        // log the extract data to check
        Log::info('Processing submission:', [
            'timestamp' => $timestamp,
            'response_id' => $responseId,
            'name' => $name,
            'phone' => $phone,
            'landlord_id' => $landlordId,
            'chat_id' => $chatId,
            'identity_card_url' => $identityCardUrl,
            'identity_card_id' => $identityCardIds,
        ]);

        // TODO: Save to database
        $notification = Notification::create([
            'landlord_id' => $landlordId,
            'chat_id' => $chatId,
            'read' => false,
            'notification_type' => NotificationType::REGISTRATION,
            'status' => NotificationStatus::PENDING,
            'payload' => [
                'name' => $name,
                'phone' => $phone,
                'identity_card_url' => $identityCardUrl,
            ],
            
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Form submission received',
            'notification_id' => $notification->id,
        ], 200);

    }

}
