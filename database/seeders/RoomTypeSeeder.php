<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RoomType;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $landlordRole = \App\Models\Role::where('role_name', 'Landlord')->first();
        if (!$landlordRole) {
            $landlordRole = \App\Models\Role::factory()->create(['role_name' => 'Landlord']);
        }
        $landlord = \App\Models\User::where('role_id', $landlordRole->id)->first() ?? \App\Models\User::factory()->create(['role_id' => $landlordRole->id]);

        $types = ['Single', 'Double', 'Suite', 'Studio'];

        foreach ($types as $type) {
            RoomType::factory()->create(['room_type_name' => $type, 'landlord_id' => $landlord->id]);
        }
    }
}
