{{-- Gabarit commun des pages d'erreur, aux couleurs du produit.
     Aucune page d'erreur brute ne doit subsister dans l'application. --}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B5D3B">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss'])
</head>
<body>
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
                            <a href="{{ url('/') }}" class="btn btn-primary">Retour à l'accueil</a>
                        @endif
                    </div>
                </div>

                <p class="text-secondary small mt-4 mb-0">
                    Besoin d'aide&nbsp;?
                    <a href="{{ config('registration.support_whatsapp') }}" target="_blank" rel="noopener">
                        Contactez le support
                    </a>
                </p>
            </div>
        </div>
    </main>
</body>
</html>
