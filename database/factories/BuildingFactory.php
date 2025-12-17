<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuildingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Building::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landlord_id' => User::factory(),
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'image_url' => $this->faker->imageUrl(),
            'floor' => $this->faker->numberBetween(1, 10),
            'unit' => $this->faker->numberBetween(1, 20),
        ];
    }
}
