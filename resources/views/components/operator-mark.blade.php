{{--
  x-operator-mark — la marque visuelle d'un opérateur de paiement.

      <x-operator-mark methode="wave" />

  ═══════════════════════════════════════════════════════════════════════
  POURQUOI CE COMPOSANT NE DESSINE PAS LES LOGOS OFFICIELS
  ═══════════════════════════════════════════════════════════════════════
  Wave, Orange Money et Free Money sont des marques déposées. Redessiner leurs
  logos de mémoire produirait des formes APPROXIMATIVES — un « W » à peu près
  juste, un cercle Orange à peu près rond. Sur un écran de paiement, c'est le
  pire endroit possible pour un à-peu-près : le client s'apprête à envoyer de
  l'argent, et un logo qui ne ressemble pas tout à fait à celui qu'il connaît
  est exactement ce qui le fait renoncer.

  Le composant affiche donc, par défaut, une PASTILLE aux couleurs officielles
  de l'opérateur avec son nom en toutes lettres. C'est exact, reconnaissable,
  et cela n'usurpe rien.

  ═══════════════════════════════════════════════════════════════════════
  DÉPOSER LES VRAIS LOGOS SUFFIT À LES ACTIVER
  ═══════════════════════════════════════════════════════════════════════
  Si un fichier existe dans public/images/operateurs/, il est servi à la place
  de la pastille — sans toucher au code :

      public/images/operateurs/wave.svg
      public/images/operateurs/orange_money.svg
      public/images/operateurs/free_money.svg

  Les .png fonctionnent aussi. Ces fichiers se récupèrent auprès de chaque
  opérateur, dans son dossier de presse ou son kit partenaire — c'est-à-dire
  auprès de la seule source qui fasse foi.
--}}
@props(['methode'])

@php
    /*
     | Couleurs officielles des trois opérateurs. Elles ne sont pas
     | approximatives : ce sont celles de leurs chartes, et c'est ce qui rend
     | la pastille reconnaissable même sans le logo.
     */
    $marques = [
        'wave'         => ['fond' => '#1DC8FF', 'encre' => '#0A2540', 'court' => 'W'],
        'orange_money' => ['fond' => '#FF7900', 'encre' => '#FFFFFF', 'court' => 'OM'],
        'free_money'   => ['fond' => '#CD0A2C', 'encre' => '#FFFFFF', 'court' => 'FM'],
    ];

    $marque = $marques[$methode] ?? ['fond' => '#0B3B2E', 'encre' => '#FFFFFF', 'court' => '?'];

    // Le premier fichier trouvé l'emporte. public_path() et non asset() : on
    // teste une PRÉSENCE sur le disque, pas une adresse.
    $fichier = collect(['svg', 'png', 'webp'])
        ->map(fn (string $ext) => 'images/operateurs/'.$methode.'.'.$ext)
        ->first(fn (string $chemin) => is_file(public_path($chemin)));
@endphp

@if ($fichier)
    {{-- alt vide : le nom de l'opérateur est écrit juste à côté, et le
         répéter ferait entendre deux fois la même chose à un lecteur d'écran. --}}
    <img src="{{ asset($fichier) }}" alt="" class="pay-option__logo" width="40" height="40">
@else
    <span class="pay-option__pastille" aria-hidden="true"
          style="background:{{ $marque['fond'] }};color:{{ $marque['encre'] }}">
        {{ $marque['court'] }}
    </span>
@endif
