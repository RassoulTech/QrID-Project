<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-admin-layout title="Clients" subtitle="…"> — espace administrateur.
 *
 * `subtitle` alimente la ligne grise sous le titre, présente sur les huit
 * écrans. Déclarée en propriété plutôt qu'en slot : c'est une phrase, pas un
 * fragment de balisage, et un slot inviterait à y glisser des boutons.
 */
class AdminLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $description = null,
    ) {}

    public function render(): View
    {
        return view('layouts.admin');
    }
}
