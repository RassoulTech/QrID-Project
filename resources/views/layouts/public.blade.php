{{--
  layouts/public — la vitrine (accueil, pages légales, design-system).

      <x-public-layout title="…" description="…">…sections…</x-public-layout>

  La barre (x-nav-public) et le pied (x-footer-public) sont posés ici ; une
  page n'a qu'à composer ses sections dans le slot. L'aide WhatsApp est dans
  le pied de page — plus aucune bulle flottante ne recouvre le contenu.
--}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.couleur-theme')

    @include('layouts.partials.icons')

    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? '' }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? '' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    {{-- La langue de la page, pas une valeur figée : une page partagée en
         anglais s'annonce en anglais aux robots des messageries. --}}
    <meta property="og:locale" content="{{ App\Support\Langue::etiquetteOuverte() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- La police est auto-hébergée et préchargée. Aucun CDN. --}}
    @include('layouts.partials.police')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <a href="#contenu" class="skip-link">{{ __('navigation.aller_au_contenu') }}</a>

    <x-nav-public />

    <main id="contenu">
        {{ $slot }}
    </main>

    <x-footer-public />
</body>
</html>
