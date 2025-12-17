<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Jobs\ExportReportToCsvJob;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(Request $request, $landlordId)
    {   
        
        $buildingId = $request->input('building_id');
        $month = $request->input('month');

        $report = $this->reportService->getMonthlyReport($landlordId, $buildingId, $month);

        return response()->json($report);
    }

    public function exportReportToCsv(Request $request, $landlordId)
    {

        // Dispatch job with MANDATORY landlord_id
        ExportReportToCsvJob::dispatch(
            $landlordId,
            $request->input('building_id'),
            $request->input('month')
        );

        return response()->json(['message' => 'Report export initiated. You will be notified once it is ready.']);
    }
}