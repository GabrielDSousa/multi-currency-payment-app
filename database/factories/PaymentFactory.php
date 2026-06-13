<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountLocal = fake()->randomFloat(4, 10, 1000);
        $exchangeRate = fake()->randomFloat(6, 0.5, 1.5);

        return [
            'user_id' => User::factory(),
            'amount_local' => fake()->randomFloat(4, 10, 1000),
            'currency_code' => fake()->currencyCode(),
            'exchange_rate' => fake()->randomFloat(6, 0.5, 1.5),
            'amount_eur' => round($amountLocal * $exchangeRate, 4),
            'rate_source' => 'Sample Exchange Rate API',
            'rate_timestamp' => fake()->dateTimeBetween('-48 hours', 'now'),
            'pending' => fake()->boolean(80), // 80% chance of being pending
            'description' => fake()->sentence(),
            'approved_by' => null,
            'approved_at' => null,
            'expired_at' => null,
        ];
    }
}
