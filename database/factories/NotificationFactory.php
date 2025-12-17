<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'notification_type' => $this->faker->word(),
            'email' => $this->faker->safeEmail(),
            'read' => $this->faker->boolean(),
        ];
    }
}
