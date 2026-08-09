<?php

namespace App\Models;

use App\Concerns\FormatsSenegalPhone;
use App\Mail\ResetPasswordMail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements MustVerifyEmail
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

    // -----------------------------------------------------------------------
    // Rôle
    // -----------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
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
     * Remplace la notification Laravel par défaut (anglaise, marquée « Laravel »)
     * par le Mailable du produit, mis en file comme tous les autres e-mails.
     * L'URL est construite à partir d'APP_URL via route(), jamais en dur.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $url = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        $ttl = config('auth.passwords.users.expire', 60);

        Mail::to($this->email)->queue(
            (new ResetPasswordMail($url, $ttl, $this->email))->onQueue('mail')
        );
    }
}
