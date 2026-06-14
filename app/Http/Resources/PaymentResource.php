<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'description' => $this->description,

            'amount_local' => (float) $this->amount_local,
            'currency_code' => $this->currency_code,
            'amount_eur' => (float) $this->amount_eur,

            'exchange_rate' => (float) $this->exchange_rate,
            'rate_source' => $this->rate_source,
            'rate_timestamp' => $this->rate_timestamp,

            'status' => $this->resolveStatus(),

            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'expired_at' => $this->expired_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveStatus(): string
    {
        return match (true) {
            $this->expired_at !== null => 'expired',
            $this->approved_at !== null => 'approved',
            (bool) $this->pending => 'pending',
            default => 'rejected',
        };
    }
}
