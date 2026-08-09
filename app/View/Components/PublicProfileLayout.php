<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-public-profile-layout :title="..."> — page de profil publique (scan QR).
 * Le gabarit le plus léger : ni navbar, ni footer, aucun JavaScript.
 */
class PublicProfileLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
    ) {}

    public function render(): View
    {
        return view('layouts.public-profile');
    }
}
