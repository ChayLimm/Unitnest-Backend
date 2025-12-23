<?php
namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Service;
use App\Models\Consumption;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get monthly report summary for a specific landlord's buildings.
     */
    public function getMonthlyReport(int $landlordId, ?int $buildingId = null, ?string $month = null): array
    { 
       return [
            'landlord_id'    => $landlordId,
            'building_id'    => $buildingId,
            'total_income'   => $this->getTotalIncome($landlordId, $buildingId, $month),
            'breakdown'      => $this->getBreakdown($landlordId, $buildingId, $month),
            'service_details'=> $this->getServiceDetails($landlordId, $buildingId, $month),
            'unpaid'         => $this->getUnpaidTotals($landlordId, $buildingId, $month),
        ];
    }

    /**
     * Prepare report data for CSV export.
     */
    public function prepareCsvData(int $landlordId, ?int $buildingId = null, ?string $month = null): array
    {
        $report = $this->getMonthlyReport($landlordId, $buildingId, $month);

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
    public function exportToCsv(array $data, int $landlordId, ?int $buildingId = null, ?string $month = null): string
    {
        $filename = 'reports/report_' . $landlordId . '_' . ($buildingId ?? 'all') . '_' . ($month ?? now()->format('Y_m_d_His')) . '.csv';
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
    
    private function getTotalIncome(int $landlordId, ?int $buildingId = null, ?string $month = null): array
    {
        try {
            // Optimized: Sum room price directly in DB using JOINs
            $paymentIncome = Payment::join('rooms', 'payments.room_id', '=', 'rooms.id')
                ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
                ->where('payments.status', 'completed')
                ->where('buildings.landlord_id', $landlordId)
                ->when($buildingId, fn($q) => $q->where('rooms.building_id', $buildingId))
                ->when($month, fn($q) => $q->whereMonth('payments.created_at', $month))
                ->sum('rooms.price');
            
            // Optimized: Sum subtotal directly in DB
            $serviceIncome = $this->getServiceIncomeQuery($landlordId, $buildingId, $month)
                ->sum('subtotal');

            $totalIncome = $paymentIncome;

            return [
                "payment_income" => $paymentIncome,
                "service_income" => $serviceIncome,
                "total_income" => $totalIncome,
            ];
        } catch (QueryException $e) {
            Log::error("Database query error in getTotalIncome: " . $e->getMessage());
            throw new Exception("Failed to retrieve total income due to a database error.");
        } catch (Exception $e) {
            Log::error("Unexpected error in getTotalIncome(): " . $e->getMessage());
            throw new \RuntimeException('An unexpected error occurred while calculating income.');
        }
    }

    private function getBreakdown(int $landlordId, ?int $buildingId = null, ?string $month = null): array
    {
        // Reuse optimized payment income query logic
        $roomTotal = Payment::join('rooms', 'payments.room_id', '=', 'rooms.id')
            ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
            ->where('payments.status', 'completed')
            ->where('buildings.landlord_id', $landlordId)
            ->when($buildingId, fn($q) => $q->where('rooms.building_id', $buildingId))
            ->when($month, fn($q) => $q->whereMonth('payments.created_at', $month))
            ->sum('rooms.price');

        // Optimized: Sum consumption directly
        $waterTotal = $this->getConsumptionQuery('Water', $landlordId, $buildingId, $month)
            ->sum('consumption');

        // Optimized: Sum consumption directly
        $electricityTotal = $this->getConsumptionQuery('Electricity', $landlordId, $buildingId, $month)
            ->sum('consumption');

        // Optimized: Sum service total directly
        $serviceTotal = $this->getServiceIncomeQuery($landlordId, $buildingId, $month)
            ->sum('subtotal');

        return [
            'room_total' => $roomTotal,
            'service_total' => $serviceTotal,
            'consumption' => [
                'water_total_m3' => $waterTotal,
                'electricity_total_kwh' => $electricityTotal,
            ],
            'total_income' => $roomTotal,
        ];
    }

    private function getServiceDetails(int $landlordId, ?int $buildingId = null, ?string $month = null)
    {
        // Major Optimization: Group by service_id and aggregate in DB
        // Avoids loading thousands of PaymentItem models into memory
        return PaymentItem::selectRaw('service_id, SUM(quantity) as total_quantity, SUM(subtotal) as total_amount')
            ->with(['service:id,name,unit_price']) // Eager load minimal service fields
            ->whereHas('payment', fn($p) => 
                $p->where('status', 'completed')
                ->whereHas('room.building', function ($q) use ($landlordId) {
                    $q->where('landlord_id', $landlordId);
                })
                ->when($buildingId, fn($q) => $q->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
                ->when($month, fn($q) => $q->whereMonth('created_at', $month))
            )
            ->groupBy('service_id')
            ->get()
            ->map(function ($item) {
                return (object)[
                    'name' => $item->service->name ?? 'Unknown Service',
                    'quantity' => $item->total_quantity,
                    'unit_price' => $item->service->unit_price ?? 0,
                    'total_amount' => $item->total_amount,
                ];
            });
    }

    private function getUnpaidTotals(int $landlordId, ?int $buildingId = null, ?string $month = null): array
    {
        // Optimized: Join for unpaid room sum
        $unpaidRooms = Payment::join('rooms', 'payments.room_id', '=', 'rooms.id')
            ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
            ->where('payments.status', '!=', 'completed')
            ->where('buildings.landlord_id', $landlordId)
            ->when($buildingId, fn($q) => $q->where('rooms.building_id', $buildingId))
            ->when($month, fn($q) => $q->whereMonth('payments.created_at', $month))
            ->sum('rooms.price');
        
        // Optimized: Sum unpaid services directly
        $unpaidServices = PaymentItem::whereHas('payment', function ($q) use ($landlordId, $buildingId, $month) {
            $q->where('status', '!=', 'completed')
              ->whereHas('room.building', function ($q) use ($landlordId) {
                  $q->where('landlord_id', $landlordId);
              })
              ->when($buildingId, fn($b) => $b->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
              ->when($month, fn($m) => $m->whereMonth('created_at', $month));
        })
        ->sum('subtotal');

        return [
            'unpaid_rooms' => $unpaidRooms,
            'unpaid_services' => $unpaidServices,
        ];
    }

    // --- Helper Methods ---

    /**
     * Get base query for service income (Payment items).
     */
    private function getServiceIncomeQuery(int $landlordId, ?int $buildingId, ?string $month): Builder
    {
        return PaymentItem::whereHas('payment', fn($p) => 
                $p->where('status', 'completed')
                ->whereHas('room.building', function ($q) use ($landlordId) {
                    $q->where('landlord_id', $landlordId);
                })
                ->when($buildingId, fn($q) => $q->whereHas('room', fn($r) => $r->where('building_id', $buildingId)))
                ->when($month, fn($q) => $q->whereMonth('created_at', $month))
            );
    }

    /**
     * Get consumption query for a specific service (Water/Electricity).
     */
    private function getConsumptionQuery(string $serviceName, int $landlordId, ?int $buildingId, ?string $month): Builder
    {
        // Join optimization can also be applied here if needed, but existing relation query is OK for now
        // since we are just summing 'consumption' column.
        return Consumption::whereHas('service', fn($s) => $s->where('name', $serviceName))
            ->whereHas('room.building', function ($q) use ($landlordId) {
                $q->where('landlord_id', $landlordId);
            })
            ->whereHas('room.payments', fn($p) => 
                $p->where('status', 'completed')
                ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
                ->when($month, fn($q2) => $q2->whereMonth('created_at', $month))
            );
    }
}