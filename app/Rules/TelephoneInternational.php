<?php

namespace App\Rules;

use App\Support\IndicatifsPays;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

/**
 * UN NUMÉRO VALIDE POUR LE PAYS CHOISI.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA RÈGLE DÉPEND D'UN AUTRE CHAMP, ET C'EST TOUT SON INTÉRÊT
 * ═══════════════════════════════════════════════════════════════════════
 * « Au moins six chiffres » accepte un numéro tronqué, et le client ne
 * l'apprend qu'au premier appel qui n'aboutit pas. Chaque pays a ses
 * longueurs réelles, et le Sénégal a en plus ses préfixes mobiles attribués.
 *
 * La règle lit donc le champ pays de la même requête. Sans ce lien, elle
 * validerait un numéro ivoirien contre des règles sénégalaises.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE SÉNÉGAL CONSERVE SA RÈGLE D'ORIGINE
 * ═══════════════════════════════════════════════════════════════════════
 * SenegalPhone vérifie 70, 75, 76, 77 et 78 — les préfixes réellement en
 * service. Un contrôle de longueur seul accepterait « 123456789 », qui
 * n'appellera jamais personne. On ne remplace pas un contrôle précis par un
 * contrôle générique sous prétexte d'uniformité.
 */
class TelephoneInternational implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    public function __construct(private string $champPays = 'phone_pays') {}

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $donnees = $this->validator->getData();
        $code = $donnees[$this->champPays] ?? IndicatifsPays::DEFAUT;

        if (! IndicatifsPays::existe($code)) {
            $fail(__('Choisissez un pays dans la liste.'));

            return;
        }

        if (IndicatifsPays::normaliser($code, $value) !== null) {
            return;
        }

        $catalogue = IndicatifsPays::catalogue()[mb_strtoupper((string) $code)];

        $fail(__('Numéro invalide pour :pays. Attendu : :n chiffres après :indicatif.', [
            'pays' => $catalogue[0],
            'n' => implode(' ou ', $catalogue[3]),
            'indicatif' => $catalogue[1],
        ]));
    }
}
