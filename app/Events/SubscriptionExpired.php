<?php

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * L'échéance est passée : la carte n'est plus consultable.
 *
 * C'est le seul e-mail du produit qui annonce une PERTE. Il doit donc être
 * exact au mot près : les données du client ne sont pas supprimées, sa carte
 * n'est pas détruite, seul le lien public cesse de répondre. Laisser croire à
 * une suppression ferait fuir quelqu'un qui serait revenu.
 */
class SubscriptionExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(public Subscription $subscription) {}
}
