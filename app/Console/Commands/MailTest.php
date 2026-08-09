<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {email}';

    protected $description = 'Envoie un e-mail de contrôle SYNCHRONE et affiche le résultat brut du transport.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $this->newLine();
        $this->line('<comment>Configuration réellement utilisée</comment>');
        $this->table(['Paramètre', 'Valeur'], [
            ['Driver (mail.default)', config('mail.default')],
            ['Hôte SMTP', config('mail.mailers.smtp.host')],
            ['Port SMTP', config('mail.mailers.smtp.port')],
            ['Chiffrement', config('mail.mailers.smtp.scheme') ?? env('MAIL_ENCRYPTION', '—')],
            ['Utilisateur', config('mail.mailers.smtp.username')],
            ['Expéditeur', config('mail.from.address')],
            ['Nom affiché', config('mail.from.name')],
            ['Destinataire', $email],
            ['File (queue.default)', config('queue.default')],
        ]);

        if (config('mail.default') === 'log') {
            $this->error('ATTENTION : driver « log ». Rien ne partira réellement.');
            $this->line('Corriger MAIL_MAILER dans .env puis : php artisan config:clear');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Envoi synchrone en cours (aucune file, aucune exception avalée)...');

        $start = microtime(true);

        // AUCUN try/catch : toute exception remonte intégralement, avec sa trace.
        // Un faux succès est plus coûteux qu'une erreur brute.
        Mail::raw(
            "Test de la chaîne d'envoi — ".config('app.name')."\n"
            .'Envoyé le '.now()->format('d/m/Y à H:i:s')."\n\n"
            .'Si vous lisez ce message, le transport SMTP fonctionne.',
            function ($message) use ($email) {
                $message->to($email)->subject('Test envoi — '.config('app.name'));
            }
        );

        $ms = round((microtime(true) - $start) * 1000);

        $this->newLine();
        $this->info("Transport OK — message accepté par le serveur SMTP en {$ms} ms.");
        $this->newLine();
        $this->line('<comment>Ce que cela prouve</comment> : le message a été construit et remis à Gmail.');
        $this->line('<comment>Ce que cela ne prouve pas</comment> : la livraison finale, décidée par');
        $this->line('le serveur du destinataire (boîte de réception, spam, ou rejet).');
        $this->newLine();
        $this->line('Vérifier le journal des envois : php artisan mail:history');
        $this->newLine();

        return self::SUCCESS;
    }
}
