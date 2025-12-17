<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\User;
use App\Models\Role;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we attach buildings to a landlord
        $landlordRole = Role::where('role_name', 'Landlord')->first();
        if (!$landlordRole) {
            $landlordRole = Role::factory()->create(['role_name' => 'Landlord']);
        }

        $landlord = User::where('role_id', $landlordRole->id)->first() ?? User::factory()->create(['role_id' => $landlordRole->id]);

        Building::factory()->count(3)->create(['landlord_id' => $landlord->id]);
    }
}
