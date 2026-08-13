<?php

namespace App\Console\Commands;

use App\Mail\ProfileReminderMail;
use App\Models\Profile;
use App\Support\Courrier;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Relance les cartes créées mais jamais publiées — à 24 h, puis à 72 h.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX RAPPELS, ET LE SECOND EST LE DERNIER
 * ═══════════════════════════════════════════════════════════════════════
 * `reminder_count` borne la séquence : à 2, le profil ne ressort plus jamais
 * de la requête. Aucune condition de date ne peut le rattraper.
 *
 * Ce n'est pas de la politesse. Un troisième message ne convainc personne et
 * fait basculer l'expéditeur dans les indésirables — une réputation qui se
 * paie ensuite sur la réinitialisation de mot de passe, c'est-à-dire sur
 * quelqu'un qui ne peut plus se connecter et qui, lui, attend vraiment.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES BORNES SONT DES MINIMUMS, PAS DES FENÊTRES
 * ═══════════════════════════════════════════════════════════════════════
 * On sélectionne « créé il y a AU MOINS 24 h », non « entre 24 et 48 h ».
 *
 * La différence compte le jour où la commande n'a pas tourné — planificateur
 * arrêté, incident d'hébergement, déploiement raté. Avec une fenêtre, les
 * profils passés à travers ne sont jamais rattrapés : ils sont sortis par le
 * haut et personne ne le saura. Avec un minimum, la première exécution qui
 * suit les reprend tous.
 *
 * Le compteur, lui, garantit qu'un rattrapage n'envoie pas deux messages
 * d'un coup : chaque exécution ne fait avancer la séquence que d'un cran.
 */
class RemindUnpublishedProfiles extends Command
{
    protected $signature = 'profiles:remind {--dry-run : Affiche sans envoyer}';

    protected $description = 'Relance les clients dont la carte est créée mais non publiée (24 h puis 72 h).';

    /** Heures écoulées exigées avant chaque rappel. */
    private const DELAIS = [1 => 24, 2 => 72];

    public function handle(): int
    {
        $simulation = (bool) $this->option('dry-run');
        $envoyes = 0;

        foreach (self::DELAIS as $rang => $heures) {
            $profils = $this->aRelancer($rang, $heures)->get();

            foreach ($profils as $profil) {
                $user = $profil->user;

                if (! $user) {
                    continue;   // profil orphelin : personne à relancer
                }

                $this->line(sprintf(
                    '  rappel %d · %s · %s',
                    $rang,
                    $user->email,
                    $profil->slug
                ));

                if ($simulation) {
                    continue;
                }

                Courrier::informer($user->email, new ProfileReminderMail(
                    name: $user->name,
                    activateUrl: route('profile.preview'),
                    rang: $rang,
                    recipient: $user->email,
                ));

                /*
                 | LE COMPTEUR AVANCE MÊME SI L'ENVOI A ÉCHOUÉ.
                 |
                 | Courrier rend false sur panne, et l'on pourrait vouloir
                 | réessayer demain. Mais une panne de messagerie dure rarement
                 | un jour : au rétablissement, tous les profils accumulés
                 | partiraient d'un coup, dont certains publiés entre-temps.
                 | Un rappel manqué coûte moins qu'une salve.
                 |
                 | L'échec n'est pas perdu pour autant : il est dans mail_logs,
                 | visible sur l'écran « État système ».
                 */
                $profil->forceFill([
                    'reminder_count' => $rang,
                    'reminder_sent_at' => now(),
                ])->save();

                $envoyes++;
            }
        }

        Log::info('Relance des cartes non publiées', [
            'envoyes' => $envoyes,
            'simulation' => $simulation,
        ]);

        $this->info($simulation
            ? 'Simulation terminée — aucun e-mail envoyé.'
            : "Rappels envoyés : {$envoyes}");

        return self::SUCCESS;
    }

    /**
     * Les profils éligibles au rappel de rang N.
     *
     * Quatre conditions, toutes nécessaires :
     *   · jamais publié — sinon le rappel n'a plus d'objet ;
     *   · pas désactivé par l'administration — la relance contredirait la
     *     décision prise, et renverrait vers un écran qui refusera ;
     *   · créé depuis assez longtemps ;
     *   · rang précédent atteint, et celui-ci pas encore.
     */
    private function aRelancer(int $rang, int $heures): Builder
    {
        return Profile::query()
            ->with('user:id,name,email')
            ->where('is_active', false)
            ->whereNull('deactivated_at')
            ->where('reminder_count', $rang - 1)
            ->where('created_at', '<=', now()->subHours($heures));
    }
}
