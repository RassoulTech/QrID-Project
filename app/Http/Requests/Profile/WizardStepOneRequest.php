<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Étape 1 — Qui êtes-vous.
 *
 * TROIS champs obligatoires : prénom, nom, fonction. Ce sont les seuls sans
 * lesquels un profil professionnel ne veut rien dire. Tout le reste attend.
 */
class WizardStepOneRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'accès est déjà borné par le middleware `auth` : un utilisateur
        // authentifié ne crée jamais que son propre profil.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'job_title' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:100'],

            /*
             | LA COUVERTURE EST FACULTATIVE, et son plafond est plus haut.
             |
             | Une bannière est un rectangle large : la même limite que le
             | portrait ferait refuser des photos de paysage tout à fait
             | ordinaires sorties d'un téléphone. Le service la ramène de
             | toute façon à 840px de large avant de la conserver.
             */
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('validation.messages.carte.prenom_requis'),
            'last_name.required' => __('validation.messages.carte.nom_requis'),
            'job_title.required' => __('validation.messages.carte.fonction_requise'),
            'cover.image' => __('validation.messages.carte.image_invalide'),
            'cover.mimes' => __('validation.messages.carte.image_formats'),
            'cover.max' => __('validation.messages.carte.image_trop_lourde'),
            'cover.uploaded' => __('validation.messages.carte.image_envoi_echoue'),
        ];
    }
}
