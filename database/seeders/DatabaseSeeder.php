<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            RoomTypeSeeder::class,
            ServiceSeeder::class,
            BuildingSeeder::class,
            RoomSeeder::class,
            ContractSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
