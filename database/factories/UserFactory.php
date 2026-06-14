<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'country' => fake()->country(),
            'currency_code' => fake()->currencyCode(),
            'department' => 'employee',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model's password are known.
     */
    public function testPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => bcrypt('SenhaSegura123!'),
        ]);
    }

    /**
     * Indicate that the department should be finance
     */
    public function finance(): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => 'finance',
        ]);
    }

    /**
     * Indicate that the department should be employee
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => 'employee',
        ]);
    }

    /**
     * Indicate that the email should be custom
     */
    public function email(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Indicate that the country should be custom
     */
    public function from(string $country): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => $country,
        ]);
    }

    /**
     * Indicate that the currency should be custom
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_code' => $currency,
        ]);
    }
}
