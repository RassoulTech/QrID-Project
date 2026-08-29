<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Mail\AlreadyRegisteredMail;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ConfirmRegistrationController extends Controller
{
    /**
     * Page affichée après l'inscription : e-mail masqué, marche à suivre,
     * rappel spams, bouton de renvoi, lien support.
     */
    public function pending(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('registration.pending_email');

        if (! $email) {
            return redirect()->route('register');
        }

        /*
         | Le compteur de renvois et le délai affichés viennent de la SESSION,
         | jamais de la table pending_registrations.
         |
         | C'est le cœur du principe anti-énumération : une demande neuve, une
         | adresse déjà inscrite et une demande déjà en cours produisent
         | exactement la même page. Lire resend_count en base afficherait
         | « 2 renvois » au lieu de « 3 » dans le seul cas où une demande
         | existait déjà — ce qui révélerait l'état de l'adresse à quiconque
         | soumet le formulaire.
         |
         | La limite réelle reste appliquée en base par RegistrationService :
         | ces valeurs ne servent QU'À l'affichage.
         */
        $max = (int) config('registration.max_resends');
        $cooldown = (int) config('registration.resend_cooldown_seconds');

        $envois = (int) $request->session()->get('registration.resend_count', 0);
        $dernier = $request->session()->get('registration.last_sent_at');

        $attente = $dernier
            ? max(0, $cooldown - (now()->getTimestamp() - (int) $dernier))
            : 0;

        return view('auth.registration.pending', [
            'maskedEmail' => $this->maskEmail($email),
            'supportWhatsapp' => config('registration.support_whatsapp'),

            // Renvois encore possibles, jamais négatif.
            'resendsLeft' => max(0, $max - $envois),
            'resendWait' => $attente,
            // Aide de développement : lien affiché à l'écran, jamais hors local.
            'devConfirmUrl' => app()->environment('local')
                ? $this->devConfirmUrl($request)
                : null,
        ]);
    }

    /**
     * Confirme la demande et crée le compte. Seul endroit de création d'un User.
     * Token à usage unique, comparé en temps constant.
     */
    public function confirm(string $token): View|RedirectResponse
    {
        $hash = hash('sha256', $token);
        $pending = PendingRegistration::where('token_hash', $hash)->first();

        // CAS 6 — lien déjà consommé (jeton à usage unique) ou invalide.
        // Message NEUTRE : ni félicitations ni alarme. On ne confirme pas non
        // plus qu'un compte existe pour cette adresse.
        if (! $pending || ! hash_equals($pending->token_hash, $hash)) {
            return redirect()->route('login')
                ->with('info', __('auth.flash.lien_perime'));
        }

        // CAS 8 — une session est déjà ouverte : on la ferme avant de traiter
        // le lien, pour ne jamais confirmer un compte dans la session d'un autre.
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        // Lien expiré : page dédiée, avec sa propre URL, où l'adresse suffit à
        // relancer l'inscription. On redirige plutôt que de rendre la vue ici,
        // pour que la page soit rechargeable et atteignable par son nom.
        if ($pending->isExpired()) {
            $email = $pending->email;
            $pending->delete();

            return redirect()->route('registration.expired')
                ->with('registration.expired_email', $email);
        }

        // Un compte a pu être créé entre-temps (course, lien rejoué).
        if (User::where('email', $pending->email)->exists()) {
            $pending->delete();

            return redirect()->route('login')
                ->with('success', __('auth.flash.deja_confirme'));
        }

        $user = DB::transaction(function () use ($pending) {
            $user = User::create([
                'name' => $pending->name,
                'email' => $pending->email,
                'phone' => $pending->phone,
                'password' => $pending->password, // déjà hashé (cast « hashed » ne re-hashe pas)
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $pending->delete();

            return $user;
        });

        event(new UserRegistered($user));

        // Session propre : on remplace toute session déjà connectée.
        Auth::login($user);
        request()->session()->regenerate();
        request()->session()->forget([
            'registration.pending_email',
            'registration.resend_count',
            'registration.last_sent_at',
            'registration.dev_token',
        ]);

        return redirect()->route('dashboard')->with(
            'success',
            'Bienvenue '.$user->name.' ! Votre compte est actif. Créez votre profil professionnel pour commencer.'
        );
    }

    /**
     * Lien de confirmation expiré. Une seule saisie suffit à relancer.
     *
     * L'adresse vient du flash posé par confirm() ; un accès direct à l'URL
     * affiche simplement le champ vide, jamais une erreur.
     */
    public function expired(Request $request): View
    {
        return view('auth.registration.expired', [
            'email' => $request->session()->get('registration.expired_email'),
        ]);
    }

    /**
     * Renvoie l'e-mail de confirmation. Réponse identique dans tous les cas.
     */
    public function resend(Request $request, RegistrationService $service): RedirectResponse
    {
        $email = $request->session()->get('registration.pending_email');

        if (! $email) {
            return redirect()->route('register');
        }

        $pending = PendingRegistration::where('email', $email)->latest('id')->first();

        if ($pending) {
            $service->resend($pending); // false silencieux si cooldown/limite atteinte
        } elseif (User::where('email', $email)->exists()) {
            // Cas compte existant : renvoi limité, message identique.
            $key = 'register-attempt-resend:'.hash('sha256', $email);
            if (! RateLimiter::tooManyAttempts($key, config('registration.max_resends'))) {
                RateLimiter::hit($key, 3600);
                Mail::to($email)->queue((new AlreadyRegisteredMail(
                    loginUrl: route('login'),
                    resetUrl: route('password.request'),
                    recipient: $email,
                ))->onQueue('mail'));
            }
        }

        /*
         | Compteur d'affichage. Incrémenté dans TOUS les cas, y compris quand
         | rien n'a été envoyé : sinon le nombre de renvois restants trahirait
         | l'état de l'adresse. Voir pending().
         */
        $request->session()->increment('registration.resend_count');
        $request->session()->put('registration.last_sent_at', now()->getTimestamp());

        return back()->with(
            'success',
            'Si une demande est en cours, un nouvel e-mail vient de vous être envoyé.'
        );
    }

    /**
     * Abandonne la demande en cours et renvoie au formulaire vierge.
     * Vide la session de demande pour ne pas rester coincé sur l'écran.
     */
    public function abandon(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'registration.pending_email',
            'registration.idem',
            'registration.resend_count',
            'registration.last_sent_at',
            'registration.dev_token',
        ]);

        return redirect()->route('register');
    }

    /**
     * URL de confirmation affichée à l'écran en développement.
     *
     * DÉVELOPPEMENT UNIQUEMENT — double garde : ici et à l'appel.
     *
     * ELLE NE TOUCHE PLUS À LA BASE. Cette méthode régénérait le jeton à chaque
     * affichage de la page, ce qui invalidait celui qui venait de partir par
     * e-mail : le lien reçu ne correspondait plus à rien, le clic tombait sur
     * « ce lien a déjà été utilisé », aucun compte n'était créé, et « Renvoyer »
     * rejouait exactement le même piège. L'inscription était infranchissable
     * en local.
     *
     * Le jeton en clair vient donc de la session, où RegistrationService l'a
     * déposé au moment de l'envoi.
     */
    private function devConfirmUrl(Request $request): ?string
    {
        if (! app()->environment('local')) {
            return null;
        }

        $raw = $request->session()->get('registration.dev_token');

        return $raw ? route('registration.confirm', ['token' => $raw]) : null;
    }

    /**
     * Masque l'adresse : premier + dernier caractère du nom local visibles.
     * exemple  →  e****e@gmail.com
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + [1 => ''];

        if (mb_strlen($local) <= 2) {
            $masked = mb_substr($local, 0, 1).'***';
        } else {
            $masked = mb_substr($local, 0, 1).'****'.mb_substr($local, -1);
        }

        return $masked.'@'.$domain;
    }
}
