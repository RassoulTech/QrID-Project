{{--
  x-admin-icon — pictogramme d'une rubrique de l'administration.

      <x-admin-icon name="clients" />

  LE NOM EST UN IDENTIFIANT, PAS UN LIBELLÉ. Traduire l'intitulé ne doit
  jamais faire retomber les onze icônes sur celle par défaut — d'où des clés
  stables, sans accent, qui ne changent dans aucune langue.

  UN ALIAS DE x-icone : le dessin vient du jeu unique du produit (trait,
  grille 24). Les tracés pleins d'autrefois formaient une seconde famille.
--}}
@props(['name'])

@php
    $noms = [
        'vue-ensemble' => 'layout-grid',
        'statistiques' => 'bar-chart',
        'clients' => 'users',
        'profils' => 'id-card',
        'paiements' => 'credit-card',
        'abonnements' => 'calendar',
        'cartes' => 'package',
        'modeles' => 'layers',
        'parametres' => 'sliders',
        'journal' => 'history',
        'etat-systeme' => 'server',
        'retour' => 'arrow-left',
        'deconnexion' => 'log-out',
    ];
@endphp

<x-icone :name="$noms[$name] ?? 'layout-grid'" {{ $attributes->merge(['class' => 'adm-nav__icone']) }} />
