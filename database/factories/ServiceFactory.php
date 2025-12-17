<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landlord_id' => \App\Models\User::factory(),
            'name' => $this->faker->word(),
            'unit_price' => $this->faker->randomFloat(2, 0.5, 100),
            'description' => $this->faker->sentence(),
        ];
    }
}
