<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Building;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'room_type_id' => RoomType::factory(),
            'price' => $this->faker->randomFloat(2, 50, 500),
            'barcode' => $this->faker->ean13(),
            'room_number' => $this->faker->unique()->numberBetween(100, 999),
            'floor' => $this->faker->numberBetween(1, 10),
            'status' => $this->faker->randomElement(['Available', 'Occupied', 'Maintenance']),
        ];
    }
}
