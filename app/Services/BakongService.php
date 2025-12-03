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
use App\Jobs\CheckTransactionStatusJob;

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

    public function generateKHQR(float $amount)
    {
        DB::beginTransaction();


        try{
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
}