<?php

namespace App\Jobs;

use App\Services\ReportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportReportToCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $landlordId;
    protected ?int $buildingId;
    protected ?string $month;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $landlordId, ?int $buildingId, ?string $month)
    {
        $this->landlordId = $landlordId;
        $this->buildingId = $buildingId;
        $this->month = $month;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        try{
            $csvData = $reportService->prepareCsvData($this->landlordId, $this->buildingId, $this->month);

            $filePath = $reportService->exportToCsv($csvData, $this->landlordId, $this->buildingId, $this->month);

            Log::info("Report CSV exported successfully", [
                'landlord_id' => $this->landlordId,
                'building_id' => $this->buildingId,
                'month' => $this->month,
                'path' => $filePath
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to export report CSV", [
                'landlord_id' => $this->landlordId,
                'building_id' => $this->buildingId,
                'month' => $this->month,
                'error' => $e->getMessage()
            ]);
        }
    }
}

