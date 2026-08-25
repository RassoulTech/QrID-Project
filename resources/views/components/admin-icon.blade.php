{{--
  x-admin-icon — pictogramme d'une entrée de menu.

      <x-admin-icon name="clients" />

  LE NOM EST UN IDENTIFIANT, PAS UN LIBELLÉ. Il l'a été : le composant
  cherchait son tracé sous « Clients », « Paiements », « État système » —
  c'est-à-dire sous le texte affiché à l'écran.

  Le jour où ce texte est traduit, les onze recherches échouent ENSEMBLE et
  le composant retombe sur l'icône par défaut. Aucune erreur, aucun test
  rouge : onze entrées de menu portant le même dessin.

  Les clés sont donc des identifiants stables, en minuscules et sans accent,
  qui ne changeront dans aucune langue.

  Tracés en ligne, aucune police d'icônes : une police ajouterait 80 ko et une
  requête pour onze symboles, et laisserait des carrés vides le temps de son
  chargement.

  aria-hidden partout : l'intitulé est écrit à côté. Un lecteur d'écran qui
  annoncerait « image, Clients » avant « Clients » lirait tout deux fois.
--}}
@props(['name'])

@php
    $tracés = [
        'vue-ensemble' => 'M2 2h5v5H2zM9 2h5v3H9zM9 7h5v7H9zM2 9h5v5H2z',
        'statistiques' => 'M2 13h2V8H2zm4.5 0h2V3h-2zM11 13h2V6h-2z',
        'clients' => 'M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m6 6c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4',
        'profils' => 'M3 2h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1m1 3h4v4H4zm6 0h3v1h-3zm0 2h3v1h-3zM4 10h9v1H4z',
        'paiements' => 'M1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v1H1zm0 3h14v5a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm2 3h4v1H3z',
        'abonnements' => 'M8 1l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 10.8 4.2 12.8l.7-4.3-3.1-3 4.3-.6z',
        'modeles' => 'M2 3h5v9H2zm6 0h6v4H8zm0 5h6v4H8z',
        'parametres' => 'M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5m6-.3-1.2.3a5 5 0 0 0-.5-.9l.7-1a6.5 6.5 0 0 0-1.6-1.6l-1 .7a5 5 0 0 0-.9-.5L9.2 1H6.8l-.3 1.2a5 5 0 0 0-.9.5l-1-.7A6.5 6.5 0 0 0 3 3.6l.7 1a5 5 0 0 0-.5.9L2 5.2v2.4l1.2.3q.2.5.5.9l-.7 1a6.5 6.5 0 0 0 1.6 1.6l1-.7q.4.3.9.5l.3 1.2h2.4l.3-1.2q.5-.2.9-.5l1 .7a6.5 6.5 0 0 0 1.6-1.6l-.7-1q.3-.4.5-.9L14 7.6z',
        'journal' => 'M4 1h6l3 3v11H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1m1 5h6v1H5zm0 2h6v1H5zm0 2h4v1H5z',
        'etat-systeme' => 'M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1m0 2.2 1 4.3h2.6L9.6 9.2l.8 3.6L8 10.6l-2.4 2.2.8-3.6L4 7.5h2.6z',
        'retour' => 'M6.7 3.3 3 7l3.7 3.7 1-1L6 8h7V6H6l1.7-1.7z',
        'deconnexion' => 'M6 2H3a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3v-2H4V4h2zm4 2.3L8.6 5.7 9.9 7H6v2h3.9l-1.3 1.3L10 11.7 13.7 8z',
    ];

    $tracé = $tracés[$name] ?? $tracés['vue-ensemble'];
@endphp

<svg class="adm-nav__icone" width="16" height="16" viewBox="0 0 16 16"
     fill="currentColor" aria-hidden="true">
    <path d="{{ $tracé }}"/>
</svg>
