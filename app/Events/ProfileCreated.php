<?php

namespace App\Events;

use App\Models\Profile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * La carte vient d'être ENREGISTRÉE — elle n'est pas encore publique.
 *
 * Distinct de ProfilePublished, et la distinction n'est pas cosmétique : entre
 * les deux se trouve l'abandon, c'est-à-dire la carte remplie que personne
 * n'active jamais. C'est précisément cet écart que les rappels à 24 h et 72 h
 * cherchent à combler ; sans deux événements séparés, il serait invisible.
 *
 * Émis une seule fois, à la création. Une modification ultérieure du profil
 * ne le réémet pas : « votre carte est créée » deux fois ne dit rien de neuf.
 */
class ProfileCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Profile $profile) {}
}
