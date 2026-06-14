<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRateLog extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'rate' => 'float',
        'fetched_at' => 'datetime',
    ];
}
