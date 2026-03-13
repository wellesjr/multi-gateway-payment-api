<?php

namespace Database\Factories;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gateway>
 */
class GatewayFactory extends Factory
{
    protected $model = Gateway::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['gateway1', 'gateway2']),
            'is_active' => true,
            'priority' => fake()->numberBetween(1, 10),
        ];
    }
}
