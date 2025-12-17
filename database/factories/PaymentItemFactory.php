<?php

namespace Database\Factories;

use App\Models\PaymentItem;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'service_id' => Service::factory(),
            'unit_price' => $this->faker->randomFloat(2, 0.5, 100),
            'quantity' => $this->faker->numberBetween(1, 10),
            'subtotal' => $this->faker->randomFloat(2, 5, 500),
        ];
    }
}
