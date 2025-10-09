<?php

namespace App\Services;

use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\SourceInfo;
use KHQR\Models\IndividualInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


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

    public function generateKHQR(float $amount): array
    {
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
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

            return [
                'qr' => $qr,
                'md5' => $md5,
                'deep_link' => $response->json()['data']['shortLink'] ?? null
            ];

        }catch(\Exception $e){
            Log::error("KHQR generation failed: " . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

    }

    public function checkTransactionStatus(string $md5, int $timeout = 60): array
    {
        $elapsed = 0;
        $bakongApiKey = config('bakong.api_key');
        $url = config('bakong.api_url', 'https://api-bakong.nbc.gov.kh') . '/v1/check_transaction_by_md5';

        while ($elapsed < $timeout) {
            Log::info("Polling transaction status for {$md5} (elapsed: {$elapsed}s)...");

            try {
                // Request for Dev only, update when Production
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $bakongApiKey,
                    ])
                    ->post($url, ['md5' => $md5]);

                $data = $response->json();

                Log::debug("Bakong response ({$response->status()}): " . json_encode($data));

                if (($data['status'] ?? null) === 'success') {
                    Log::info("✅ Transaction {$md5} completed successfully.");
                    return $data;
                }

            } catch (\Throwable $e) {
                Log::error("Bakong polling error for {$md5}: {$e->getMessage()}");
            }

            sleep(1);
            $elapsed++;
        }

        Log::warning("⚠️ Transaction {$md5} timed out after {$timeout} seconds.");
        return ['status' => 'timeout'];
    }

}