<?php

namespace App\Events;

use App\Models\Profile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * La carte est EN LIGNE : son lien public répond.
 *
 * Deux chemins y mènent, et un seul événement les couvre :
 *   · l'activation directe, possible pendant l'essai gratuit ;
 *   · l'encaissement d'un paiement, qui publie dans la foulée.
 *
 * ATTENTION — « publié » n'égale pas « visible ». isPubliclyVisible() exige
 * AUSSI un abonnement actif. Une carte publiée dont l'abonnement a expiré est
 * hors ligne sans que is_active ait changé. L'e-mail correspondant doit donc
 * parler du lien, jamais promettre une visibilité perpétuelle.
 */
class ProfilePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Profile $profile) {}
}
