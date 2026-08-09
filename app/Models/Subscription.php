<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // -----------------------------------------------------------------------
    // Portées
    // -----------------------------------------------------------------------

    /** Abonnements en cours de validité (statut actif et non échus). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** Abonnements arrivant à échéance dans N jours (relances J-7, J-3, J-1). */
    public function scopeExpiringInDays(Builder $query, int $days): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('ends_at', now()->addDays($days)->toDateString());
    }

    // -----------------------------------------------------------------------
    // Métier
    // -----------------------------------------------------------------------

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired();
    }

    /** Jours restants avant échéance. 0 si expiré, null si sans échéance. */
    public function daysRemaining(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        return $this->ends_at->isPast() ? 0 : (int) now()->diffInDays($this->ends_at);
    }

    /** L'abonnement correspond-il à la formule d'essai gratuit ? */
    public function isTrial(): bool
    {
        return $this->plan?->isFree() ?? false;
    }
}
