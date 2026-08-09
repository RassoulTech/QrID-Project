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
