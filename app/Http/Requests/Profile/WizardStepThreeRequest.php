<?php

namespace App\Http\Requests\Profile;

use App\Enums\VarianteCarte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Étape 3 — Votre style.
 *
 * ZÉRO saisie : les deux champs sont pré-sélectionnés. L'utilisateur peut
 * cliquer « Terminer » sans rien toucher. C'est délibéré — cette étape est
 * là pour rassurer, pas pour faire travailler.
 *
 * `primary_color` NE PORTE PLUS UNE TEINTE LIBRE mais la couleur de fond
 * d'une des deux variantes de carte. C'est ici que la règle est tenue en
 * ENTRÉE : rien d'autre que ces deux valeurs ne peut être écrit en base, quoi
 * qu'on poste. En sortie, VarianteCarte::depuis() dégrade proprement toute
 * valeur héritée. Les deux bouts sont couverts, sans contrainte de base qui
 * bloquerait le jour où une troisième variante existera.
 */
class WizardStepThreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'template_id' => [
                'required',
                Rule::exists('templates', 'id')->where('is_active', true),
            ],
            'primary_color' => [
                'required',
                Rule::in(array_column(VarianteCarte::cases(), 'value')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => __('validation.messages.carte.modele_requis'),
            'template_id.exists' => __('validation.messages.carte.modele_absent'),
            'primary_color.required' => __('validation.messages.carte.variante_requise'),
            'primary_color.in' => __('validation.messages.carte.variante_absente'),
        ];
    }
}
