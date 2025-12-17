<?php

namespace Database\Factories;

use App\Models\BakongAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BakongAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BakongAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landlord_id' => User::factory(),
            'bakong_id' => $this->faker->uuid(),
            'bakong_name' => $this->faker->name(),
            'bakong_location' => $this->faker->address(),
        ];
    }
}
