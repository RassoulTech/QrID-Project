{{-- Gabarit commun des pages d'erreur, aux couleurs du produit.
     Aucune page d'erreur brute ne doit subsister dans l'application. --}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B3B2E">

    @include('layouts.partials.icons')
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss'])
</head>
<body>
    {{-- LE SÉLECTEUR EST SUR LES PAGES D'ERREUR AUSSI, ET CE N'EST PAS DU ZÈLE.

         Une 419 arrive au milieu d'un formulaire, une 429 après un envoi de
         trop, une 404 sur un lien partagé. Ce sont les pages où quelqu'un est
         déjà contrarié : lui retirer là, et là seulement, la commande qu'il a
         sur toutes les autres, c'est ajouter une petite panne à un incident.

         Il vit hors de la carte, en haut à droite, pour ne pas concurrencer
         le message d'erreur — qui reste la seule chose à lire. --}}
    <div class="erreur__prefs">
        <x-language-toggle />
        <x-theme-toggle />
    </div>

    <main class="auth-wrapper">
        <div class="container">
            <div class="auth-card mx-auto text-center">
                <x-brand size="lg" class="mb-4 justify-content-center" />

                <div class="card">
                    <div class="card-body p-4">
                        <p class="text-secondary small fw-semibold mb-2"
                           style="letter-spacing:.08em;">@yield('code')</p>

                        <h1 class="h4 fw-bold mb-2">@yield('title')</h1>

                        <p class="text-secondary mb-4">@yield('message')</p>

                        @hasSection('action')
                            @yield('action')
                        @else
                            <a href="{{ url('/') }}" class="btn btn-primary">{{ __('errors.retour_accueil') }}</a>
                        @endif
                    </div>
                </div>

                <p class="text-secondary small mt-4 mb-0">
                    {!! __('errors.aide.question') !!}
                    <a href="{{ config('registration.support_whatsapp') }}" target="_blank" rel="noopener">
                        {{ __('errors.aide.contact') }}
                    </a>
                </p>
            </div>
        </div>
    </main>
</body>
</html>
