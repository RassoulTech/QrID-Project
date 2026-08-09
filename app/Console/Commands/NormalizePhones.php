<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\SenegalPhone;
use Illuminate\Console\Command;

class NormalizePhones extends Command
{
    protected $signature = 'phones:normalize {--dry-run : Affiche les changements sans les appliquer}';

    protected $description = 'Remet au format canonique +221XXXXXXXXX les numéros déjà enregistrés.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $changed = 0;
        $invalid = 0;

        User::whereNotNull('phone')->chunkById(200, function ($users) use (&$changed, &$invalid, $dry) {
            foreach ($users as $user) {
                $canonical = SenegalPhone::normalize($user->phone);

                if ($canonical === null) {
                    $invalid++;
                    $this->warn("User #{$user->id} : numéro non normalisable « {$user->phone} » (laissé tel quel).");

                    continue;
                }

                if ($canonical === $user->phone) {
                    continue;
                }

                $this->line("User #{$user->id} : « {$user->phone} » → « {$canonical} »");
                $changed++;

                if (! $dry) {
                    $user->forceFill(['phone' => $canonical])->save();
                }
            }
        });

        $mode = $dry ? '[DRY-RUN] ' : '';
        $this->info("{$mode}Numéros à normaliser : {$changed} · non normalisables : {$invalid}");

        return self::SUCCESS;
    }
}
