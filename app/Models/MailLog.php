<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    protected $fillable = [
        'recipient',
        'subject',
        'mailable',
        'mailer',
        'status',
        'error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
