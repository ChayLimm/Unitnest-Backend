<?php

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use App\Services\FormService;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Services\NotificationService;

class FormController extends Controller
{

    protected $formService;
    protected $notificationService;
    // protected $agentService;

    public function __construct(FormService $formService, NotificationService $notificationService)
    {
        $this->formService = $formService;
        $this->notificationService = $notificationService;
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

        // call agent service to process data
        $result = $this->notificationService->handleRegistrationSubmmision($data);

        return response()->json($result, $result['success'] ? 200 : 500);

    }

}
