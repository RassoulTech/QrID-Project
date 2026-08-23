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
use Illuminate\Support\Facades\Storage;

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
        'maps_url',
        'photo_path',
        'photo_data',
        'cover_path',
        'cover_data',
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
    /**
     * LA PHOTO EXISTE-T-ELLE VRAIMENT SUR LE DISQUE ?
     *
     * ═══════════════════════════════════════════════════════════════════
     * TESTER LA COLONNE NE SUFFIT PAS
     * ═══════════════════════════════════════════════════════════════════
     * La vue publique testait `photo_path`, c'est-à-dire la BASE. Elle rendait
     * donc une balise <img> cassée chaque fois que le fichier avait disparu du
     * disque — et le repli par initiales, écrit juste à côté, ne se
     * déclenchait jamais. C'est ainsi qu'on obtient un bandeau vide.
     *
     * Or le disque de Render est ÉPHÉMÈRE : les photos téléversées sont
     * effacées à chaque déploiement. Vérifié en production — dans le même
     * dossier /storage/, l'aperçu de partage répond 200 et la photo 404, parce
     * que l'application REGÉNÈRE l'un et pas l'autre.
     *
     * Ce test est donc la seule chose qui distingue « pas de photo » de
     * « photo perdue » — et pour le visiteur, les deux doivent produire le
     * même repli propre.
     */
    public function aUnePhoto(): bool
    {
        return $this->photoBinaire() !== null;
    }

    /**
     * LES OCTETS DE LA PHOTO — la base d'abord, le disque ensuite.
     *
     * ═══════════════════════════════════════════════════════════════════
     * LA BASE EST LA SOURCE, LE DISQUE EST UN CACHE
     * ═══════════════════════════════════════════════════════════════════
     * Le disque de Render est éphémère : chaque déploiement le remet à zéro.
     * La colonne photo_path survivait, le fichier non — et le visiteur voyait
     * les initiales d'un profil qui avait bel et bien une photo.
     *
     * On lit donc le disque en premier, parce que c'est le chemin rapide, mais
     * on retombe sur la base dès qu'il est vide. Et l'on REMET le fichier en
     * place au passage : le déploiement suivant repart d'un cache chaud sans
     * qu'aucune tâche n'ait à y penser.
     */
    public function photoBinaire(): ?string
    {
        return $this->mediaBinaire('photo_path', 'photo_data');
    }

    /** Y a-t-il une bannière de couverture choisie par le porteur ? */
    public function aUneCouverture(): bool
    {
        return $this->couvertureBinaire() !== null;
    }

    /**
     * LES OCTETS DE LA COUVERTURE — même mécanique que la photo.
     *
     * Facultative : sans elle, la page rend une bannière composée qui porte
     * le nom du produit. Voir x-couverture.
     */
    public function couvertureBinaire(): ?string
    {
        return $this->mediaBinaire('cover_path', 'cover_data');
    }

    /**
     * UNE SEULE MÉCANIQUE POUR LES DEUX IMAGES.
     *
     * Photo et couverture posent exactement le même problème et méritent
     * exactement la même réponse. Deux copies de ces vingt lignes auraient
     * divergé à la première correction — c'est toujours la seconde qu'on
     * oublie de mettre à jour.
     */
    private function mediaBinaire(string $colonneChemin, string $colonneOctets): ?string
    {
        $chemin = $this->{$colonneChemin};

        if (filled($chemin)) {
            try {
                if (Storage::disk('public')->exists($chemin)) {
                    return Storage::disk('public')->get($chemin);
                }
            } catch (\Throwable) {
                // Un disque injoignable ne doit pas casser la page.
            }
        }

        $octets = $this->{$colonneOctets};

        if (blank($octets)) {
            return null;
        }

        // Le cache se reconstitue tout seul, sans bloquer l'affichage si le
        // disque refuse l'écriture.
        if (filled($chemin)) {
            try {
                Storage::disk('public')->put($chemin, $octets);
            } catch (\Throwable) {
            }
        }

        return $octets;
    }

    /** Les initiales du porteur, pour le repli. */
    public function initiales(): string
    {
        $initiales = mb_strtoupper(
            mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1)
        );

        return $initiales !== '' ? $initiales : mb_strtoupper(mb_substr($this->full_name, 0, 1));
    }

    /**
     * OÙ OUVRIR LA POSITION DU PORTEUR.
     *
     * Le lien exact qu'il a collé, s'il en a un. Sinon une recherche
     * cartographique sur l'adresse saisie — qui tombe à peu près juste sur
     * « Sacré-Cœur 3, Dakar », et nulle part sur « en face de la pharmacie ».
     * D'où le champ dédié.
     */
    public function lienCarte(): ?string
    {
        if (filled($this->maps_url)) {
            return $this->maps_url;
        }

        return filled($this->address)
            ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->address)
            : null;
    }

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
