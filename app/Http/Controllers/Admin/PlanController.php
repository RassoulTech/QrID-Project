<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanRequest;
use App\Models\AdminAction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\AdminActionType;
use Illuminate\Http\RedirectResponse;

/**
 * Formules tarifaires — création et modification.
 *
 * TARIFICATION JOURNALISÉE, au même titre qu'un blocage de compte. Changer un
 * prix engage l'entreprise : six mois plus tard, la question « qui a passé
 * l'offre Entreprise de 120 000 à 90 000 et quand » doit avoir une réponse.
 *
 * LES ABONNEMENTS EN COURS NE SONT PAS TOUCHÉS. Un abonnement porte déjà sa
 * date de fin ; changer le prix de la formule ne rétro-facture personne et ne
 * raccourcit aucune échéance. Le nouveau tarif s'applique aux souscriptions
 * suivantes — c'est la seule règle défendable devant un client.
 */
class PlanController extends Controller
{
    public function store(PlanRequest $request): RedirectResponse
    {
        $plan = Plan::create($request->donnees());

        AdminAction::log(
            AdminActionType::PLAN_CREE,
            $plan,
            sprintf('« %s » — %s, %s', $plan->name, $plan->formattedPrice(), $plan->periodicite())
        );

        return redirect()
            ->route('admin.settings.plan', $plan)
            ->with('status', "La formule « {$plan->name} » est créée.");
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $avant = [
            'prix' => $plan->formattedPrice(),
            'periodicite' => $plan->periodicite(),
            'actif' => $plan->is_active,
        ];

        $plan->update($request->donnees());

        AdminAction::log(
            AdminActionType::PLAN_MODIFIE,
            $plan,
            $this->resume($plan, $avant)
        );

        return redirect()
            ->route('admin.settings.plan', $plan)
            ->with('status', $this->messageDeRetour($plan));
    }

    /**
     * Le motif du journal décrit CE QUI A CHANGÉ, pas l'état final.
     *
     * « Plan modifié » sur une ligne de journal n'apprend rien. « Prix :
     * 15 000 FCFA → 12 000 FCFA » se lit sans ouvrir autre chose.
     */
    private function resume(Plan $plan, array $avant): string
    {
        $changements = [];

        if ($avant['prix'] !== $plan->formattedPrice()) {
            $changements[] = "prix : {$avant['prix']} → {$plan->formattedPrice()}";
        }

        if ($avant['periodicite'] !== $plan->periodicite()) {
            $changements[] = "périodicité : {$avant['periodicite']} → {$plan->periodicite()}";
        }

        if ($avant['actif'] !== $plan->is_active) {
            $changements[] = $plan->is_active ? 'remise en vente' : 'retirée de la vente';
        }

        return "« {$plan->name} » — ".($changements === [] ? 'inclusions ou libellé' : implode(', ', $changements));
    }

    /**
     * Retirer une formule de la vente alors que des clients la paient encore
     * n'est pas une erreur, mais cela doit être dit : ces abonnements courent
     * jusqu'à leur terme et ne pourront pas être renouvelés à l'identique.
     */
    private function messageDeRetour(Plan $plan): string
    {
        if ($plan->is_active) {
            return "La formule « {$plan->name} » est enregistrée.";
        }

        $encours = Subscription::query()
            ->where('plan_id', $plan->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->count();

        if ($encours === 0) {
            return "La formule « {$plan->name} » est enregistrée et retirée de la vente.";
        }

        return "La formule « {$plan->name} » est retirée de la vente. {$encours} abonnement"
            .($encours > 1 ? 's en cours iront' : ' en cours ira')
            .' à son terme sans changement.';
    }
}
