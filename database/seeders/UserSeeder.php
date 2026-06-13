<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users for each country with specific currency codes
        $countries = [
            ['name' => 'Portugal', 'currency_code' => 'EUR'],
            ['name' => 'EUA', 'currency_code' => 'USD'],
            ['name' => 'UK', 'currency_code' => 'GBP'],
            ['name' => 'Japão', 'currency_code' => 'JPY'],
        ];

        foreach ($countries as $country) {
            \App\Models\User::factory()->create([
                'country' => $country['name'],
                'currency_code' => $country['currency_code'],
                'department' => 'employee',
            ]);
        }

        // Create additional random users
        User::factory()->count(9)->create();

        // Create a specific user for testing finance department access
        User::factory()->create([
            'name' => 'Finance User',
            'email' => 'finance_user@email.com',
            'password' => bcrypt('SenhaSegura123!'),
            'country' => 'EUA',
            'currency_code' => 'USD',
            'department' => 'finance',
        ]);

        // Create a specific user for testing employee department access
        User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee_user@email.com',
            'password' => bcrypt('SenhaSegura123!'),
            'country' => 'EUA',
            'currency_code' => 'USD',
            'department' => 'employee',
        ]);

        // Create a specific user for testing authentication
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'country' => 'Brasil',
            'currency_code' => 'BRL'
        ]);
    }
}
