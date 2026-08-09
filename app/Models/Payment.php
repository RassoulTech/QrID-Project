<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Paiement — pièce comptable. Jamais supprimé physiquement (softDeletes),
 * et survit à la suppression du compte (user_id nullable, nullOnDelete).
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const METHOD_WAVE = 'wave';

    public const METHOD_ORANGE_MONEY = 'orange_money';

    public const METHOD_FREE_MONEY = 'free_money';

    public const METHODS = [
        self::METHOD_WAVE => 'Wave',
        self::METHOD_ORANGE_MONEY => 'Orange Money',
        self::METHOD_FREE_MONEY => 'Free Money',
    ];

    protected $fillable = [
        'user_id',
        'subscription_id',
        'provider',
        'provider_ref',
        'method',
        'amount_fcfa',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_fcfa' => 'integer',  // JAMAIS de float sur de l'argent
            'payload' => 'array',
        ];
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // -----------------------------------------------------------------------
    // Portées
    // -----------------------------------------------------------------------

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // -----------------------------------------------------------------------
    // Métier
    // -----------------------------------------------------------------------

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /** Montant formaté : « 25 000 FCFA ». */
    public function formattedAmount(): string
    {
        return number_format($this->amount_fcfa, 0, ',', ' ').' FCFA';
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
