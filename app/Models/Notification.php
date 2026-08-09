<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification du client — un fait réel de son compte, jamais un rappel
 * publicitaire.
 *
 * Quatre types seulement, et chacun correspond à un événement qui s'est
 * produit en base. Une notification sans fait derrière elle n'aurait rien à
 * annoncer.
 */
class Notification extends Model
{
    public const TYPE_PAIEMENT = 'paiement_valide';

    public const TYPE_EXPIRATION = 'abonnement_expire_bientot';

    public const TYPE_PREMIERE_VUE = 'premiere_vue';

    public const TYPE_CONTACT = 'contact_enregistre';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'url',
        'read_at',
        'cle_unicite',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /** Icône du type, choisie une fois ici plutôt que dans chaque vue. */
    public function icon(): string
    {
        return match ($this->type) {
            self::TYPE_PAIEMENT => 'carte',
            self::TYPE_EXPIRATION => 'horloge',
            self::TYPE_PREMIERE_VUE => 'oeil',
            self::TYPE_CONTACT => 'contact',
            default => 'point',
        };
    }
}
