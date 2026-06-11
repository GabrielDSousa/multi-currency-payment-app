<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuários específicos para cada país
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
            ]);
        }
    }
}
