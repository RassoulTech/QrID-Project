{{--
  layouts/public-profile — page de profil publique (arrivée par scan de QR Code).

  LE GABARIT LE PLUS LÉGER DU PRODUIT. Aucune navbar, aucun footer de site,
  aucune ressource externe : le visiteur arrive souvent en 3G, depuis un
  smartphone, et juge le professionnel en trois secondes.

  <x-public-profile-layout :title="$profil->nom" :description="$profil->accroche">
      ...
  </x-public-profile-layout>

  LES PARAMÈTRES SONT DÉCLARÉS DANS LA CLASSE, pas ici : ce gabarit est servi
  par App\View\Components\PublicProfileLayout. Une valeur passée depuis la vue
  sans figurer dans SON CONSTRUCTEUR n'atteint jamais cette page — elle est
  rangée dans $attributes, sans erreur ni avertissement. C'est ainsi que
  l'image de partage a manqué en silence.

  @props n'y changerait rien : c'est une syntaxe de composant ANONYME, ignorée
  dès qu'une classe existe.
--}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B5D3B">

    @include('layouts.partials.icons')

    <title>{{ $title ?? config('app.name') }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    {{--
      PARTAGE SUR LES MESSAGERIES — c'est le geste central du produit.

      Le client colle ce lien dans WhatsApp. Sans og:image, WhatsApp rend un
      aperçu minuscule : une ligne de titre grise et rien d'autre. Avec, il
      rend une grande vignette qu'on remarque dans une conversation.

      L'écart n'est pas cosmétique : c'est la différence entre un lien qu'on
      ouvre et un lien qu'on fait défiler.

      L'ADRESSE DE L'IMAGE EST ABSOLUE, obligatoirement. Les robots des
      messageries ne résolvent aucun chemin relatif : une URL relative donne
      exactement le même résultat qu'une balise absente, sans que rien ne le
      signale.

      LES DIMENSIONS SONT DÉCLARÉES pour que l'aperçu s'affiche dès le premier
      partage. Sans elles, WhatsApp doit télécharger l'image avant de savoir
      quelle place lui réserver — et affiche souvent le lien sans vignette en
      attendant.
    --}}
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="fr_SN">

    @if ($apercuUrl)
        <meta property="og:image" content="{{ $apercuUrl }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $title ?? config('app.name') }}">

        {{-- « summary_large_image » et non « summary » : le second rend une
             vignette carrée de la taille d'un timbre. --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $apercuUrl }}">
    @endif

    @vite(['resources/sass/app.scss'])
    {{-- Pas de JavaScript ici : rien sur cette page n'en a besoin. --}}
</head>
<body>
    <main class="public-profile">
        <div class="container px-3">
            <div class="public-profile__card">
                {{ $slot }}
            </div>

            <p class="text-center text-secondary small mt-4 mb-0">
                Propulsé par <a href="{{ url('/') }}">{{ config('app.name') }}</a>
            </p>
        </div>
    </main>
</body>
</html>
