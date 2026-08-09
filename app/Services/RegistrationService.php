<?php

namespace App\Services;

use App\Enums\RegistrationOutcome;
use App\Mail\AlreadyRegisteredMail;
use App\Mail\ConfirmRegistrationMail;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Toute la logique d'inscription. Le contrôleur ne fait que valider, appeler
 * ce service et rediriger.
 *
 * PRINCIPE ANTI-ÉNUMÉRATION
 * L'écran affiché est rigoureusement identique quel que soit l'état de
 * l'adresse. La méthode handle() retourne toujours un RegistrationOutcome,
 * jamais exploité pour brancher l'affichage : il ne sert qu'à la
 * journalisation et aux tests. La différenciation n'existe que dans
 * l'e-mail envoyé, lisible par le seul propriétaire de la boîte.
 *
 * TEMPS DE RÉPONSE CONSTANT
 * Le hachage du mot de passe (bcrypt, ~200 ms) est le poste dominant. Il est
 * exécuté dans TOUS les cas, y compris ceux qui n'écrivent rien en base, afin
 * qu'aucun écart de durée ne permette de deviner l'état de l'adresse.
 */
class RegistrationService
{
    /**
     * Traite une demande d'inscription.
     *
     * @param  array{name:string,email:string,password:string}  $data
     */
    public function handle(array $data, string $canonicalPhone, ?string $ip): RegistrationOutcome
    {
        $email = $data['email'];

        // Toujours hashé, même si le résultat est jeté : temps constant.
        $hashedPassword = Hash::make($data['password']);

        $outcome = $this->resolve($email, $data['name'], $canonicalPhone, $hashedPassword, $ip);

        Log::channel('mail')->info('Demande d\'inscription traitée', [
            'email' => $email,
            'outcome' => $outcome->value,
            'detail' => $outcome->label(),
        ]);

        return $outcome;
    }

    private function resolve(
        string $email,
        string $name,
        string $phone,
        string $hashedPassword,
        ?string $ip
    ): RegistrationOutcome {
        // --- CAS 2 et 5 : un compte existe déjà pour cette adresse ----------
        // Cas 5 (compte non vérifié) est impossible par construction : aucun
        // User n'est créé avant validation du lien. Si un tel compte existait
        // malgré tout (données héritées), il est traité comme le cas 2.
        if (User::where('email', $email)->exists()) {
            // Une éventuelle demande en attente devient caduque.
            PendingRegistration::where('email', $email)->delete();

            Mail::to($email)->queue((new AlreadyRegisteredMail(
                loginUrl: route('login'),
                resetUrl: route('password.request'),
                recipient: $email,
            ))->onQueue('mail'));

            return RegistrationOutcome::AccountExists;
        }

        $pending = PendingRegistration::where('email', $email)->latest('id')->first();

        // --- CAS 3 : une demande en attente existe et n'est pas expirée -----
        if ($pending && ! $pending->isExpired()) {
            if ($pending->resend_count >= config('registration.max_resends')) {
                // Limite atteinte : même écran, aucun e-mail supplémentaire.
                return RegistrationOutcome::ResendLimitReached;
            }

            [$raw, $hash] = $this->makeToken();

            $pending->forceFill([
                // Les informations les plus récentes remplacent les anciennes.
                'name' => $name,
                'phone' => $phone,
                'password' => $hashedPassword,
                'token_hash' => $hash,
                'expires_at' => $this->expiry(),
                'last_sent_at' => Carbon::now(),
                'resend_count' => $pending->resend_count + 1,
            ])->save();

            $this->sendConfirmation($pending, $raw);

            return RegistrationOutcome::PendingRefreshed;
        }

        // --- CAS 4 : une demande existe mais a expiré ----------------------
        $wasExpired = false;

        if ($pending && $pending->isExpired()) {
            $pending->delete();
            $wasExpired = true;
        }

        // --- CAS 1 (et suite du cas 4) : création d'une demande complète ----
        [$raw, $hash] = $this->makeToken();

        $created = PendingRegistration::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $hashedPassword,
            'token_hash' => $hash,
            'expires_at' => $this->expiry(),
            'ip_hash' => $this->ipHash($ip),
            'resend_count' => 0,
            'last_sent_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);

        $this->sendConfirmation($created, $raw);

        return $wasExpired
            ? RegistrationOutcome::ExpiredReplaced
            : RegistrationOutcome::Created;
    }

    /**
     * Renvoi explicite depuis la page de confirmation.
     * Retourne false si la limite ou le délai de renvoi n'est pas respecté.
     */
    public function resend(PendingRegistration $pending): bool
    {
        if ($pending->resend_count >= config('registration.max_resends')) {
            return false;
        }

        $cooldown = config('registration.resend_cooldown_seconds');

        if ($pending->last_sent_at && $pending->last_sent_at->diffInSeconds(Carbon::now()) < $cooldown) {
            return false;
        }

        [$raw, $hash] = $this->makeToken();

        $pending->forceFill([
            'token_hash' => $hash,
            'expires_at' => $this->expiry(),
            'last_sent_at' => Carbon::now(),
            'resend_count' => $pending->resend_count + 1,
        ])->save();

        $this->sendConfirmation($pending, $raw);

        return true;
    }

    private function sendConfirmation(PendingRegistration $pending, string $rawToken): void
    {
        $ttl = config('registration.verification_ttl');

        /*
         | Aide de développement. Le jeton en clair n'est jamais persisté (seul
         | son sha256 l'est) : on le dépose en session pour que la page de
         | confirmation puisse afficher un lien cliquable SANS le régénérer.
         |
         | C'est tout l'objet du correctif : la page régénérait le jeton à
         | chaque affichage, ce qui tuait celui qui venait de partir par e-mail.
         | L'inscription devenait alors impossible à confirmer en local.
         |
         | Local uniquement, et la session appartient déjà à la personne qui
         | vient de soumettre le formulaire : rien n'est exposé.
         */
        if (app()->environment('local')) {
            session()->put('registration.dev_token', $rawToken);
        }

        Mail::to($pending->email)->queue(
            (new ConfirmRegistrationMail(
                $pending->name,
                route('registration.confirm', ['token' => $rawToken]),
                $ttl,
                $pending->email
            ))->onQueue('mail')
        );
    }

    /** @return array{0:string,1:string} [jeton en clair, hash sha256] */
    private function makeToken(): array
    {
        $raw = Str::random(64);

        return [$raw, hash('sha256', $raw)];
    }

    private function expiry(): Carbon
    {
        return Carbon::now()->addMinutes(config('registration.verification_ttl'));
    }

    private function ipHash(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip.config('app.key')) : null;
    }
}
