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
            'first_name.required' => 'Votre prénom est obligatoire.',
            'last_name.required' => 'Votre nom est obligatoire.',
            'job_title.required' => 'Votre fonction est obligatoire.',
            'cover.image' => 'Ce fichier n\'est pas une image.',
            'cover.mimes' => 'Formats acceptés : JPG, PNG ou WEBP.',
            'cover.max' => 'Votre image dépasse 2 Mo. Choisissez une image plus légère.',
            'cover.uploaded' => 'L\'envoi a échoué : la image dépasse 2 Mo ou la connexion s\'est interrompue.',
        ];
    }
}
