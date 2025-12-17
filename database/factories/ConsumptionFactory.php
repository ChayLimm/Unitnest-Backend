<?php

namespace Database\Factories;

use App\Models\Consumption;
use App\Models\Room;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsumptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consumption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'service_id' => Service::factory(),
            'end_reading' => $this->faker->randomFloat(4, 100, 1000),
            'photo_url' => $this->faker->imageUrl(),
            'consumption' => $this->faker->randomFloat(4, 10, 100),
        ];
    }
}
