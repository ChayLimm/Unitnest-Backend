<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Contract;
use App\Models\Room;
use App\Models\User;
use App\Models\Role;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = Room::where('status', 'Available')->take(5)->get();
        
        $tenantRole = Role::where('role_name', 'Tenant')->first();
        if (!$tenantRole) {
            $tenantRole = Role::factory()->create(['role_name' => 'Tenant']);
        }

        foreach ($rooms as $room) {
            // Need a Landlord for the Tenant model (who owns the tenant record?)
            // Usually the landlord of the room/building owns the tenant record.
            // Let's get the landlord from the room's building.
            $landlordId = $room->building->landlord_id;

            $tenant = \App\Models\Tenant::factory()->create(['landlord_id' => $landlordId]);
            
            Contract::factory()->create([
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'status' => 'Active',
            ]);

            $room->update(['status' => 'Occupied']);
        }
    }
}
