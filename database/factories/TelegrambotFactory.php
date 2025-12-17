<?php

namespace Database\Factories;

use App\Models\Telegrambot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TelegrambotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Telegrambot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bot_id' => $this->faker->unique()->uuid(),
            'image_url' => $this->faker->imageUrl(),
            'about' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'username' => $this->faker->unique()->userName(),
            'token' => $this->faker->unique()->sha256(),
        ];
    }
}
