<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'tenant_id' => \App\Models\Tenant::factory(),
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'deposit_amount' => $this->faker->randomFloat(2, 100, 1000),
            'status' => $this->faker->randomElement(['Active', 'Expired', 'Terminated']),
        ];
    }
}
