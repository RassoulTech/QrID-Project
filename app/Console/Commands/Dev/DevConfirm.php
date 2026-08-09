<?php

namespace App\Console\Commands\Dev;

use App\Models\PendingRegistration;
use Illuminate\Support\Str;

/**
 * Affiche l'URL de confirmation d'une inscription en attente,
 * sans avoir à ouvrir la moindre boîte mail.
 */
class DevConfirm extends DevCommand
{
    protected $signature = 'dev:confirm {email}';

    protected $description = '[LOCAL] Affiche l\'URL de confirmation d\'inscription pour une adresse.';

    public function handle(): int
    {
        if (! $this->guardLocal()) {
            return self::FAILURE;
        }

        $email = mb_strtolower(trim($this->argument('email')));

        $pending = PendingRegistration::where('email', $email)->latest('id')->first();

        if (! $pending) {
            $this->error("Aucune demande en attente pour {$email}.");
            $this->line('Inscris-toi d\'abord sur /register, ou utilise : php artisan dev:user '.$email);

            return self::FAILURE;
        }

        if ($pending->isExpired()) {
            $this->warn('Cette demande était EXPIRÉE ('.$pending->expires_at->diffForHumans().').');
            $this->line('Elle est réarmée ci-dessous : le lien fonctionnera.');
        }

        /*
         | Le jeton en clair n'est jamais stocké : on le régénère et on met le
         | hash à jour pour que l'URL affichée soit valide.
         |
         | Régénérer INVALIDE le lien déjà parti par e-mail. C'est acceptable
         | ici parce que la commande est lancée sciemment, à la main, et qu'elle
         | affiche le lien de remplacement. C'est en revanche exactement ce qui
         | ne devait pas se produire tout seul à l'affichage de la page de
         | confirmation — voir ConfirmRegistrationController::devConfirmUrl().
         */
        $raw = Str::random(64);

        $pending->forceFill([
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addMinutes(config('registration.verification_ttl')),
        ])->save();

        $url = route('registration.confirm', ['token' => $raw]);

        $this->newLine();
        $this->info('Demande trouvée pour '.$pending->email.' ('.$pending->name.')');
        $this->line('URL de confirmation (jeton régénéré, valable '.config('registration.verification_ttl').' min) :');
        $this->newLine();
        $this->line('<comment>'.$url.'</comment>');
        $this->newLine();

        return self::SUCCESS;
    }
}
