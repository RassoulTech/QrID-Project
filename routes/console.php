<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge quotidienne des demandes d'inscription expirées depuis plus de 24 h.
Schedule::command('registrations:purge')->dailyAt('03:00');

// Surveillance de la file mail : émet QueueBusy au-delà de 50 jobs en attente.
Schedule::command('queue:monitor database:mail --max=50')->everyMinute();

/*
|------------------------------------------------------------------------------
| RELANCES QUOTIDIENNES — bloc 1 du plan de lancement
|------------------------------------------------------------------------------
| L'HEURE N'EST PAS ARBITRAIRE. 09h00 heure de Dakar : un e-mail commercial
| arrivé à 3 h du matin est enterré sous la nuit au réveil, et un rappel qui
| n'est pas lu ne sert à rien. Les deux commandes sont espacées pour ne pas
| additionner deux salves d'envois synchrones dans la même minute.
|
| ⚠ AVERTISSEMENT — CES TÂCHES NE S'EXÉCUTENT PAS EN PRODUCTION.
| Aucun processus ne lance `schedule:run` sur le plan gratuit de Render : un
| service web ne fait que répondre aux requêtes HTTP. C'est le risque n° 2 du
| plan, toujours ouvert. Les deux commandes sont écrites, testées et
| déclenchables à la main (`php artisan profiles:remind`) ; elles resteront
| muettes tant qu'un Cron Job Render n'existera pas.
|
| Ne pas les déclarer ici aurait été pire : le jour où le cron est créé, une
| seule ligne de configuration suffit et rien d'autre n'est à écrire.
*/
/*
 | ═══════════════════════════════════════════════════════════════════════
 | L'AGRÉGATION DES STATISTIQUES — 02:30, avant tout le reste
 | ═══════════════════════════════════════════════════════════════════════
 | Elle passe AVANT les rappels et le récapitulatif : ces deux-là lisent des
 | chiffres, et les lire avant l'agrégation donnerait le compte de l'avant-
 | veille sans que rien ne le signale.
 |
 | --purger supprime dans la foulée les événements bruts au-delà de la
 | rétention. La purge ne part QUE derrière une agrégation réussie : sans
 | elle, on supprimerait une source qu'on n'a pas encore résumée.
 |
 | withoutOverlapping : une agrégation qui déborde sur la suivante
 | travaillerait sur la même journée deux fois. L'upsert le supporterait —
 | il est rejouable — mais deux balayages simultanés de la table
 | d'événements se disputeraient les mêmes pages de disque pour rien.
 */
Schedule::command('app:agreger-statistiques --purger')
    ->dailyAt('02:30')
    ->timezone('Africa/Dakar')
    ->withoutOverlapping();

/*
 | LA SAUVEGARDE — hebdomadaire, le dimanche à 04:00.
 |
 | Elle passe APRÈS l'agrégation et la purge : sauvegarder avant reviendrait
 | à conserver chaque semaine des millions d'événements bruts qu'on
 | s'apprête à supprimer.
 |
 | Hebdomadaire et non quotidienne : Aiven garde déjà des sauvegardes
 | quotidiennes. Celle-ci est le filet du jour où le compte Aiven lui-même
 | devient inaccessible, et ce risque-là ne se matérialise pas en un jour.
 */
Schedule::command('app:sauvegarder')
    ->weeklyOn(0, '04:00')
    ->timezone('Africa/Dakar')
    ->withoutOverlapping();

/*
 | LES PAIEMENTS RESTÉS EN ATTENTE — tous les matins.
 |
 | Une ligne `pending` de plus de deux jours signifie soit un abandon, soit
 | quelqu'un qui a payé et attend un service. Le second cas ne se voit pas
 | tout seul : il faut aller le chercher.
 */
Schedule::command('app:reconcilier-paiements')
    ->dailyAt('08:45')
    ->timezone('Africa/Dakar');

Schedule::command('profiles:remind')->dailyAt('09:00')->timezone('Africa/Dakar');
Schedule::command('subscriptions:notify')->dailyAt('09:15')->timezone('Africa/Dakar');

/*
|------------------------------------------------------------------------------
| RÉCAPITULATIF QUOTIDIEN DISCORD — bloc 2 du plan de lancement
|------------------------------------------------------------------------------
| 21h00 heure de Dakar : la journée est finie, et le message est lu le soir ou
| au réveil, dans les deux cas avant la reprise.
|
| LE FUSEAU EST EXPLICITE, ET CE N'EST PAS DU ZÈLE. Le serveur tourne en UTC.
| Sans ->timezone(), le message partirait à 21 h UTC — ce qui donne 21 h à
| Dakar aujourd'hui, puisque le Sénégal est à UTC+0, mais cesserait d'être vrai
| au premier changement de région d'hébergement. Écrire le fuseau rend
| l'intention indépendante de l'infrastructure.
|
| withoutOverlapping : si un envoi s'éternise, le suivant ne se superpose pas.
| Deux récapitulatifs de la même journée dans le salon feraient douter de tous
| les autres.
*/
Schedule::command('report:daily')
    ->dailyAt(config('notifications.discord.heure', '21:00'))
    ->timezone(config('notifications.discord.fuseau', 'Africa/Dakar'))
    ->withoutOverlapping();
