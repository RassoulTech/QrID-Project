<?php

namespace App\Console\Commands\Dev;

/**
 * Extrait les liens de l'application contenus dans les derniers e-mails
 * écrits dans le log (driver « log »). Évite tout grep manuel.
 */
class DevMails extends DevCommand
{
    protected $signature = 'dev:mails {--lines=3000 : Nombre de lignes de log à analyser}
                            {--all : Afficher tous les liens, pas seulement les derniers}';

    protected $description = '[LOCAL] Affiche les liens contenus dans les derniers e-mails du log.';

    public function handle(): int
    {
        if (! $this->guardLocal()) {
            return self::FAILURE;
        }

        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            $this->error('Aucun fichier de log : '.$path);

            return self::FAILURE;
        }

        $lines = (int) $this->option('lines');
        $content = $this->tail($path, $lines);

        if ($content === '') {
            $this->warn('Log vide.');

            return self::SUCCESS;
        }

        $base = preg_quote(rtrim(config('app.url'), '/'), '#');

        preg_match_all('#'.$base.'/[^\s"\'<>\)\]]+#i', $content, $matches);

        $links = array_values(array_unique($matches[0] ?? []));

        // On ne garde que les liens actionnables (confirmation, réinitialisation).
        $interesting = array_values(array_filter($links, fn ($l) => (bool) preg_match(
            '#(inscription/confirmer|reset-password|verifier)#i', $l
        )));

        $toShow = $interesting ?: $links;

        if (empty($toShow)) {
            $this->warn('Aucun lien trouvé dans les '.$lines.' dernières lignes.');
            $this->line('Vérifie que MAIL_MAILER=log et qu\'un e-mail a bien été déclenché.');

            return self::SUCCESS;
        }

        if (! $this->option('all')) {
            $toShow = array_slice($toShow, -5);
        }

        $this->newLine();
        $this->info('Liens trouvés (du plus ancien au plus récent) :');
        $this->newLine();

        foreach ($toShow as $link) {
            $label = match (true) {
                str_contains($link, 'inscription/confirmer') => 'Confirmation d\'inscription',
                str_contains($link, 'reset-password') => 'Réinitialisation de mot de passe',
                default => 'Lien',
            };

            $this->line("  <info>{$label}</info>");
            $this->line("  <comment>{$link}</comment>");
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /** Lit les N dernières lignes d'un fichier sans le charger entièrement. */
    private function tail(string $path, int $lines): string
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $last = $file->key();

        $start = max(0, $last - $lines);
        $out = '';

        $file->seek($start);
        while (! $file->eof()) {
            $out .= $file->fgets();
        }

        return $out;
    }
}
