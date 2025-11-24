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

    protected ?int $buildingId;
    protected ?string $month;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $buildingId, ?string $month)
    {
        $this->buildingId = $buildingId;
        $this->month = $month;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        try{
            $csvData = $reportService->prepareCsvData($this->buildingId, $this->month);

            $filePath = $reportService->exportToCsv($csvData, $this->buildingId, $this->month);

            Log::info("Report CSV exported successfully", [
                'building_id' => $this->buildingId,
                'month' => $this->month,
                'path' => $filePath
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to export report CSV", [
                'building_id' => $this->buildingId,
                'month' => $this->month,
                'error' => $e->getMessage()
            ]);
        }
    }
}

