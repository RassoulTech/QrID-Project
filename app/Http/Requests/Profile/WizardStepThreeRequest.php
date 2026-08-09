<?php

namespace App\Http\Requests\Profile;

use App\Http\Controllers\Profile\ProfileWizardController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Étape 3 — Votre style.
 *
 * ZÉRO saisie : les deux champs sont pré-sélectionnés. L'utilisateur peut
 * cliquer « Terminer » sans rien toucher. C'est délibéré — cette étape est
 * là pour rassurer, pas pour faire travailler.
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
                Rule::in(array_keys(ProfileWizardController::COLORS)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'Choisissez un modèle.',
            'template_id.exists' => 'Ce modèle n\'est plus disponible.',
            'primary_color.required' => 'Choisissez une couleur.',
            'primary_color.in' => 'Cette couleur n\'est pas proposée.',
        ];
    }
}
