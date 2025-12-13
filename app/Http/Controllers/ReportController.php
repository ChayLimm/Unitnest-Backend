<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use App\Jobs\ExportReportToCsvJob;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(ReportRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        // If the user has a landlord_id (e.g. is a sub-account), use it; otherwise use their own ID.
        $landlordId = $user->id;

        $buildingId = $request->input('building_id');
        $month = $request->input('month');

        $report = $this->reportService->getMonthlyReport($landlordId, $buildingId, $month);
        
        // // For API testing: Get landlord_id from request input
        // $landlordId = $request->input('landlord_id');
        
        // $report = $this->reportService->getMonthlyReport(
        //     $landlordId, // MANDATORY: landlord_id must be provided
        //     $request->input('building_id'), 
        //     $request->input('month')
        // );

        return response()->json($report);
    }

    public function exportReportToCsv(ReportRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        // If the user has a landlord_id (e.g. is a sub-account), use it; otherwise use their own ID.
        $landlordId = $user->id;

        // Dispatch job with MANDATORY landlord_id
        ExportReportToCsvJob::dispatch(
            $landlordId,
            $request->input('building_id'),
            $request->input('month')
        );

        return response()->json(['message' => 'Report export initiated. You will be notified once it is ready.']);
    }
}