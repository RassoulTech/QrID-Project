<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de base des actions sensibles : le MOTIF est obligatoire.
 *
 * Une sanction sans motif est inexploitable trois mois plus tard, quand le
 * client réclame et que personne ne se souvient. La contrainte est portée par
 * la validation, pas par l'écran : un formulaire contourné ne suffit pas à
 * écrire une ligne au journal sans justification.
 *
 * LE PLANCHER DE 10 CARACTÈRES est délibéré. Sans lui, le champ se remplit de
 * « ok », « test », « rien » — et le journal redevient illisible tout en
 * paraissant rempli. Dix caractères n'obligent pas à écrire un rapport, ils
 * obligent à écrire une phrase.
 */
class MotifRequest extends FormRequest
{
    /** Le middleware `admin` a déjà tranché : y revenir ici dupliquerait la règle. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['motif' => 'motif'];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Un motif est obligatoire pour cette action.',
            'motif.min' => 'Le motif doit être compréhensible par quelqu\'un qui relira le journal dans six mois — 10 caractères au minimum.',
        ];
    }

    public function motif(): string
    {
        return trim($this->validated()['motif']);
    }
}
