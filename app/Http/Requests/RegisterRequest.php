<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $countryWhitelist = ['BR', 'US', 'GB'];
        $currency_codeWhitelist = ['BRL', 'USD', 'EUR'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', PasswordRule::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
            'country' => ['required', 'string', 'size:2', Rule::in($countryWhitelist)],
            'currency_code' => ['required', 'string', 'size:3', Rule::in($currency_codeWhitelist)],
            'departament' => ['sometimes', 'string', Rule::in(['finance'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country')) {
            $this->merge(['country' => strtoupper($this->input('country'))]);
        }
        if ($this->has('currency_code')) {
            $this->merge(['currency_code' => strtoupper($this->input('currency_code'))]);
        }
    }
}
