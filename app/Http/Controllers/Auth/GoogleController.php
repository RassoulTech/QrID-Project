<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as UtilisateurGoogle;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * CONNEXION PAR GOOGLE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI ELLE EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * C'est le chemin le plus court vers un compte : aucun mot de passe à
 * inventer, aucun e-mail de confirmation à attendre — Google a déjà vérifié
 * l'adresse. Elle supprime d'un coup les deux parcours les plus coûteux du
 * produit : la confirmation d'inscription et la réinitialisation de mot de
 * passe, qui dépendent l'une et l'autre d'une messagerie qui doit fonctionner.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA RÈGLE DE SÉCURITÉ QUI GOUVERNE TOUT CE FICHIER
 * ═══════════════════════════════════════════════════════════════════════
 * Retrouver un compte existant par son ADRESSE est une opération dangereuse :
 * si l'adresse rendue par Google n'était pas vérifiée, n'importe qui pourrait
 * créer un compte Google portant l'adresse d'autrui et prendre le contrôle du
 * compte correspondant chez nous.
 *
 * On exige donc `email_verified` dans la réponse de Google, et l'on refuse
 * sans exception dans le cas contraire. Google le renvoie systématiquement à
 * true pour les adresses @gmail.com et les domaines Workspace ; le cas
 * refusé est précisément celui qui doit l'être.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * IDENTIFIER PAR google_id, RETROUVER PAR L'ADRESSE
 * ═══════════════════════════════════════════════════════════════════════
 * Une adresse peut changer de propriétaire — un salarié part, son adresse est
 * réattribuée. L'identifiant Google, lui, est stable et ne se réattribue
 * jamais. L'adresse ne sert donc qu'UNE FOIS, pour rattacher un compte
 * existant ; ensuite, c'est l'identifiant qui fait foi.
 */
