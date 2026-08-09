<?php

namespace App\Console\Commands;

use App\Models\MailLog;
use Illuminate\Console\Command;

class MailHistory extends Command
{
    protected $signature = 'mail:history {--limit=20 : Nombre d\'envois à afficher}
                            {--failed : N\'afficher que les échecs}
                            {--email= : Filtrer sur un destinataire}';

    protected $description = 'Affiche les derniers e-mails émis avec leur statut.';

    public function handle(): int
    {
        $query = MailLog::query()->latest('id');

        if ($this->option('failed')) {
            $query->where('status', 'failed');
        }

        if ($email = $this->option('email')) {
            $query->where('recipient', 'like', '%'.$email.'%');
        }

        $logs = $query->limit((int) $this->option('limit'))->get();

        if ($logs->isEmpty()) {
            $this->warn('Aucun envoi enregistré.');
            $this->line('Si des e-mails ont été déclenchés, vérifier que la table mail_logs est migrée.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Date', 'Destinataire', 'Type', 'Statut', 'Erreur'],
            $logs->reverse()->map(fn (MailLog $log) => [
                $log->sent_at?->format('d/m H:i:s'),
                $log->recipient,
                class_basename($log->mailable ?? '—'),
                $log->status === 'sent' ? 'envoyé' : 'ÉCHEC',
                $log->error ? mb_substr($log->error, 0, 60).'…' : '',
            ])->all()
        );

        $sent = $logs->where('status', 'sent')->count();
        $failed = $logs->where('status', 'failed')->count();

        $this->line("Envoyés : <info>{$sent}</info> · Échecs : ".($failed > 0 ? "<error>{$failed}</error>" : '0'));
        $this->newLine();
        $this->line('« envoyé » signifie : accepté par le serveur SMTP.');
        $this->line('La livraison finale (boîte de réception ou spam) dépend du destinataire.');
        $this->newLine();

        return self::SUCCESS;
    }
}
