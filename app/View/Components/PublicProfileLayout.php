<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-public-profile-layout :title="..."> — page de profil publique (scan QR).
 * Le gabarit le plus léger : ni navbar, ni footer, aucun JavaScript.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * C'EST UN COMPOSANT DE CLASSE, ET CELA A UNE CONSÉQUENCE
 * ═══════════════════════════════════════════════════════════════════════
 * Seuls les paramètres DÉCLARÉS DANS CE CONSTRUCTEUR atteignent la vue. Tout
 * ce qu'on passe d'autre est rangé dans $attributes, où personne ne le
 * cherche — sans erreur, sans avertissement.
 *
 * L'image de partage a manqué pour cette raison exacte : la balise og:image
 * était écrite dans le gabarit, la valeur passée depuis la vue, et rien ne
 * s'affichait. Ajouter @props dans le fichier Blade n'y changeait rien : c'est
 * une syntaxe de composant ANONYME, ignorée dès qu'une classe existe.
 *
 * Toute nouvelle donnée destinée à ce gabarit passe donc par ici.
 */
class PublicProfileLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,

        /**
         * L'image affichée par les messageries au partage du lien.
         *
         * Null quand elle n'a pas pu être produite : les balises disparaissent
         * alors, plutôt que de pointer vers un fichier absent — un og:image
         * cassé donne un aperçu vide, pire que pas d'aperçu du tout.
         */
        public ?string $apercuUrl = null,
    ) {}

    public function render(): View
    {
        return view('layouts.public-profile');
    }
}
