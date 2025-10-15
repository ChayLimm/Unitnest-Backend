<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function monthlyReport(Request $request)
    {
        $request->validate([
            'building_id' => 'nullable|integer|exists:buildings,id'
        ]);
        $report = $this->reportService->getMonthlyReport($request->input('building_id'));

        return response()->json($report);
    }
}
