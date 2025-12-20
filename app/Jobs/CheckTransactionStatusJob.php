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
use App\Enums\PaymentStatus;

class CheckTransactionStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $md5;
    protected int $timeout;

    /**
     * Create a new job instance.
     */
    public function __construct(array $md5, int $timeout = 60)
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
        $url = config('bakong.api_url', 'https://api-bakong.nbc.gov.kh')
            . '/v1/check_transaction_by_md5_list';

        $timeout = $this->timeout;
        $elapsed = 0;

        while ($elapsed < $timeout) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $bakongApiKey,
                    ])
                    ->post($url, [
                        'md5' => $this->md5, // MUST be array
                    ]);

                $data = $response->json();

                Log::debug("Bakong response ({$response->status()}): " . json_encode($data));

                if (($data['responseCode'] ?? null) !== 0) {
                    sleep(1);
                    $elapsed++;
                    continue;
                }

                $transactions = $data['data'] ?? [];

                foreach ($transactions as $trx) {
                    // SUCCESS condition (adjust if Bakong uses different flag)
                    if (($trx['status'] ?? null) !== 'SUCCESS') {
                        continue;
                    }

                    $md5 = $trx['md5'] ?? null;

                    if (!$md5) {
                        continue;
                    }

                    Log::info("✅ Transaction {$md5} completed successfully.");

                    $payment = Payment::where('md5', $md5)->first();

                    if (!$payment) {
                        continue;
                    }

                    // Update transaction record
                    if ($payment->transaction_id) {
                        $transaction = Transaction::find($payment->transaction_id);

                        if ($transaction) {
                            $transaction->update([
                                'payload' => $trx,
                            ]);
                        }
                    }

                    // Update payment
                    $payment->update([
                        'status' => PaymentStatus::COMPLETED->value,
                    ]);
                }

                // If any transaction succeeded → stop polling
                return;

            } catch (\Throwable $e) {
                Log::error("Bakong polling error: {$e->getMessage()}");
            }

            sleep(1);
            $elapsed++;
        }

        Log::warning(
            "⚠️ Transaction(s) " . implode(',', $this->md5)
            . " timed out after {$timeout} seconds."
        );
    }

}
