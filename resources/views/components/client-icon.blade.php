{{--
  x-client-icon — pictogramme d'une destination de l'espace client.

      <x-client-icon name="grille" />

  UN ALIAS DE x-icone. Les identifiants (« grille », « qr »…) sont ceux
  qu'emploient la navigation et ses tests ; le dessin vient du jeu unique du
  produit. Deux jeux d'icônes — pleines côté administration, en trait côté
  client — se remarquaient avant qu'on lise un mot ; il n'en reste qu'un.
--}}
@props(['name'])

@php
    $noms = [
        'grille' => 'layout-grid',
        'courbe' => 'bar-chart',
        'personne' => 'id-card',
        'qr' => 'qr-code',
        'carte-bancaire' => 'credit-card',
        'aide' => 'help-circle',
        'sortie' => 'log-out',
        'engrenage' => 'user',
        'compte' => 'user',
        'cloche' => 'bell',
        'recherche' => 'search',
    ];
@endphp

<x-icone :name="$noms[$name] ?? $name" {{ $attributes->merge(['class' => 'adm-nav__icone']) }} />
