<?php

namespace App\Console\Commands;

use App\Models\PendingRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PurgeExpiredRegistrations extends Command
{
    protected $signature = 'registrations:purge';

    protected $description = 'Supprime les demandes d\'inscription expirées depuis plus de 24 heures.';

    public function handle(): int
    {
        $threshold = Carbon::now()->subDay();

        $deleted = PendingRegistration::where('expires_at', '<', $threshold)->delete();

        Log::info('Purge des inscriptions en attente', ['deleted' => $deleted]);
        $this->info("Demandes en attente purgées : {$deleted}");

        return self::SUCCESS;
    }
}
