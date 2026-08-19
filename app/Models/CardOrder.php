<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UNE CARTE PHYSIQUE À PRODUIRE, PUIS À EXPÉDIER.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES ÉTATS SUIVENT LA RÉALITÉ DE L'ATELIER, PAS UNE ABSTRACTION
 * ═══════════════════════════════════════════════════════════════════════
 *   pending    la commande existe, on attend d'en avoir assez pour lancer
 *   in_batch   elle est partie chez l'imprimeur, dans un lot identifié
 *   produced   la carte est imprimée et revenue
 *   shipped    elle est chez le transporteur ou en main propre
 *   delivered  le client l'a
 *   cancelled  abandon — remboursement, adresse introuvable, doublon
 *
 * Chaque passage porte SA date. Sans elles, « où en est ma carte ? » n'aurait
 * qu'une réponse : le statut courant, sans le moindre repère de temps — et
 * c'est le délai, pas l'état, qui fait écrire un client.
 */
class CardOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_BATCH = 'in_batch';

    public const STATUS_PRODUCED = 'produced';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /** L'ordre est celui du parcours réel : il sert aussi à l'affichage. */
    public const STATUTS = [
        self::STATUS_PENDING => 'En attente de production',
        self::STATUS_IN_BATCH => 'Chez l’imprimeur',
        self::STATUS_PRODUCED => 'Imprimée',
        self::STATUS_SHIPPED => 'Expédiée',
        self::STATUS_DELIVERED => 'Livrée',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    protected $fillable = [
        'user_id',
        'profile_id',
        'status',
        'recipient_name',
        'phone',
        'address_line',
        'city',
        'region',
        'delivery_notes',
        'batch_id',
        'produced_at',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'produced_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function statutLibelle(): string
    {
        return self::STATUTS[$this->status] ?? $this->status;
    }

    /**
     * L'ADRESSE EST-ELLE ENCORE MODIFIABLE ?
     *
     * Tant que la commande attend, oui. Dès qu'elle part chez l'imprimeur, le
     * bordereau est figé : laisser corriger l'adresse après coup ferait croire
     * au client que le colis suivra, alors qu'il est déjà parti ailleurs.
     */
    public function adresseModifiable(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** L'adresse est-elle complète au point qu'on puisse imprimer ? */
    public function adresseComplete(): bool
    {
        return filled($this->recipient_name)
            && filled($this->phone)
            && filled($this->address_line)
            && filled($this->city);
    }

    /** Jours écoulés depuis la commande — la donnée qui déclenche les relances. */
    public function anciennete(): int
    {
        return (int) $this->created_at?->diffInDays(now());
    }

    public function scopeEnAttente($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** Les commandes qui bloquent réellement la production. */
    public function scopeAProduire($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_BATCH]);
    }
}
