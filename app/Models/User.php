<?php

namespace App\Models;

use App\Concerns\FormatsSenegalPhone;
use App\Mail\ResetPasswordMail;
use App\Support\Courrier;
use App\Support\Langue;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use FormatsSenegalPhone, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'theme',
        'google_id',
        'google_avatar',
    ];

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'physical_card_granted_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    /** RÈGLE V1 : un utilisateur = UN SEUL profil. */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function adminActions(): HasMany
    {
        return $this->hasMany(AdminAction::class, 'admin_id');
    }

    /**
     * Notifications du produit.
     *
     * Nommée « alerts » et non « notifications » : le trait Notifiable du
     * framework définit déjà une relation de ce nom, vers SA table à lui.
     * Deux relations homonymes sur le même modèle, c'est un bug qui n'apparaît
     * qu'au premier appel, très loin d'ici.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // -----------------------------------------------------------------------
    // Préférences
    // -----------------------------------------------------------------------

    public function prefersDark(): bool
    {
        return $this->theme === 'dark';
    }

    /**
     * LA LANGUE DES E-MAILS ADRESSÉS À CETTE PERSONNE.
     *
     * ═══════════════════════════════════════════════════════════════════
     * CETTE MÉTHODE N'EST JAMAIS APPELÉE PAR NOTRE CODE, ET C'EST LE POINT
     * ═══════════════════════════════════════════════════════════════════
     * Elle vient du contrat HasLocalePreference. Dès qu'un modèle le porte,
     * `Mail::to($user)` lit lui-même cette valeur et rend le message dans
     * cette langue — sans qu'aucun appelant ait à y penser.
     *
     * C'est ce qui répond au vrai piège des e-mails : un envoi DÉCLENCHÉ par
     * l'administrateur — une prolongation d'abonnement, un déblocage de
     * compte — s'exécute dans la requête de l'administrateur, donc avec SA
     * langue posée par le middleware. Sans ce contrat, un client anglophone
     * recevrait un message en français parce que quelqu'un d'autre a cliqué.
     *
     * Le mécanisme vaut aussi EN FILE : Laravel range la locale dans la
     * charge utile du job. Le worker, qui n'a ni session ni cookie ni
     * requête, restitue donc la bonne langue.
     */
    public function preferredLocale(): string
    {
        return Langue::valide($this->locale) ? $this->locale : Langue::FRANCAIS;
    }

    // -----------------------------------------------------------------------
    // Rôle
    // -----------------------------------------------------------------------

    /**
     * La carte PVC lui a-t-elle deja ete offerte ?
     *
     * Le fait est POSE au premier paiement encaisse, jamais recalcule : le
     * deduire en comptant les paiements serait faux des le premier
     * remboursement, et l'erreur coute une carte imprimee et expediee.
     */
    /** Les commandes de cartes physiques de ce compte. */
    public function cardOrders(): HasMany
    {
        return $this->hasMany(CardOrder::class);
    }

    public function hasPhysicalCard(): bool
    {
        return $this->physical_card_granted_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Ce compte a-t-il un mot de passe ?
     *
     * Faux pour qui s'est inscrit par Google et n'en a jamais posé. La
     * distinction compte partout où l'on propose de CHANGER le mot de passe :
     * demander l'actuel à quelqu'un qui n'en a pas est une impasse.
     */
    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    /** Le compte est-il rattaché à Google ? */
    public function usesGoogle(): bool
    {
        return filled($this->google_id);
    }

    /** Un compte suspendu ne peut ni se connecter, ni poursuivre sa session. */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    // -----------------------------------------------------------------------
    // Abonnement
    // -----------------------------------------------------------------------

    /** Abonnement en cours de validité, ou null. */
    /** Mémoire de l'abonnement courant, le temps d'une requête HTTP. */
    private ?Subscription $abonnementCourant = null;

    private bool $abonnementLu = false;

    /**
     * Abonnement en cours de validité, ou null. RÉSULTAT MÉMORISÉ.
     *
     * Cette méthode est appelée de partout — le bandeau d'essai du gabarit, le
     * tableau de bord, isPubliclyVisible() sur chaque carte affichée. Sans
     * mémoire, une seule page du tableau de bord la déclenchait QUATRE fois,
     * et chaque appel rechargeait aussi la formule : huit requêtes pour une
     * information unique et immuable le temps d'une requête HTTP.
     *
     * Toute écriture sur les abonnements doit appeler forgetActiveSubscription()
     * — sinon la valeur mémorisée survivrait au changement qui l'invalide.
     */
    public function activeSubscription(): ?Subscription
    {
        if ($this->abonnementLu) {
            return $this->abonnementCourant;
        }

        /*
         | SI LA RELATION EST DÉJÀ CHARGÉE, ON NE REQUÊTE PAS.
         |
         | La mémoire par instance ne suffit pas sur une LISTE : elle évite les
         | appels répétés pour UNE personne, pas les quinze personnes d'une
         | page. La liste des clients chargeait `subscriptions.plan` d'avance
         | puis lançait quand même une requête par ligne — l'eager loading
         | payé et jeté, 23 requêtes là où 8 suffisent.
         |
         | Le filtre reproduit exactement scopeActive() : statut actif, et
         | échéance nulle ou future. Le tri décroissant sur `ends_at` retient
         | l'abonnement qui court le plus loin, comme la requête.
         */
        if ($this->relationLoaded('subscriptions')) {
            $this->abonnementCourant = $this->subscriptions
                ->filter(fn (Subscription $a) => $a->status === Subscription::STATUS_ACTIVE
                    && ($a->ends_at === null || $a->ends_at->isFuture()))
                ->sortByDesc('ends_at')
                ->first();

            $this->abonnementLu = true;

            return $this->abonnementCourant;
        }

        $this->abonnementCourant = $this->subscriptions()
            ->active()
            ->with('plan')
            ->latest('ends_at')
            ->first();

        $this->abonnementLu = true;

        return $this->abonnementCourant;
    }

    /** À appeler après toute création, prolongation ou annulation d'abonnement. */
    public function forgetActiveSubscription(): void
    {
        $this->abonnementCourant = null;
        $this->abonnementLu = false;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /** L'utilisateur est-il en période d'essai gratuit ? */
    public function isOnTrial(): bool
    {
        return $this->activeSubscription()?->isTrial() ?? false;
    }

    /**
     * ENVOI IMMÉDIAT, JAMAIS PAR LA FILE.
     *
     * Cette méthode appelait Mail::queue(). Le message partait alors dans la
     * table `jobs` et n'en ressortait que si un worker exécutait
     * `queue:work`. Or aucun worker ne tourne : le plan gratuit de Render
     * n'héberge qu'un service web.
     *
     * Conséquence exacte, constatée en production : la page répondait, le
     * jeton de réinitialisation était créé, le délai de sécurité de soixante
     * secondes s'armait — et AUCUN E-MAIL NE PARTAIT. Sans erreur, sans
     * trace. L'utilisateur recliquait, tombait sur « Merci de patienter »,
     * et concluait que l'application était cassée.
     *
     * `send()` supprime cette dépendance. Le message part pendant la requête,
     * quelle que soit la valeur de QUEUE_CONNECTION. C'est une seconde
     * d'attente de plus — et un lien qui arrive.
     *
     * LA RÉINITIALISATION DE MOT DE PASSE EST LE PIRE ENDROIT OÙ DIFFÉRER UN
     * ENVOI : quelqu'un qui ne peut plus se connecter attend devant sa boîte.
     * Le jour où un worker existera, les e-mails de confort — récapitulatifs,
     * rappels — pourront repasser en file. Celui-ci restera immédiat.
     *
     * L'échec est journalisé ET relancé : une panne SMTP doit se voir, pas
     * se taire derrière un « lien envoyé » qui serait faux.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $url = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        $ttl = config('auth.passwords.users.expire', 60);

        /*
         | LA TRACE ET LA RELANCE VIENNENT DÉSORMAIS DU MÊME ENDROIT.
         |
         | Ce bloc reproduisait à la main ce que Courrier::exiger() fait : un
         | envoi immédiat, une ligne dans mail_logs si le transport refuse, et
         | l'exception relancée pour qu'une panne se voie. Trois envois
         | portaient ce besoin, chacun l'ayant résolu à sa façon — et deux
         | d'entre eux avaient oublié la trace.
         */
        Courrier::exiger($this->email, new ResetPasswordMail($url, $ttl, $this->email));
    }
}
