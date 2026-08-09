<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QueueHealth extends Command
{
    protected $signature = 'queue:health {--max=50 : Seuil d\'alerte sur la file mail}';

    protected $description = 'Détecte un worker arrêté : jobs bloqués, file qui s\'accumule, échecs.';

    public function handle(): int
    {
        $max = (int) $this->option('max');

        $mail = DB::table('jobs')->where('queue', 'mail')->count();
        $total = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        // Un job créé il y a plus de 2 minutes et toujours en attente
        // signale très probablement un worker arrêté.
        $stale = DB::table('jobs')
            ->where('created_at', '<', Carbon::now()->subMinutes(2)->getTimestamp())
            ->count();

        $this->table(
            ['Indicateur', 'Valeur'],
            [
                ['File « mail » en attente', $mail],
                ['Total jobs en attente', $total],
                ['Jobs bloqués (> 2 min)', $stale],
                ['Jobs échoués', $failed],
            ]
        );

        $problem = false;

        if ($stale > 0) {
            $this->error("{$stale} job(s) en attente depuis plus de 2 minutes : le worker est probablement ARRÊTÉ.");
            $this->line('  → Lancer : php artisan queue:work database --queue=mail,default');
            $problem = true;
        }

        if ($mail > $max) {
            $this->warn("File « mail » engorgée : {$mail} > {$max}.");
            $problem = true;
        }

        if ($failed > 0) {
            $this->error("{$failed} job(s) en échec définitif.");
            $this->line('  → Inspecter : php artisan queue:failed   puis   php artisan queue:retry all');
            $problem = true;
        }

        if (! $problem) {
            $this->info('File d\'attente saine.');
        }

        return $problem ? self::FAILURE : self::SUCCESS;
    }
}
