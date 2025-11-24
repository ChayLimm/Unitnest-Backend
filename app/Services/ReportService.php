<?php
namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Service;
use App\Models\Consumption;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;

class ReportService
{
    /**
     * Get monthly report summary for all or a specific building.
     */
    public function getMonthlyReport(?int $buildingId = null, ?string $month = null){ 
       return [
            'building_id'    => $buildingId,
            'total_income'   => $this->getTotalIncome($buildingId, $month),
            'breakdown'      => $this->getBreakdown($buildingId, $month),
            'service_details'=> $this->getServiceDetails($buildingId, $month),
            'unpaid'         => $this->getUnpaidTotals($buildingId, $month),
        ];
    }

    /**
     * Prepare report data for CSV export.
     */
    public function prepareCsvData(?int $buildingId = null, ?string $month = null): array{
        $report = $this->getMonthlyReport($buildingId, $month);

        $rows = [];

        $rows[] = ['Category', 'Value'];
        $rows[] = ['Total Income', $report['breakdown']['total_income']];
        $rows[] = ['Room Total', $report['breakdown']['room_total']];
        $rows[] = ['Service Total', $report['breakdown']['service_total']];
        $rows[] = ['Water Consumption (m³)', $report['breakdown']['consumption']['water_total_m3']];
        $rows[] = ['Electricity Consumption (kWh)', $report['breakdown']['consumption']['electricity_total_kwh']];
        $rows[] = [];
        $rows[] = ['Unpaid Rooms', $report['unpaid']['unpaid_rooms']];
        $rows[] = ['Unpaid Services', $report['unpaid']['unpaid_services']];
        $rows[] = [];
        $rows[] = ['Service Details', ''];
        $rows[] = ['Name', 'Quantity', 'Unit Price', 'Total Amount'];

        foreach ($report['service_details'] as $service) {
            $rows[] = [
                $service->name,
                $service->quantity,
                $service->unit_price,
                $service->total_amount,
            ];
        }

        return $rows;
    }

    /**
     * Save CSV to storage (called by queued job).
     */
    public function exportToCsv(array $data, ?int $buildingId = null, ?string $month = null): string{
        $filename = 'reports/report_' . ($buildingId ?? 'all') . '_' . ($month ?? now()->format('Ymd_His')) . '.csv';
        $path = storage_path("app/{$filename}");
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $handle = fopen($path, 'w+');
        if (!empty($data)) {
            fputcsv($handle, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
        } else {
            fputcsv($handle, ['No data found']);
        }
        fclose($handle);

        return $path;
    }
    

    private function getTotalIncome(?int $buildingId = null, ?string $month = null): array
    {
        try{
            $PaymentIncome = Payment::with('room')
                ->where('status', 'completed')
                ->when($buildingId, fn($q) => $q->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
                ->when($month, fn($q) => $q->whereMonth('created_at', $month))
                ->get()
                ->sum(fn($payment) => $payment->room->price);
            
            $serviceIncome = PaymentItem::with('payment.room')
                ->whereHas('payment', fn($p) => 
                    $p->where('status', 'completed')
                    ->when($buildingId, fn($q) => $q->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
                    ->when($month, fn($q) => $q->whereMonth('created_at', $month))
                )
                ->get()
                ->sum('subtotal');

            $totalIncome = $PaymentIncome + $serviceIncome;

            return [
                "payment_income" => $PaymentIncome,
                "service_income" => $serviceIncome,
                "total_income" => $totalIncome,
            ];
        } catch (QueryException $e) {
            Log::error("Database query error in getTotalIncome: " . $e->getMessage());
            throw new Exception("Failed to retrieve total income due to a database error.");
        } catch (Exception $e) {
            // unexpected logic or runtime error
            Log::error("Unexpected error in getTotalIncome(): " . $e->getMessage());
            throw new \RuntimeException('An unexpected error occurred while calculating income.');
        }
    }

    private function getBreakdown(?int $buildingId, ?string $month = null){
        
            // Room total income
            $roomTotal = Payment::with('room')
                ->where('status', 'completed')
                ->when($buildingId, fn($q) => $q->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
                ->when($month, fn($q) => $q->whereMonth('created_at', $month))
                ->get()
                ->sum(fn($payment) => $payment->room->price);

            // Total water usage (m³)
            $waterTotal = Consumption::query()
                ->whereHas('service', fn($s) => $s->where('name', 'Water'))
                ->whereHas('room.payments', fn($p) => 
                    $p->where('status', 'completed')
                    ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
                    ->when($month, fn($q2) => $q2->whereMonth('created_at', $month))
                )
                ->sum('consumption');


            $electricityTotal = Consumption::query()
                ->whereHas('service', fn($s) => $s->where('name', 'Electricity'))
                ->whereHas('room.payments', fn($p) => 
                    $p->where('status', 'completed')
                    ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
                    ->when($month, fn($q2) => $q2->whereMonth('created_at', $month))
                )
                ->sum('consumption');

            // Total service charges
            $serviceTotal = PaymentItem::with('payment.room')
                ->whereHas('payment', fn($p) => 
                    $p->where('status', 'completed')
                    ->when($buildingId, fn($q) => $q->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
                    ->when($month, fn($q) => $q->whereMonth('created_at', $month))
                )
                ->get()
                ->sum('subtotal');

            // Return a detailed breakdown
            return [
                'room_total' => $roomTotal,
                'service_total' => $serviceTotal,
                'consumption' => [
                    'water_total_m3' => $waterTotal,
                    'electricity_total_kwh' => $electricityTotal,
                ],
                'total_income' => $roomTotal + $serviceTotal,
            ];
        
    }

    private function getServiceDetails(?int $buildingId, ?string $month = null)
    {
        return Service::with(['paymentItems.payment.room'])
            ->get()
            ->map(function ($service) use ($buildingId, $month) {
                $filteredItems = $service->paymentItems->filter(fn($item) =>
                    $item->payment->status === 'completed' &&
                    (!$buildingId || $item->payment->room->building_id === $buildingId) &&
                    (!$month || $item->payment->created_at->format('m') === $month)
                );

                return (object)[
                    'name' => $service->name,
                    'quantity' => $filteredItems->sum('quantity') ?? 0,
                    'unit_price' => $service->unit_price,
                    'total_amount' => $filteredItems->sum('subtotal'),
                ];
            });
    }

    private function getUnpaidTotals(?int $buildingId, ?string $month = null)
    {
        $unpaidPayments = Payment::with(['room', 'paymentItems'])
            ->where('status', '!=', 'completed')
            ->when($buildingId, fn($q) => $q->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
            ->when($month, fn($q) => $q->whereMonth('created_at', $month))
            ->get();

        $unpaidRooms = $unpaidPayments->sum(fn($p) => $p->room->price);
        
        $unpaidServices = PaymentItem::whereHas('payment', function ($q) use ($buildingId, $month) {
            $q->where('status', '!=', 'completed')
              ->when($buildingId, fn($b) => $b->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
              ->when($month, fn($m) => $m->whereMonth('created_at', $month));
        })
        ->sum('subtotal');

        return [
            'unpaid_rooms' => $unpaidRooms,
            'unpaid_services' => $unpaidServices,
        ];
    }
}
