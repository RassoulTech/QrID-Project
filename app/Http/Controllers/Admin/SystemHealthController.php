<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use App\Models\PendingRegistration;
use App\Support\Planificateur;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ÉTAT SYSTÈME — l'écran qui rend visible ce qui échoue en silence.
 *
 * Il existe pour une raison précise : un e-mail qui ne part pas ne produit
 * aucune erreur. La page répond, l'utilisateur voit « lien envoyé », et
 * personne ne reçoit rien. C'est arrivé sur la réinitialisation de mot de
 * passe, et rien dans l'application ne permettait de le constater.
 */
class SystemHealthController extends Controller
{
    public function index(): View
    {
        /*
         | LE CONTRÔLE D'ACCÈS A ÉTÉ RETIRÉ D'ICI, et c'était un défaut actif.
         |
         | Cette méthode comparait l'adresse du visiteur à ADMIN_ALERT_EMAIL et
         | refusait tout le monde d'autre. Or la route vit désormais sous le
         | middleware `admin` (routes/admin.php) : le contrôle était devenu
         | redondant — et nuisible. Les deux administrateurs de l'équipe, dont
         | l'adresse ne figure pas dans ADMIN_ALERT_EMAIL, recevaient un 403
         | sur un écran auquel leur rôle leur donne droit.
         |
         | Un second verrou qui ne protège rien mais ferme la porte à des
         | ayants droit est pire que pas de verrou.
         */

        $enFile = DB::table('jobs')->where('queue', 'mail')->count();

        /*
         | LE PILOTE DE FILE EST AFFICHÉ, et c'est le renseignement décisif.
         |
         | Sans worker qui exécute `queue:work`, un pilote autre que `sync`
         | fait disparaître les e-mails : le message est écrit dans la table
         | `jobs` et personne ne le reprend jamais. Aucune erreur, aucune
         | trace — la page confirme même l'envoi à l'utilisateur.
         */
        $pilote = config('queue.default');

        return view('admin.system-health', [
            /*
             | LE PLANIFICATEUR EST-IL VIVANT ?
             |
             | Un processus arrêté ressemble en tout point à un processus qui
             | n'a rien à faire : les deux ne produisent rien. Sans cette
             | mesure, une panne du planificateur se découvre des jours plus
             | tard, en constatant qu'une statistique n'a pas bougé.
             |
             | `null` — jamais battu — n'est PAS la même chose que « vient de
             | battre », et la vue doit pouvoir les distinguer.
             */
            'planificateurMinutes' => Planificateur::minutesDepuisLeDernierBattement(),

            'mailQueue' => $enFile,
            'totalJobs' => DB::table('jobs')->count(),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'pendingRegistrations' => PendingRegistration::count(),
            'queueAlert' => $enFile > 50,

            'pilote' => $pilote,

            // Un pilote de file sans worker déclaré : les e-mails s'empilent
            // sans partir. On le dit en toutes lettres sur l'écran.
            'fileSansWorker' => $pilote !== 'sync',

            'derniersMails' => MailLog::query()
                ->latest('id')
                ->limit(10)
                ->get(),

            'mailsDuJour' => MailLog::query()
                ->whereDate('created_at', today())
                ->count(),
        ]);
    }
}
