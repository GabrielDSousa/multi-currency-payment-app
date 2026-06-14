<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Portugal', 'currency_code' => 'EUR'],
            ['name' => 'EUA', 'currency_code' => 'USD'],
            ['name' => 'UK', 'currency_code' => 'GBP'],
            ['name' => 'Japão', 'currency_code' => 'JPY'],
        ];

        foreach ($countries as $country) {
            User::factory()->from($country['name'])->currency($country['currency_code'])->employee()->create();
        }

        User::factory()->count(9)->create();
        User::factory()->email('test_user@example.com')->testPassword()->from('Brasil')->currency('BRL')->create();
        User::factory()->email('finance_user@example.com')->testPassword()->finance()->create();
        User::factory()->email('employee_user@example.com')->testPassword()->employee()->create();
    }
}
