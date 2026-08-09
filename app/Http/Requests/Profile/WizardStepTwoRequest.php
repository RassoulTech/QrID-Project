<?php

namespace App\Http\Requests\Profile;

use App\Rules\SenegalPhone;
use App\Services\ProfileWizardService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Étape 2 — Comment vous joindre.
 *
 * UN SEUL champ obligatoire : le téléphone. C'est le canal par lequel on
 * rappelle quelqu'un au Sénégal ; tout le reste est un bonus.
 */
class WizardStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Une adresse saisie sans protocole reste une adresse valide. */
    protected function prepareForValidation(): void
    {
        if ($site = trim((string) $this->input('website'))) {
            $this->merge(['website' => $this->withScheme($site)]);
        }

        $socials = [];

        foreach ((array) $this->input('socials', []) as $row) {
            $url = trim((string) ($row['url'] ?? ''));

            $socials[] = [
                'platform' => $row['platform'] ?? '',
                'url' => $url === '' ? '' : $this->withScheme($url),
            ];
        }

        $this->merge(['socials' => $socials]);
    }

    private function withScheme(string $url): string
    {
        return preg_match('#^https?://#i', $url) ? $url : 'https://'.$url;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32', new SenegalPhone],
            'whatsapp' => ['nullable', 'string', 'max:32', new SenegalPhone],
            'public_email' => ['nullable', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'address' => ['nullable', 'string', 'max:160'],

            // Six réseaux au maximum : au-delà, le profil devient un annuaire.
            'socials' => ['nullable', 'array', 'max:6'],
            'socials.*.platform' => [
                'nullable',
                Rule::in(array_keys(ProfileWizardService::PLATFORMS)),
                // Une ligne dont l'adresse est remplie doit désigner un réseau.
                'required_with:socials.*.url',
            ],
            'socials.*.url' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Votre téléphone est obligatoire.',
            'public_email.email' => 'Cette adresse e-mail n\'est pas valide.',
            'website.url' => 'Cette adresse de site n\'est pas valide.',
            'socials.max' => 'Six réseaux sociaux au maximum.',
            'socials.*.url.url' => 'Ce lien n\'est pas une adresse valide.',
            'socials.*.platform.required_with' => 'Choisissez le réseau correspondant à ce lien.',
            'socials.*.platform.in' => 'Ce réseau n\'est pas proposé.',
        ];
    }

    public function attributes(): array
    {
        return [
            'socials.*.url' => 'lien',
            'socials.*.platform' => 'réseau',
        ];
    }

    /** Numéros au format canonique +221XXXXXXXXX pour le stockage. */
    public function canonicalPhones(): array
    {
        return [
            'phone' => SenegalPhone::normalize($this->input('phone')),
            'whatsapp' => $this->filled('whatsapp')
                ? SenegalPhone::normalize($this->input('whatsapp'))
                : null,
        ];
    }

    /**
     * Réseaux réellement renseignés.
     *
     * Une ligne ajoutée puis laissée vide n'est pas une erreur : on l'ignore
     * silencieusement plutôt que de bloquer l'utilisateur sur un champ qu'il
     * n'a jamais voulu remplir.
     */
    public function cleanSocials(): array
    {
        return array_values(array_filter(
            $this->validated()['socials'] ?? [],
            fn (array $row) => ! empty($row['url']) && ! empty($row['platform'])
        ));
    }
}
