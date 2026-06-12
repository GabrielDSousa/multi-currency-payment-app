<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    private function isFinance(User $user): bool
    {
        return $user->department === 'finance';
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($this->isFinance($user)) {
            return true;
        }

        return $payment->user_id === $user->id;
    }
}
