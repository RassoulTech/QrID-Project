<?php

namespace App\Http\Requests\Profile;

use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Choix de la formule et du moyen de paiement.
 *
 * Le formulaire ne transmet que des IDENTIFIANTS, jamais un montant : le prix
 * est relu en base au moment de créer le paiement. Accepter un montant venu du
 * navigateur reviendrait à laisser le client fixer son prix.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'plan' => [
                'required',
                'string',
                // La formule doit exister ET être commercialisée. L'essai
                // gratuit est exclu : il ne s'achète pas.
                Rule::exists('plans', 'slug')->where(
                    fn ($q) => $q->where('is_active', true)->where('price_fcfa', '>', 0)
                ),
            ],
            'method' => ['required', 'string', Rule::in(array_keys(Payment::METHODS))],
        ];
    }

    public function messages(): array
    {
        return [
            'plan.required' => 'Choisissez une formule.',
            'plan.exists' => 'Cette formule n\'est pas disponible.',
            'method.required' => 'Choisissez un moyen de paiement.',
            'method.in' => 'Ce moyen de paiement n\'est pas proposé.',
        ];
    }

    /** La formule choisie, relue en base. */
    public function plan(): Plan
    {
        return Plan::where('slug', $this->input('plan'))->firstOrFail();
    }
}
