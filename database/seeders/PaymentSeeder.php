<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

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
