<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Building;
use App\Models\RoomType;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buildings = Building::all();
        $roomTypes = RoomType::all();

        if ($buildings->isEmpty()) {
            $this->call(BuildingSeeder::class);
            $buildings = Building::all();
        }

        if ($roomTypes->isEmpty()) {
            $this->call(RoomTypeSeeder::class);
            $roomTypes = RoomType::all();
        }

        foreach ($buildings as $building) {
            Room::factory()->count(10)->create([
                'building_id' => $building->id,
                'room_type_id' => $roomTypes->random()->id,
            ]);
        }
    }
}
