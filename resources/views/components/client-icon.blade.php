{{--
  x-client-icon — pictogramme du menu de l'espace client.

      <x-client-icon name="grille" />

  Même rôle que x-admin-icon, même classe de sortie (`adm-nav__icone`), pour
  que les deux menus s'alignent au pixel près. Deux fichiers plutôt qu'un
  seul : les jeux d'icônes ne se recouvrent pas, et les fusionner
  obligerait à charger onze tracés d'administration sur chaque page client.

  Ces tracés sont en trait (stroke), là où ceux de l'administration sont
  pleins. C'est la nuance qui reste entre les deux espaces, une fois la
  couleur de colonne devenue commune.
--}}
@props(['name'])

@php
    $tracés = [
        'grille' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',

        'courbe' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',

        'personne' => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',

        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><path d="M14 14h3v3h-3zM20 20h1M17 20v1"/>',

        'carte-bancaire' => '<rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/>',

        'aide' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.2 2.4c-.5.2-.7.6-.7 1.1v.5"/><path d="M12 17h.01"/>',

        'sortie' => '<path d="M15 17l5-5-5-5M20 12H9M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/>',
    ];

    // Un nom inconnu rend un point : une icône absente décalerait
    // l'alignement de toute la colonne.
    $tracé = $tracés[$name] ?? '<circle cx="12" cy="12" r="4"/>';
@endphp

<svg class="adm-nav__icone" width="17" height="17" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
     stroke-linejoin="round" aria-hidden="true" focusable="false">
    {!! $tracé !!}
</svg>
