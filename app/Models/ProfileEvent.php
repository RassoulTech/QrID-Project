<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Événement de consultation d'un profil. Table à forte volumétrie :
 * pas d'updated_at, aucune donnée personnelle en clair (ip_hash uniquement).
 */
class ProfileEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_VIEW = 'view';

    public const TYPE_SCAN = 'scan';

    public const TYPE_SAVE = 'save';

    protected $fillable = [
        'profile_id',
        'type',
        'ip_hash',
        'user_agent',
        'referer',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSince(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /** Hachage d'IP : jamais d'adresse en clair (RGPD, et bon sens). */
    public static function hashIp(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip.config('app.key')) : null;
    }
}
