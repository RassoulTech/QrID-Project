<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    // La table ne porte que created_at (pas d'updated_at).
    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'token_hash',
        'expires_at',
        'ip_hash',
        'resend_count',
        'last_sent_at',
    ];

    protected $hidden = [
        'password',
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
