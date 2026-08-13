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
Schedule::command('profiles:remind')->dailyAt('09:00')->timezone('Africa/Dakar');
Schedule::command('subscriptions:notify')->dailyAt('09:15')->timezone('Africa/Dakar');
