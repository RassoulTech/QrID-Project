<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Contrôle de la chaîne d'envoi, en synchrone et sans filet.
 *
 * AUCUN try/catch : toute exception remonte intégralement, avec sa trace. Sur
 * un outil de diagnostic, un faux succès coûte infiniment plus cher qu'une
 * erreur brute — c'est exactement ce qui a fait chercher pendant des jours
 * une panne d'envoi que rien ne signalait.
 *
 * LE TABLEAU S'ADAPTE AU TRANSPORT. Il affichait autrefois l'hôte et le port
 * SMTP en toutes circonstances : sous Resend, qui n'utilise ni l'un ni
 * l'autre, on lisait « smtp.gmail.com » pendant qu'un appel HTTPS partait
 * vers Resend. Un diagnostic qui décrit une configuration qui n'est pas
 * utilisée est pire qu'aucun diagnostic.
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {email}';

    protected $description = 'Envoie un e-mail de contrôle SYNCHRONE et affiche le résultat brut du transport.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $pilote = (string) config('mail.default');

        $this->newLine();
        $this->line('<comment>Configuration réellement utilisée</comment>');
        $this->table(['Paramètre', 'Valeur'], [
            ['Transport (mail.default)', $pilote],
            ...$this->detailsDuTransport($pilote),
            ['Expéditeur', config('mail.from.address')],
            ['Nom affiché', config('mail.from.name')],
            ['Destinataire', $email],
            ['File (queue.default)', config('queue.default')],
        ]);

        if ($pilote === 'log') {
            $this->error('ATTENTION : transport « log ». Rien ne partira réellement.');
            $this->line('Corriger MAIL_MAILER dans .env puis : php artisan config:clear');

            return self::FAILURE;
        }

        if ($pilote === 'resend' && blank(config('services.resend.key'))) {
            $this->error('RESEND_API_KEY est vide : aucun envoi n\'est possible.');
            $this->line('Renseigner la clé dans .env puis : php artisan config:clear');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Envoi synchrone en cours (aucune file, aucune exception avalée)...');

        $depart = microtime(true);

        Mail::raw(
            "Test de la chaîne d'envoi — ".config('app.name')."\n"
            .'Envoyé le '.now()->format('d/m/Y à H:i:s')."\n"
            .'Transport : '.$pilote."\n\n"
            .'Si vous lisez ce message, la chaîne d\'envoi fonctionne.',
            function ($message) use ($email) {
                $message->to($email)->subject('Test envoi — '.config('app.name'));
            }
        );

        $ms = round((microtime(true) - $depart) * 1000);

        $this->newLine();
        $this->info("Transport OK — message accepté en {$ms} ms.");
        $this->newLine();
        $this->line('<comment>Ce que cela prouve</comment> : le message a été construit et remis au fournisseur.');
        $this->line('<comment>Ce que cela ne prouve pas</comment> : la livraison finale, décidée par');
        $this->line('le serveur du destinataire (boîte de réception, spam, ou rejet).');

        if ($pilote === 'resend' && str_contains((string) config('mail.from.address'), '@resend.dev')) {
            $this->newLine();
            $this->warn('Adresse d\'expédition de test : Resend n\'écrira qu\'à l\'adresse');
            $this->warn('du titulaire du compte. Tout autre destinataire sera refusé,');
            $this->warn('jusqu\'à la vérification d\'un domaine.');
        }

        $this->newLine();
        $this->line('Journal des envois : php artisan mail:history');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Les paramètres qui comptent VRAIMENT pour le transport en cours.
     *
     * @return array<int, array{string, string}>
     */
    private function detailsDuTransport(string $pilote): array
    {
        return match ($pilote) {
            'resend' => [
                ['Voie', 'HTTPS (port 443) — aucun port SMTP'],
                ['Clé API', $this->cleMasquee((string) config('services.resend.key'))],
            ],
            'smtp' => [
                ['Hôte SMTP', (string) config('mail.mailers.smtp.host')],
                ['Port SMTP', (string) config('mail.mailers.smtp.port')],
                ['Délai d\'attente', config('mail.mailers.smtp.timeout').' s'],
                ['Utilisateur', (string) config('mail.mailers.smtp.username')],
            ],
            default => [],
        };
    }

    /**
     * La clé n'est jamais affichée en entier.
     *
     * Une commande de diagnostic finit copiée-collée dans une conversation
     * pour demander de l'aide. On montre assez pour vérifier qu'une clé est
     * bien là et laquelle, jamais assez pour s'en servir.
     */
    private function cleMasquee(string $cle): string
    {
        if ($cle === '') {
            return '— ABSENTE —';
        }

        return mb_substr($cle, 0, 6).str_repeat('•', 8).mb_substr($cle, -4);
    }
}
