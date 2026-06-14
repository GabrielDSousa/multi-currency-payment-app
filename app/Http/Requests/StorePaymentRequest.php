<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_local' => ['required', 'numeric', 'gt:0', 'max:99999999999'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
