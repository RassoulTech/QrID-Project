{{-- Partie <head> commune à tous les gabarits. Aucune ressource externe. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0B3B2E">

<title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

@isset($description)
    <meta name="description" content="{{ $description }}">
@endisset

@vite(['resources/sass/app.scss', 'resources/js/app.js'])
