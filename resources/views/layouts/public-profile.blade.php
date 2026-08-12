{{--
  layouts/public-profile — page de profil publique (arrivée par scan de QR Code).

  LE GABARIT LE PLUS LÉGER DU PRODUIT. Aucune navbar, aucun footer de site,
  aucune ressource externe : le visiteur arrive souvent en 3G, depuis un
  smartphone, et juge le professionnel en trois secondes.

  <x-public-profile-layout :title="$profil->nom" :description="$profil->accroche">
      ...
  </x-public-profile-layout>
--}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B5D3B">

    @include('layouts.partials.icons')

    <title>{{ $title ?? config('app.name') }}</title>

    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset

    {{-- Partage sur les réseaux et messageries (WhatsApp notamment) --}}
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    @isset($description)
        <meta property="og:description" content="{{ $description }}">
    @endisset
    <meta property="og:url" content="{{ url()->current() }}">

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
