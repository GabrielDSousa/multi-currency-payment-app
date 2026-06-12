<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount_local',
        'currency_code',
        'amount_eur',
        'exchange_rate',
        'rate_source',
        'rate_timestamp',
        'pending',
        'description',
        'approved_by',
        'approved_at',
        'expired_at',
    ];

    protected $casts = [
        'user_id'     => 'integer',
        'amount_local' => 'decimal:4',
        'amount_eur' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'pending' => 'boolean',
        'rate_timestamp' => 'datetime',
        'approved_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (Payment $payment) {
            if ($payment->isDirty('exchange_rate')) {
                throw new \RuntimeException('The exchange rate is immutable and cannot be modified after creation.');
            }

            if ($payment->isDirty('amount_eur')) {
                throw new \RuntimeException('The amount in EUR is immutable and cannot be modified after creation.');
            }

            if ($payment->isDirty('rate_source')) {
                throw new \RuntimeException('The rate source is immutable and cannot be modified after creation.');
            }

            if ($payment->isDirty('rate_timestamp')) {
                throw new \RuntimeException('The rate timestamp is immutable and cannot be modified after creation.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
