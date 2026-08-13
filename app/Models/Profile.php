<?php

namespace App\Models;

use App\Concerns\FormatsSenegalPhone;
use App\Enums\VarianteCarte;
use App\Observers\ProfileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ProfileObserver::class)]
class Profile extends Model
{
    use FormatsSenegalPhone, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'slug',
        'first_name',
        'last_name',
        'job_title',
        'company',
        'bio',
        'phone',
        'whatsapp',
        'public_email',
        'website',
        'address',
        'photo_path',
        'template_id',
        'primary_color',
        'is_active',
        'slug_changed_at',
    ];

    /*
     | Les trois états d'un profil. `is_active` seul n'en distinguait que deux,
     | et confondait le brouillon jamais publié avec le profil coupé par
     | l'administration.
     */
    public const ETAT_PUBLIE = 'published';

    public const ETAT_BROUILLON = 'draft';

    public const ETAT_DESACTIVE = 'deactivated';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'slug_changed_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * La variante de carte du profil — verte ou blanche, jamais autre chose.
     *
     * Point de passage UNIQUE vers primary_color. Aucune vue, aucun service ne
     * doit lire la colonne directement : c'est ainsi qu'une teinte héritée de
     * l'ancien nuancier finirait par ressortir quelque part.
     *
     * La résolution est tolérante — une valeur inattendue retombe sur la
     * variante par défaut plutôt que de lever sur la page publique d'un client.
     */
    public function variante(): VarianteCarte
    {
        return VarianteCarte::depuis($this->primary_color);
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** Liens sociaux, toujours dans l'ordre d'affichage voulu. */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(SocialLink::class)->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProfileEvent::class);
    }

    // -----------------------------------------------------------------------
    // Accesseurs
    // -----------------------------------------------------------------------

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Lien WhatsApp cliquable : https://wa.me/221XXXXXXXXX */
    public function getWhatsappHrefAttribute(): ?string
    {
        return static::senegalWhatsappHref($this->whatsapp ?: $this->phone);
    }

    public function getTelHrefAttribute(): ?string
    {
        return static::senegalTelHref($this->phone);
    }

    // -----------------------------------------------------------------------
    // Portées
    // -----------------------------------------------------------------------

    /**
     * Profils publiés (drapeau seul — la visibilité réelle dépend de l'abonnement).
     *
     * LES COLONNES SONT QUALIFIÉES, et ce n'est pas de la coquetterie :
     * `templates` porte elle aussi une colonne `is_active`. Dès qu'une requête
     * joint les deux tables — la répartition des cartes par modèle, par
     * exemple — MySQL refuse la requête entière pour ambiguïté. Le préfixe
     * rend la portée composable avec n'importe quelle jointure.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('profiles.is_active', true);
    }

    /**
     * Profils réellement visibles du public : publiés ET dont le propriétaire
     * dispose d'un abonnement actif non expiré.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published()->whereHas('user.subscriptions', function (Builder $q) {
            $q->where('status', 'active')
                ->where(fn (Builder $sub) => $sub->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        });
    }

    /** Coupé par l'administration — distinct d'un brouillon jamais publié. */
    public function scopeDeactivated(Builder $query): Builder
    {
        return $query->whereNotNull('profiles.deactivated_at');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('profiles.is_active', false)
            ->whereNull('profiles.deactivated_at');
    }

    /** Filtre de la liste des profils. Un état inconnu ne filtre rien. */
    public function scopeInState(Builder $query, ?string $etat): Builder
    {
        return match ($etat) {
            self::ETAT_PUBLIE => $query->published(),
            self::ETAT_BROUILLON => $query->draft(),
            self::ETAT_DESACTIVE => $query->deactivated(),
            default => $query,
        };
    }

    // -----------------------------------------------------------------------
    // Règles métier
    // -----------------------------------------------------------------------

    /**
     * RÈGLE : un profil n'est visible publiquement que s'il est actif ET que
     * son propriétaire a un abonnement actif (essai gratuit compris).
     *
     * Un profil désactivé a `is_active` à false : la coupure administrative
     * passe donc déjà par ce test, sans condition supplémentaire.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->is_active && $this->user?->hasActiveSubscription();
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function etat(): string
    {
        return match (true) {
            $this->isDeactivated() => self::ETAT_DESACTIVE,
            $this->is_active => self::ETAT_PUBLIE,
            default => self::ETAT_BROUILLON,
        };
    }

    public function etatLibelle(): string
    {
        return match ($this->etat()) {
            self::ETAT_PUBLIE => 'Publié',
            self::ETAT_DESACTIVE => 'Désactivé',
            default => 'Brouillon',
        };
    }

    /** RÈGLE : le slug n'est modifiable qu'une seule fois. */
    public function canChangeSlug(): bool
    {
        return $this->slug_changed_at === null;
    }

    public function markSlugAsChanged(): void
    {
        $this->forceFill(['slug_changed_at' => now()])->save();
    }

    /** L'URL publique du profil est toujours résolue par le slug. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
