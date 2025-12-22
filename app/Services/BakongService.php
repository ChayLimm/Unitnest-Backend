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

    public function checkAndUpdate(array $md5List): void
    {
        $bakongApiKey = config('bakong.api_key');
        $url = config('bakong.api_url', 'https://api-bakong.nbc.gov.kh')
            . '/v1/check_transaction_by_md5_list';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $bakongApiKey,
                ])
                ->post($url, [
                    'md5' => $md5List,
                ]);

            $data = $response->json();

            Log::debug("Bakong response ({$response->status()}): " . json_encode($data));

            // API-level failure
            if (($data['responseCode'] ?? null) !== 0) {
                Log::warning('Bakong API returned non-zero responseCode', $data);
                return;
            }

            foreach (($data['data'] ?? []) as $trx) {

                if (($trx['status'] ?? null) !== 'SUCCESS') {
                    continue;
                }

                $md5 = $trx['md5'] ?? null;
                if (!$md5) {
                    continue;
                }

                $payment = Payment::where('md5', $md5)->first();
                if (!$payment) {
                    continue;
                }

                Log::info("✅ Bakong transaction {$md5} success");

                // Update transaction payload
                if ($payment->transaction_id) {
                    Transaction::where('id', $payment->transaction_id)
                        ->update(['payload' => $trx]);
                }

                // Update payment status
                $payment->update([
                    'status' => PaymentStatus::COMPLETED->value,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Bakong check transaction error: ' . $e->getMessage());
        }
    }
}