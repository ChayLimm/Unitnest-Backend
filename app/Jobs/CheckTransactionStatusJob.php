<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckTransactionStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $md5;
    protected int $timeout;

    /**
     * Create a new job instance.
     */
    public function __construct(string $md5, int $timeout = 60)
    {
        $this->md5 = $md5;
        $this->timeout = $timeout; // seconds
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        $bakongApiKey = config('bakong.api_key');
        $url = config('bakong.api_url', 'https://api-bakong.nbc.gov.kh') . '/v1/check_transaction_by_md5';

        $timeout = $this->timeout;
        $elapsed = 0;

        while ($elapsed < $timeout) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $bakongApiKey,
                    ])
                    ->post($url, ['md5' => $this->md5]);

                $data = $response->json();

                Log::debug("Bakong response ({$response->status()}): " . json_encode($data));

                if (($data['responseCode'] ?? null) === 0) {
                    Log::info("✅ Transaction {$this->md5} completed successfully.");

                    $payment = Payment::where('md5', $this->md5)->first();
                    
                    if ($payment && $payment->transaction_id) {
                        // find transaction record
                        $transaction = Transaction::find($payment->transaction_id);

                        // update transaction status
                        if ($transaction) {
                            $transaction->update([
                                'payload' => $data['data'] ?? null,
                            ]);
                        }

                        // update payment status
                        $payment->update([
                            'status' => 'completed',
                        ]);
                    }

                    return; // Stop polling after success
                }

            } catch (\Throwable $e) {
                Log::error("Bakong polling error for {$this->md5}: {$e->getMessage()}");
            }

            sleep(1);
            $elapsed++;
        }

        Log::warning("⚠️ Transaction {$this->md5} timed out after {$timeout} seconds.");
    }
}
