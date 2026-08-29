<?php

namespace App\Http\Requests\Profile;

use App\Rules\TelephoneInternational;
use App\Support\IndicatifsPays;
use Illuminate\Foundation\Http\FormRequest;

/**
 * L'ADRESSE OÙ EXPÉDIER LA CARTE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUI EST EXIGÉ, ET CE QUI NE L'EST PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Destinataire, téléphone, adresse et ville sont obligatoires : sans l'un des
 * quatre, le colis ne part pas. La région et les indications sont
 * facultatives — au Sénégal, « Sacré-Cœur 3, en face de la pharmacie » vaut
 * mieux qu'un code postal que personne n'utilise.
 *
 * LE TÉLÉPHONE EST OBLIGATOIRE et distinct de celui du compte : c'est le
 * livreur qui appelle, souvent à quelqu'un d'autre que le titulaire. Il passe
 * par la même règle internationale que le reste du produit.
 */
class AdresseLivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone_pays' => ['nullable', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:32', new TelephoneInternational],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'recipient_name' => __('validation.attributs.destinataire'),
            'phone' => __('validation.attributs.telephone'),
            'address_line' => __('validation.attributs.adresse'),
            'city' => __('validation.attributs.ville'),
        ];
    }

    /** Les données prêtes pour la commande, téléphone normalisé. */
    public function livraison(): array
    {
        return [
            'recipient_name' => $this->string('recipient_name')->trim()->value(),
            'phone' => IndicatifsPays::normaliser($this->input('phone_pays'), $this->input('phone')),
            'address_line' => $this->string('address_line')->trim()->value(),
            'city' => $this->string('city')->trim()->value(),
            'region' => $this->input('region') ? $this->string('region')->trim()->value() : null,
            'delivery_notes' => $this->input('delivery_notes') ? $this->string('delivery_notes')->trim()->value() : null,
        ];
    }
}
