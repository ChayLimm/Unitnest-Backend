<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Jobs\ExportReportToCsvJob;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(Request $request)
    {
        $request->validate([
            'building_id' => 'nullable|integer|exists:buildings,id',
            'month' => 'nullable|date_format:Y-m-d'
        ]);
        $report = $this->reportService->getMonthlyReport($request->input('building_id'), $request->input('month'));

        return response()->json($report);
    }

    public function exportReportToCsv(Request $request)
    {
        $request->validate([
            'building_id' => 'nullable|integer|exists:buildings,id',
            'month' => 'nullable|date_format:Y-m'
        ]);

        // Dispatch job to export report to CSV
        ExportReportToCsvJob::dispatch(
            $request->input('building_id'),
            $request->input('month')
        );

        return response()->json(['message' => 'Report export initiated. You will be notified once it is ready.']);
    }
}

