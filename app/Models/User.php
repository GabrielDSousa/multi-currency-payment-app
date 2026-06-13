<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;
use Laravel\Passport\PersonalAccessTokenResult;

class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'country',
        'currency_code',
        'department',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function pendingPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'pending');
    }

    public function approvedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'approved_by');
    }

    public function avaibleTokens(): HasMany
    {
        return $this->hasMany(Token::class)->where('revoked', false);
    }

    public function revokeAllTokens(): void
    {
        $this->avaibleTokens()->each(function (Token $token) {
            $token->revoke();
        });
    }

    public function createTokenWithDepartmentScope(): PersonalAccessTokenResult
    {
        $token = $this->createToken("{$this->name} #{$this->id}", [$this->department]);

        return $token;
    }
}
