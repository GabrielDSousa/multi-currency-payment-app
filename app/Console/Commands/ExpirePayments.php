<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpirePayments extends Command
{
    protected $signature = 'payment:expire';

    protected $description = 'Expire payment requests that have been pending for more than 48 hours.';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subHours(48);

        $count = Payment::query()
            ->where('pending', true)
            ->whereNull('approved_at')
            ->whereNull('expired_at')
            ->where('created_at', '<=', $cutoff)
            ->update([
                'pending' => false,
                'expired_at' => Carbon::now(),
            ]);

        $this->info("Expired {$count} payment request(s).");

        return self::SUCCESS;
    }
}
