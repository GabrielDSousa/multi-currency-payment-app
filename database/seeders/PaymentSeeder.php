<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Payment;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {

        $users = User::all();

        foreach ($users as $user) {
            Payment::factory()->create([
                'user_id' => $user->id,
                'currency_code' => $user->currency_code,
            ]);
        }
    }
}
