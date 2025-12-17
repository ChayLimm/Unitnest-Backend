<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landlord_id' => User::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'telegram_id' => $this->faker->numerify('##########'),
            'identify_id' => $this->faker->numerify('#########'),
            'profile_image_url' => $this->faker->imageUrl(),
            'identify_image_url' => $this->faker->imageUrl(),
            'emergency_contact' => json_encode(['name' => $this->faker->name(), 'phone' => $this->faker->phoneNumber()]),
        ];
    }
}
