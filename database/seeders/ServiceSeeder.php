<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
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

        $services = [
            ['name' => 'Electricity', 'unit_price' => 0.25],
            ['name' => 'Water', 'unit_price' => 0.50],
            ['name' => 'Internet', 'unit_price' => 15.00],
            ['name' => 'Garbage Collection', 'unit_price' => 2.00],
        ];

        foreach ($services as $service) {
            Service::factory()->create(array_merge($service, ['landlord_id' => $landlord->id]));
        }
    }
}
