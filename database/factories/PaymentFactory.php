<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => User::factory(),
            'landlord_id' => User::factory(),
            'transaction_id' => Transaction::factory(),
            'room_id' => Room::factory(),
            'status' => $this->faker->randomElement(['Pending', 'Paid', 'Failed']),
            'qr_code' => $this->faker->md5(),
            'md5' => $this->faker->md5(),
            'receipt_url' => null,
        ];
    }
}
