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

    /**
     * UN PARTAGE INITIÉ — et rien de plus.
     *
     * Quelqu'un a appuyé sur le bouton, WhatsApp ou la feuille système s'est
     * ouvert. Ce qui se passe ensuite échappe entièrement à l'application :
     * elle ne sait pas, et ne peut pas savoir, si un message est parti.
     *
     * Le jour où l'on affichera ce chiffre, il devra être nommé « partages
     * lancés » et non « messages envoyés ». Un compteur inventé sur un
     * tableau de bord fait douter de tous les autres.
     */
    public const TYPE_SHARE = 'share';

    /**
     * Les moyens de partage, tels que la base les accepte.
     *
     * `natif` est la feuille du système : elle propose WhatsApp parmi tout
     * le reste, et l'on ne saura jamais ce qui a été choisi ensuite. La
     * distinguer de `whatsapp` est une honnêteté — l'un dit « elle a choisi
     * WhatsApp », l'autre « elle a ouvert le choix ».
     */
    public const CANAUX = ['whatsapp', 'natif', 'copie', 'qr'];

    protected $fillable = [
        'profile_id',
        'type',
        'canal',
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
