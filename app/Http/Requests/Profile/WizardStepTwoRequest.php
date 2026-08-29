<?php

namespace App\Http\Requests\Profile;

use App\Rules\TelephoneInternational;
use App\Services\ProfileWizardService;
use App\Support\IndicatifsPays;
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
            /*
             | LE LIEN DE LOCALISATION — facultatif, et validé comme une URL.
             |
             | « Thiès, Sénégal » lancé dans une recherche cartographique tombe
             | au centre d'une ville de 300 000 habitants. Pour un commerce,
             | c'est inutilisable : le visiteur voulait la boutique, il obtient
             | un quartier. Ce champ permet de coller le lien exact.
             */
            'maps_url' => ['nullable', 'url', 'max:500'],
            'phone_pays' => ['nullable', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:32', new TelephoneInternational],
            'whatsapp_pays' => ['nullable', 'string', 'size:2'],
            'whatsapp' => ['nullable', 'string', 'max:32', new TelephoneInternational('whatsapp_pays')],
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
            'phone.required' => __('validation.messages.carte.telephone_requis'),
            'public_email.email' => __('validation.messages.carte.email_invalide'),
            'website.url' => __('validation.messages.carte.site_invalide'),
            'socials.max' => __('validation.messages.carte.reseaux_max'),
            'socials.*.url.url' => __('validation.messages.carte.lien_invalide'),
            'socials.*.platform.required_with' => __('validation.messages.carte.reseau_requis'),
            'socials.*.platform.in' => __('validation.messages.carte.reseau_absent'),
        ];
    }

    public function attributes(): array
    {
        return [
            'socials.*.url' => __('validation.attributs.lien'),
            'socials.*.platform' => __('validation.attributs.reseau'),
        ];
    }

    /** Numéros au format canonique +221XXXXXXXXX pour le stockage. */
    public function canonicalPhones(): array
    {
        return [
            'maps_url' => $this->input('maps_url') ?: null,
            'phone' => IndicatifsPays::normaliser($this->input('phone_pays'), $this->input('phone')),
            'whatsapp' => $this->filled('whatsapp')
                ? IndicatifsPays::normaliser($this->input('whatsapp_pays') ?: $this->input('phone_pays'), $this->input('whatsapp'))
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
