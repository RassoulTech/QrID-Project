<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-auth-layout> — coquille commune aux écrans d'authentification.
 *
 * Colonne gauche : logo, formulaire, mentions légales.
 * Colonne droite : le visuel PROPRE À CHAQUE PAGE, absent sous 992px.
 *
 *     <x-auth-layout title="Connexion" aside-tone="dark"
 *                    aside-title="…" aside-text="…" :aside-step="1">
 *         <x-slot:aside>… le visuel de CETTE page …</x-slot:aside>
 *         … le formulaire …
 *     </x-auth-layout>
 *
 * Le visuel passe par un SLOT, et c'est le point important : il était jusqu'ici
 * écrit en dur dans le gabarit, donc identique sur les sept pages. Chaque
 * maquette montre pourtant sa propre composition — cartes, pastilles,
 * illustrations diffèrent d'un écran à l'autre.
 *
 * Le sélecteur Connexion / Créer un compte n'est pas posé ici non plus : les
 * écrans qui en ont besoin placent <x-auth-tabs> eux-mêmes, sa position dans
 * le formulaire n'étant pas la même partout.
 */
class AuthLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,

        /** 'dark' (vert foncé) ou 'light' (fond clair) selon la maquette. */
        public string $asideTone = 'dark',

        public ?string $asideTitle = null,
        public ?string $asideText = null,

        /** Position sur l'indicateur de progression, de 1 à 4. */
        public int $asideStep = 1,
    ) {}

    /**
     * Les quatre traits de l'indicateur, allumés jusqu'à l'étape courante.
     *
     * @return array<int, bool>
     */
    public function dots(): array
    {
        $etape = max(1, min(4, $this->asideStep));

        return array_map(fn (int $i) => $i <= $etape, [1, 2, 3, 4]);
    }

    /** Le ton n'accepte que deux valeurs : une faute de frappe ne doit pas passer. */
    public function tone(): string
    {
        return $this->asideTone === 'light' ? 'light' : 'dark';
    }

    public function render(): View
    {
        return view('layouts.auth');
    }
}
