<?php

namespace App\Http\Requests\Auth;

use App\Rules\SenegalPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * On nettoie l'e-mail (comparaison stable) mais on NE réécrit PAS `phone` :
     * la saisie brute doit rester telle quelle pour old() en cas d'erreur sur
     * un autre champ. La tolérance de format est portée par la règle SenegalPhone,
     * et la canonisation se fait à la persistance via canonicalPhone().
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:32', new SenegalPhone],

            // « confirmed » exige un champ password_confirmation. Le formulaire
            // le porte à nouveau (maquette d'inscription) : une faute de frappe
            // sur un mot de passe qu'on ne relit pas coûte un cycle complet de
            // réinitialisation.
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ];
    }

    /**
     * Numéro canonique +221XXXXXXXXX pour le stockage.
     */
    public function canonicalPhone(): string
    {
        return SenegalPhone::normalize($this->input('phone'));
    }
}
