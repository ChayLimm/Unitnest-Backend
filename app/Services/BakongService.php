<?php

namespace App\Services;

use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\SourceInfo;
use KHQR\Models\IndividualInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Payment;
use App\Models\Transaction;
use App\Enums\PaymentStatus;
use App\Models\TelegramBot;
class BakongService
{
    protected string $bakong_account;
    protected string $bakong_merchant_name;
    protected string $bakong_merchant_city;
    protected string $bakong_mobile_number;

    public function __construct(){
        $this->bakong_account = config('bakong.merchant_id');
        $this->bakong_merchant_name = config('bakong.merchant_name');
        $this->bakong_merchant_city = config('bakong.merchant_city');
        $this->bakong_mobile_number = config('bakong.mobile_number');
    }

    public function setBakongAccount(array $accountInfo){
        $this->bakong_account = $accountInfo['bakong_account'] ?? $this->bakong_account;
        $this->bakong_merchant_name = $accountInfo['bakong_merchant_name'] ?? $this->bakong_merchant_name;
        $this->bakong_merchant_city = $accountInfo['bakong_merchant_city'] ?? $this->bakong_merchant_city;
        $this->bakong_mobile_number = $accountInfo['bakong_mobile_number'] ?? $this->bakong_mobile_number;
    }

    public function setBakongAccountFromLandlord(int $landlord_id){
        $bakongAccount = \App\Models\BakongAccount::where('landlord_id', $landlord_id)->first();
        
        if (!$bakongAccount) {
            // Log::warning('Bakong account not found for landlord ' . $landlord_id . ', using default.');
            // throw new \Exception('Bakong account not found for this landlord');
            return; // Or throw exception if strictly required
        }

        $this->bakong_account = $bakongAccount->bakong_id;
        $this->bakong_merchant_name = $bakongAccount->bakong_name;
        $this->bakong_merchant_city = $bakongAccount->bakong_location;
    }

    public function generateKHQR(float $amount)
    {
        DB::beginTransaction();

        try{
            Log::info("Starting KHQR generation for amount: {$amount}", [
                'merchant_name' => $this->bakong_merchant_name,
                'bakong_account' => $this->bakong_account
            ]);

            $individualInfo = new IndividualInfo(
                bakongAccountID: $this->bakong_account,
                merchantName: $this->bakong_merchant_name,
                merchantCity: $this->bakong_merchant_city,
                currency: KHQRData::CURRENCY_USD,
                amount: $amount
            );

            $response = BakongKHQR::generateIndividual($individualInfo);
            $qr = $response->data['qr'] ?? null;
            $md5 = $response->data['md5'] ?? null;

            if (!$qr || !$md5) {
                throw new \Exception('Invalid KHQR generation response.');
            }

            $url = config('bakong.api_url', 'https://api-bakong.nbc.gov.kh') . '/v1/generate_deeplink_by_qr';

            $sourceInfo = new SourceInfo(
                appIconUrl: config('bakong.app_icon'),
                appName: config('bakong.app_name'),
                appDeepLinkCallback: config('bakong.app_callback')
            );

            $payload = [
                'qr' => $qr,
                'sourceInfo' => [
                    'appIconUrl' => $sourceInfo->appIconUrl,
                    'appName' => $sourceInfo->appName,
                    'appDeepLinkCallback' => $sourceInfo->appDeepLinkCallback
                ],
            ];
            Log::info("Requesting Bakong Deep Link", ['url' => $url]);

            // Request for Dev only, update when Production
            $response1 = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

            $data = $response1->json()['data'];

            $deepLink = $data['shortLink'] ?? null;

            $transaction = Transaction::create([
                'payload' => [
                    'qr' => $qr,
                    'md5' => $md5,
                    'amount' => $amount,
                    'deepLink' => $deepLink,
                ],
            ]);

            DB::commit();

            // Dispatch queued job to check transaction asynchronously
            // CheckTransactionStatusJob::dispatch($md5);

            Log::info("KHQR Generated Successfully", [
                'md5' => $md5,
                'deepLink' => $deepLink
            ]);

            return response()->json([
                'success' => true,
                "data" => [
                    "transaction" => $transaction,
                    "qr_code" => $qr,
                    "md5" => $md5,
                    "deepLink" => $deepLink,
                ]
            ]);

        }catch(\Exception $e){
            DB::rollBack();
            Log::error("KHQR generation failed: " . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function checkAndUpdate(string $md5): void
    {
        $bakongApiKey = config('bakong.api_key');
        $url = config('bakong.api_url', 'https://api-bakong.nbc.gov.kh')
            . '/v1/check_transaction_by_md5';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $bakongApiKey,
                ])
                ->post($url, [
                    'md5' => $md5,
                ]);

            $result = $response->json();

            Log::debug("Bakong single MD5 response ({$response->status()}): " . json_encode($result));

            // API-level failure
            if (($result['responseCode'] ?? null) !== 0) {
                Log::warning('Bakong API returned non-zero responseCode', $result);
                return;
            }

            $trxData = $result['data'] ?? null;
            if (!$trxData) {
                Log::warning("Bakong transaction not found for md5: {$md5}");
                return;
            }

            $payment = Payment::where('md5', $md5)->first();
            if (!$payment) {
                Log::warning("Payment not found for md5: {$md5}");
                return;
            }

            Log::info("✅ Bakong transaction {$md5} success");

            // Normalize payload
            $payload = $result['data'];

            // Update transaction
            if ($payment->transaction_id) {
                Transaction::where('id', $payment->transaction_id)
                    ->update(['payload' => $payload]);
            }

            // Update payment status
            $payment->update([
                'status' => 'completed',
            ]);

                try{
                    $tenant = $payment->tenant;
                    $chatId = $tenant->telegram_id;
                    if ($tenant && $chatId) {

                        $bot = TelegramBot::where('user_id', $payment->landlord_id)->first();
                        $telegramService = new TelegramBotService();

                        $roomNumber = $payment->room->room_number ?? 'N/A';
                        $amount = $trx['amount'] ?? null;
                        $message = "✅ Payment Received\n"
                                . "━━━━━━━━━━━━━━━━━━━━\n"
                                . "Room: {$roomNumber}\n"
                                . ($amount ? "Amount: {$amount}\n" : "")
                                . "━━━━━━━━━━━━━━━━━━━━\n"
                                . "Your payment has been confirmed. Thank you!!";

                        if ($bot) {
                            $telegramService->sendMessage($bot, $chatId, $message);
                            Log::info("Sent payment success notification to tenant (chat_id: {$chatId})");
                        } else {
                            Log::warning("No Telegram bot found for landlord_id: {$payment->landlord_id}");
                        }
                    } else {
                        Log::warning("Tenant or chat ID not found for payment ID: {$payment->id}");

                    }

                }catch(\Exception $e){
                    Log::error("Failed to update payment after Bakong transaction success: " . $e->getMessage());
                }

        } catch (\Throwable $e) {
            Log::error('Bakong check transaction error: ' . $e->getMessage());
        }
    }
}