<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get monthly report summary for all or a specific building.
     */
    public function getMonthlyReport(?int $buildingId){
        
        // Total income
        $totalIncome = $this->getTotalIncome($buildingId);

        // Breakdown (room, consumption, service)
        $breakdown = $this->getBreakdown($buildingId);

        // Details of each service
        $serviceDetails = $this->getServiceDetails($buildingId);

        // Unpaid totals
        $unpaid = $this->getUnpaidTotals($buildingId);

        return [
            'building_id' => $buildingId,
            'total_income' => $totalIncome,
            'breakdown' => $breakdown,
            'service_details' => $serviceDetails,
            'unpaid' => $unpaid,
        ];
    }

    private function getTotalIncome(?int $buildingId){
        $query = DB::table('payments as p')
            ->join('rooms as r', 'p.room_id', '=', 'r.id')
            ->when($buildingId, fn($q) => $q->where('r.building_id', $buildingId))
            ->where('p.status', 'completed')
            ->select(DB::raw('SUM(r.price) as total_income'))
            ->first();

        return $query->total_income ?? 0;
    }

    private function getBreakdown(?int $buildingId){
        $room = DB::table('payments as p')
            ->join('rooms as r', 'p.room_id', '=', 'r.id')
            ->when($buildingId, fn($q) => $q->where('r.building_id', $buildingId))
            ->where('p.status', 'completed')
            ->sum('r.price');

        $consumption = DB::table('consumptions as c')
            ->join('rooms as r', 'c.room_id', '=', 'r.id')
            ->when($buildingId, fn($q) => $q->where('r.building_id', $buildingId))
            ->sum(DB::raw('c.consumption'));

        $service = DB::table('payment_items as pi')
            ->join('payments as p', 'pi.payment_id', '=', 'p.id')
            ->join('rooms as r', 'p.room_id', '=', 'r.id')
            ->when($buildingId, fn($q) => $q->where('r.building_id', $buildingId))
            ->where('p.status', 'completed')
            ->sum('pi.subtotal');

        return [
            'room_total' => $room,
            'consumption_total' => $consumption,
            'service_total' => $service,
        ];
    }

    private function getServiceDetails(?int $buildingId)
    {
        return DB::table('payment_items as pi')
            ->join('services as s', 'pi.service_id', '=', 's.id')
            ->join('payments as p', 'pi.payment_id', '=', 'p.id')
            ->join('rooms as r', 'p.room_id', '=', 'r.id')
            ->when($buildingId, fn($q) => $q->where('r.building_id', $buildingId))
            ->where('p.status', 'completed')
            ->select('s.name', DB::raw('SUM(pi.subtotal) as total_amount'))
            ->groupBy('s.name')
            ->get();
    }

    private function getUnpaidTotals(?int $buildingId)
    {
        $unpaidRooms = DB::table('payments as p')
            ->join('rooms as r', 'p.room_id', '=', 'r.id')
            ->when($buildingId, fn($q) => $q->where('r.building_id', $buildingId))
            ->where('p.status', '!=', 'completed')
            ->sum('r.price');

        $unpaidServices = DB::table('payment_items as pi')
            ->join('payments as p', 'pi.payment_id', '=', 'p.id')
            ->join('rooms as r', 'p.room_id', '=', 'r.id')
            ->when($buildingId, fn($q) => $q->where('r.building_id', $buildingId))
            ->where('p.status', '!=', 'completed')
            ->sum('pi.subtotal');

        return [
            'unpaid_rooms' => $unpaidRooms,
            'unpaid_services' => $unpaidServices,
        ];
    }
}