<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar pagamentos para cada usuário
        $users = \App\Models\User::all();

        foreach ($users as $user) {
            \App\Models\Payment::factory()->create([
                'user_id' => $user->id,
                'currency_code' => $user->currency_code,
            ]);
        }
    }
}
