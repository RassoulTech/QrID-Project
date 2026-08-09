<?php

namespace App\Http\Requests\Admin;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création et modification d'une formule tarifaire.
 *
 * LE SLUG EST IMMUABLE APRÈS CRÉATION, et ce n'est pas une commodité : il est
 * écrit dans `payments.payload` à chaque encaissement, et CheckoutService s'en
 * sert pour retrouver la formule au retour de l'opérateur. Le renommer
 * casserait rétroactivement tous les paiements en cours de route.
 *
 * LE PRIX EST UN ENTIER EN FCFA. Le franc CFA n'a pas de subdivision en
 * circulation : accepter des décimales inviterait des centimes qui
 * n'existent pas, et un `float` sur de l'argent finit toujours par produire
 * un écart d'un franc que personne n'explique.
 */
class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:120'],

            // Absent à la modification : la règle ci-dessous ne s'applique
            // qu'à la création, où le slug est encore libre.
            'slug' => $plan
                ? ['nullable']
                : ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('plans', 'slug')],

            'price_fcfa' => ['required', 'integer', 'min:0', 'max:10000000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],

            // Les inclusions arrivent en champs répétés. Les lignes vides sont
            // filtrées avant validation, pas rejetées : ajouter une ligne puis
            // se raviser est un geste normal, pas une erreur de saisie.
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['required', 'string', 'max:160'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'features' => collect($this->input('features', []))
                ->map(fn ($ligne) => is_string($ligne) ? trim($ligne) : $ligne)
                ->filter(fn ($ligne) => $ligne !== '' && $ligne !== null)
                ->values()
                ->all(),

            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom du plan',
            'slug' => 'identifiant technique',
            'price_fcfa' => 'prix',
            'duration_days' => 'périodicité',
            'features.*' => 'inclusion',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Cet identifiant technique est déjà pris par une autre formule.',
            'slug.alpha_dash' => 'L\'identifiant technique n\'accepte que lettres, chiffres, tirets et tirets bas.',
        ];
    }

    /** @return array<string, mixed> */
    public function donnees(): array
    {
        $valide = $this->validated();

        $donnees = [
            'name' => $valide['name'],
            'price_fcfa' => (int) $valide['price_fcfa'],
            'duration_days' => (int) $valide['duration_days'],
            'features' => $valide['features'] ?? [],
            'is_active' => (bool) ($valide['is_active'] ?? false),
        ];

        if (! $this->route('plan')) {
            $donnees['slug'] = $valide['slug'];
        }

        return $donnees;
    }

    /** Le catalogue proposé par la liste déroulante de l'écran. */
    public function periodicitesConnues(): array
    {
        return array_keys(Plan::PERIODICITES);
    }
}
