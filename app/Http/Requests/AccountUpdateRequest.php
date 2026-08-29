<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mise à jour du COMPTE (users) : identifiants d'accès uniquement.
 * Aucune information professionnelle ici — elle appartient au profil.
 */
class AccountUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.messages.compte.nom_requis'),
            'email.required' => __('validation.messages.compte.email_requis'),
            'email.unique' => __('validation.messages.compte.email_pris'),
        ];
    }
}
