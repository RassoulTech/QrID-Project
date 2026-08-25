{{-- layouts/public — structure de la landing QrID. --}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B3B2E">

    @include('layouts.partials.icons')

    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? '' }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? '' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    {{-- og:locale était figé sur « fr_SN ». Une page partagée en anglais
         s'annonçait donc en français aux robots des messageries. --}}
    <meta property="og:locale" content="{{ App\Support\Langue::etiquetteOuverte() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Polices servies en local (voir @font-face dans _base.scss).
         Aucun CDN de police. --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <a href="#contenu" class="skip-link">{{ __('navigation.aller_au_contenu') }}</a>

    <x-nav-public />

    <main id="contenu">
        {{ $slot }}
    </main>

    <x-footer-public />

    {{-- Hors du <main> : c'est une aide permanente, pas un élément du
         contenu. Un lecteur d'écran qui parcourt la page ne doit pas la
         rencontrer au milieu d'un paragraphe.

         Aucun message n'est passé : le composant le déduit de la page. --}}
    <x-whatsapp-fab />
</body>
</html>