class GoogleController extends Controller
{
    /**
     * La connexion Google est-elle configurée ?
     *
     * Les écrans consultent cette méthode pour décider d'afficher le bouton.
     * Un bouton qui mène à une page d'erreur Google est PIRE que pas de
     * bouton : l'utilisateur en conclut que le service est cassé, non qu'il
     * est en cours de configuration.
     */
    public static function estDisponible(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /** Départ vers Google. */
    public function redirect(): RedirectResponse
    {
        if (! self::estDisponible()) {
            return redirect()->route('login')->withErrors([
                'email' => 'La connexion Google n\'est pas encore disponible. '
                    .'Utilisez votre adresse e-mail et votre mot de passe.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Retour de Google.
     *
     * AUCUNE EXCEPTION NE DOIT ATTEINDRE L'UTILISATEUR. Ce point d'entrée est
     * appelé par un tiers, avec des paramètres qu'on ne maîtrise pas : refus
     * de consentement, session perdue entre l'aller et le retour, panne
     * réseau chez Google. Chacun de ces cas est normal et se termine par un
     * message en français sur l'écran de connexion, jamais par une page 500.
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! self::estDisponible()) {
            return $this->echec('La connexion Google n\'est pas disponible.');
        }

        // L'utilisateur a refusé, ou fermé la fenêtre de consentement. Ce
        // n'est pas une erreur : c'est une décision, et elle se respecte en
        // silence.
        if ($request->filled('error')) {
            return redirect()->route('login');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Retour Google inexploitable', ['error' => $e->getMessage()]);

            return $this->echec(
                'La connexion avec Google n\'a pas abouti. Réessayez, '
                .'ou utilisez votre adresse e-mail et votre mot de passe.'
            );
        }

        $email = mb_strtolower(trim((string) $googleUser->getEmail()));

        if ($email === '') {
            return $this->echec('Google n\'a pas transmis votre adresse e-mail.');
        }

        /*
         | LE CONTRÔLE QUI PROTÈGE LES COMPTES EXISTANTS.
         |
         | Sans lui, un compte Google créé avec l'adresse d'autrui — et non
         | vérifiée — donnerait accès au compte QrID de cette personne. Google
         | renvoie ce drapeau pour toute adresse @gmail.com et tout domaine
         | Workspace ; le refuser ne ferme la porte qu'aux cas douteux.
         */
        if (! $this->adresseVerifieeParGoogle($googleUser)) {
            Log::warning('Adresse Google non vérifiée refusée', ['email' => $email]);

            return $this->echec(
                'Google n\'a pas confirmé que cette adresse vous appartient. '
                .'Créez un compte avec votre adresse e-mail et un mot de passe.'
            );
        }

        [$user, $nouveau] = $this->trouverOuCreer($googleUser, $email);

        if ($user === null) {
            return $this->echec(
                'La connexion a échoué. Réessayez dans un instant, '
                .'ou utilisez votre adresse e-mail et votre mot de passe.'
            );
        }

        // Un compte suspendu ne s'ouvre pas davantage par Google que par mot
        // de passe : la porte de service doit être fermée comme la principale.
        if ($user->isBlocked()) {
            return $this->echec(trans('auth.blocked'));
        }

        /*
         | L'ESSAI GRATUIT ET L'E-MAIL DE BIENVENUE, APRÈS LA TRANSACTION.
         |
         | Émis à l'intérieur, un envoi qui échoue annulerait la création du
         | compte : le client se retrouverait sans compte pour une panne de
         | messagerie. C'est exactement l'erreur déjà corrigée sur le paiement.
         |
         | Seul un compte NOUVEAU le déclenche. Rattacher Google à un compte
         | existant n'est pas une inscription : lui souhaiter la bienvenue et
         | lui rouvrir un essai serait faux deux fois.
         */
        if ($nouveau) {
            $this->accueillir($user);
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(
            $user->isAdmin() ? route('admin.overview') : route('dashboard')
        );
    }

    // -----------------------------------------------------------------------

    /**
     * Google affirme-t-il que cette adresse appartient bien à l'utilisateur ?
     *
     * Le drapeau vit dans la charge utile brute, sous deux noms selon la
     * version de l'API. On lit les deux, et l'absence vaut REFUS — sur un
     * contrôle de sécurité, le doute ne profite pas à l'appelant.
     */
    private function adresseVerifieeParGoogle(UtilisateurGoogle $googleUser): bool
    {
        $brut = $googleUser->getRaw();

        return ($brut['email_verified'] ?? $brut['verified_email'] ?? false) === true;
    }

    /**
     * Le compte correspondant, créé si besoin.
     *
     * TROIS CAS, DANS CET ORDRE :
     *
     *   1. l'identifiant Google est déjà connu — c'est un retour, on ouvre ;
     *   2. l'adresse correspond à un compte existant — on RATTACHE. C'est le
     *      cas de quelqu'un inscrit par mot de passe qui essaie Google ; lui
     *      refuser l'entrée serait incompréhensible ;
     *   3. personne — on crée le compte, déjà vérifié.
     *
     * @return array{0: ?User, 1: bool} le compte, et s'il vient d'être créé
     */
    private function trouverOuCreer(UtilisateurGoogle $googleUser, string $email): array
    {
        $googleId = (string) $googleUser->getId();

        try {
            return DB::transaction(function () use ($googleUser, $email, $googleId) {
                if ($user = User::where('google_id', $googleId)->first()) {
                    $this->rafraichir($user, $googleUser);

                    return [$user, false];
                }

                if ($user = User::where('email', $email)->first()) {
                    $user->forceFill([
                        'google_id' => $googleId,
                        'google_avatar' => $googleUser->getAvatar(),
                        // Google vient de vérifier l'adresse : un compte qui
                        // attendait encore sa confirmation par e-mail est
                        // désormais confirmé, et par une source plus sûre.
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();

                    return [$user, false];
                }

                /*
                 | forceFill, ET NON create().
                 |
                 | `email_verified_at` ne figure pas dans $fillable — à raison :
                 | c'est un état de sécurité, il n'a rien à faire dans une
                 | affectation en masse alimentée par une requête. Passé à
                 | create(), il était donc SILENCIEUSEMENT IGNORÉ, et le compte
                 | naissait non vérifié malgré la vérification de Google.
                 |
                 | Ici la valeur ne vient d'aucun formulaire : elle vient de
                 | Google, après contrôle du drapeau email_verified. La poser
                 | explicitement est le geste juste.
                 */
                $user = (new User)->forceFill([
                    'name' => $this->nom($googleUser, $email),
                    'email' => $email,
                    'google_id' => $googleId,
                    'google_avatar' => $googleUser->getAvatar(),
                    'role' => User::ROLE_USER,
                    // Aucun mot de passe : voir la migration. NULL dit la
                    // vérité, un mot de passe aléatoire mentirait.
                    'password' => null,
                    'email_verified_at' => now(),
                ]);

                $user->save();

                // Une demande d'inscription en attente sur la même adresse
                // devient caduque : le compte existe désormais.
                PendingRegistration::where('email', $email)->delete();

                return [$user, true];
            });
        } catch (Throwable $e) {
            Log::error('Création du compte Google impossible', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [null, false];
        }
    }

    /** Ouvre l'essai gratuit et envoie la bienvenue, par l'événement habituel. */
    private function accueillir(User $user): void
    {
        event(new UserRegistered($user));
    }

    /** Met à jour ce qui a pu changer côté Google, sans jamais l'écraser en base. */
    private function rafraichir(User $user, UtilisateurGoogle $googleUser): void
    {
        $avatar = $googleUser->getAvatar();

        if ($avatar && $avatar !== $user->google_avatar) {
            $user->forceFill(['google_avatar' => $avatar])->save();
        }
    }

    /**
     * Le nom, avec un repli sur la partie locale de l'adresse.
     *
     * Certains comptes Google n'exposent pas de nom. Un compte sans nom
     * afficherait « Bonjour , » dans tous les e-mails du produit.
     */
    private function nom(UtilisateurGoogle $googleUser, string $email): string
    {
        $nom = trim((string) $googleUser->getName());

        if ($nom !== '') {
            return $nom;
        }

        return ucfirst(strtok($email, '@') ?: 'Client');
    }

    private function echec(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
