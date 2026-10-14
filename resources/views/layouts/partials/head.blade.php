{{-- Partie <head> commune aux gabarits de l'application. Aucune ressource externe. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('layouts.partials.couleur-theme')

@include('layouts.partials.icons')

<title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

@isset($description)
    <meta name="description" content="{{ $description }}">
@endisset

@include('layouts.partials.police')

@vite(['resources/sass/app.scss', 'resources/js/app.js'])
