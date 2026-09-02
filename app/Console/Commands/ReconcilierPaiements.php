<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * LE FILET DES PAIEMENTS RESTÉS EN ATTENTE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'UN PAIEMENT « EN ATTENTE » SIGNIFIE VRAIMENT
 * ═══════════════════════════════════════════════════════════════════════
 * Une ligne `pending` veut dire : quelqu'un a cliqué sur « payer », et
 * personne ne sait ce qui s'est passé ensuite. Trois cas, indiscernables
 * depuis la base :
 *
 *   · il a abandonné avant de payer — la ligne est un déchet ;
 *   · il a payé et la confirmation ne nous est jamais parvenue — il attend
 *     un service qu'il a réglé ;
 *   · il a payé et la confirmation viendra plus tard.
 *
 * Le deuxième cas est le seul qui compte, et c'est le seul qu'un compteur
 * ne rend pas visible. Cette commande le met sous les yeux de quelqu'un.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ELLE NE DÉCIDE RIEN
 * ═══════════════════════════════════════════════════════════════════════
 * Elle ne valide aucun paiement et n'en annule aucun. Marquer `success`
 * sans preuve donnerait un abonnement à qui n'a pas payé ; marquer `failed`
 * effacerait la trace de qui a payé. Les deux erreurs se découvrent des
 * semaines plus tard, et aucune ne se rattrape proprement.
 *
 * Elle SIGNALE, et un humain tranche. C'est le bon partage tant que la
 * validation passe par WhatsApp : il n'existe aucune source de vérité
 * automatique à interroger.
 *
 * Le jour où une passerelle avec rappel serveur existera, c'est ici que sa
 * vérification se branchera — la structure est prête, la décision ne l'est
 * pas.
 *
 *     php artisan app:reconcilier-paiements
 *     php artisan app:reconcilier-paiements --jours=7
 */
class ReconcilierPaiements extends Command
{
    protected $signature = 'app:reconcilier-paiements
        {--jours=2 : Âge minimal, en jours, d\'un paiement à signaler}';

    protected $description = 'Signale les paiements restés en attente au-delà d\'un délai raisonnable.';

    public function handle(): int
    {
        $jours = max(1, (int) $this->option('jours'));
        $limite = now()->subDays($jours);

        $bloques = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->where('created_at', '<', $limite)
            ->with('user:id,name,email')
            ->orderBy('created_at')
            ->get();

        if ($bloques->isEmpty()) {
            $this->info("Aucun paiement en attente depuis plus de {$jours} jour(s).");

            return self::SUCCESS;
        }

        $this->warn("{$bloques->count()} paiement(s) en attente depuis plus de {$jours} jour(s) :");
        $this->newLine();

        $total = 0;

        foreach ($bloques as $paiement) {
            $age = (int) round($paiement->created_at->diffInDays(now(), absolute: true));
            $total += (int) $paiement->amount_fcfa;

            $this->line(sprintf(
                '  #%-6s %-14s %8s FCFA   %2d j   %s',
                $paiement->id,
                $paiement->method,
                number_format((int) $paiement->amount_fcfa, 0, ',', ' '),
                $age,
                $paiement->user?->email ?? '(compte supprimé)'
            ));
        }

        $this->newLine();
        $this->line('  Total immobilisé : '.number_format($total, 0, ',', ' ').' FCFA');

        /*
         | JOURNALISÉ, pour que le récapitulatif quotidien puisse le relayer
         | sans relancer la commande. Un chiffre qui n'apparaît que dans un
         | terminal n'est vu que par qui ouvre le terminal.
         */
        Log::warning('Paiements en attente prolongée', [
            'nombre' => $bloques->count(),
            'total_fcfa' => $total,
            'jours' => $jours,
        ]);

        /*
         | ELLE REND SUCCESS, MÊME QUAND ELLE TROUVE QUELQUE CHOSE.
         |
         | Elle rendait FAILURE, pour qu'une surveillance externe puisse s'y
         | brancher sans lire la sortie. L'intention était bonne ; la
         | surveillance externe, elle, n'a jamais existé — et rien
         | n'exécutait cette commande.
         |
         | Depuis que le planificateur tourne, ce code non nul produit une
         | ligne `production.ERROR: Scheduled command [...] failed` CHAQUE
         | JOUR où un paiement traîne. Or trouver un paiement en attente
         | n'est pas une panne : c'est le travail de cette commande, et
         | c'est même son seul résultat utile.
         |
         | Une erreur quotidienne qui n'en est pas une apprend à ignorer les
         | erreurs. Le jour où le planificateur tombera vraiment, sa ligne
         | rouge se perdra au milieu de celles-ci.
         |
         | Le signal reste entier, et à deux endroits : le Log::warning
         | ci-dessus, que le récapitulatif quotidien relaie, et la sortie de
         | la commande — désormais visible dans les journaux du service,
         | puisque les tâches planifiées n'écrivent plus vers /dev/null.
         */
        return self::SUCCESS;
    }
}
