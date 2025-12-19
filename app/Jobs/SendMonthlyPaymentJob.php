<?php

namespace App\Jobs;

use App\Models\Telegrambot;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Contract;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMonthlyPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct($tenantId)
    {
        //
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramBotService $telegramBotService): void
    {
        //
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant || ! $tenant->telegram_id) {
            Log::info('Tenant is not found / telegram id is missing', [
                'tenant_id' => $this->tenantId
            ]);
            return;
        }

        //
        $activeContract = Contract::where('tenant_id', $tenant->id)->where('status', 'active')->first();
        if (! $activeContract) {
            Log::info('active contract is not found for tenant', [
                'tenant_id' => $this->tenantId
            ]);
            return;
        }
        
        $landlordId = $tenant->landlord_id;
        $chatId = $tenant->telegram_id;

        // deine bot for send msg
        $bot = Telegrambot::where("user_id", $landlordId)->first();
        if (! $bot) return;

        // define button for open mini app
        $button = $telegramBotService->getWebAppButton('📲 Open Mini App To Scan');

        // define message
        $message = "💡 Monthly rent reminder: \n"
        . "━━━━━━━━━━━━━━━━━━━━\n"
        . "Tenant: {$tenant->first_name} {$tenant->last_name}\n"
        . "Room: " . ($activeContract->room ? $activeContract->room->name : 'N/A') . "\n"
        . "Please  tap the button below to scan meters and submit payment request.";

        $telegramBotService->sendMessage($bot, $chatId, $message, $button);

        Log::info('Monthly payment reminder sent', [
            'tenant_id' => $this->tenantId,
            'landlord_id' => $landlordId,
            'chat_id' => $chatId,
            'bot_username' => $bot->username
        ]);

    }
}
