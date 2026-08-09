<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\SubscriptionExtensionService;

/**
 * Prolongation manuelle : un nombre de jours ET un motif.
 *
 * Le plafond de 365 jours n'est pas une précaution de saisie : au-delà, ce
 * n'est plus un geste commercial mais un abonnement offert, qui doit passer
 * par une formule et laisser une trace comptable.
 */
class ExtendSubscriptionRequest extends MotifRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'jours' => [
                'required',
                'integer',
                'min:'.SubscriptionExtensionService::JOURS_MIN,
                'max:'.SubscriptionExtensionService::JOURS_MAX,
            ],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), ['jours' => 'nombre de jours']);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'jours.max' => 'Au-delà de '.SubscriptionExtensionService::JOURS_MAX
                .' jours, passez par une formule plutôt que par une prolongation.',
        ]);
    }

    public function jours(): int
    {
        return (int) $this->validated()['jours'];
    }
}
