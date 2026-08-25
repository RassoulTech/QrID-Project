{{--
  layouts/auth — coquille des écrans d'authentification.

  Conteneur blanc arrondi sur fond gris clair, deux colonnes. La colonne de
  droite n'existe qu'à partir de 992px : display:none en dessous, elle sort
  de la grille et ne laisse aucun espace vide.

  Mobile first : la mise en page par défaut est celle du téléphone.

  LE VISUEL DE DROITE VIENT DE LA PAGE, par le slot « aside ». Il n'est plus
  écrit ici : chaque maquette a sa propre composition.
--}}
<!DOCTYPE html>
@include('layouts.partials.html-open')
<head>
    @include('layouts.partials.head')
</head>
<body>
<main class="auth">
    <div class="auth__shell">

        {{-- ==================== COLONNE GAUCHE ==================== --}}
        <div class="auth__panel">

            {{-- Marque à gauche, bascule de thème à droite : un visiteur doit
                 pouvoir passer en sombre AVANT même d'avoir un compte. --}}
            <div class="auth__entete">
                <x-brand size="sm" />
                <x-language-toggle />
                <x-theme-toggle />
            </div>

            <div class="auth__body">
                {{-- Messages de session UNIQUEMENT. Les erreurs de validation ne
                     sont jamais regroupées ici : chacune s'affiche sous le champ
                     concerné (voir x-auth-field et x-auth-password). --}}
                <x-flash />

                {{ $slot }}
            </div>

            <p class="auth__legal">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                <a href="{{ route('legal.conditions') }}">{{ __('navigation.pied.conditions') }}</a>
                <a href="{{ route('legal.confidentialite') }}">{{ __('navigation.pied.confidentialite') }}</a>
                <a href="{{ route('legal.mentions') }}">{{ __('navigation.pied.mentions') }}</a>
            </p>
        </div>

        {{-- ==================== COLONNE DROITE ====================
             Décorative : aucune donnée n'est lue en base pour l'afficher, sur
             aucune page. Retirée du parcours des lecteurs d'écran, le
             formulaire porte tout le sens de l'écran. --}}
        <aside class="auth__aside auth__aside--{{ $tone() }}" aria-hidden="true">
            <div class="auth-aside">

                <span class="auth-aside__mark">
                    <svg width="17" height="17" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4"/>
                    </svg>
                </span>

                <div class="auth-aside__stage">
                    <div class="auth-aside__cards">
                        {{ $aside ?? '' }}
                    </div>
                </div>

                @if ($asideTitle)
                    <h2 class="auth-aside__title">{{ $asideTitle }}</h2>
                @endif

                @if ($asideText)
                    <p class="auth-aside__text">{{ $asideText }}</p>
                @endif

                <div class="auth-aside__dots">
                    @foreach ($dots() as $allume)
                        <span @class(['auth-aside__dot', 'is-on' => $allume])></span>
                    @endforeach
                </div>
            </div>
        </aside>

    </div>
</main>

{{-- L'AIDE COMPTE ICI PLUS QUE PARTOUT AILLEURS.
     Quelqu'un qui ne peut pas se connecter n'a aucun autre canal pour le
     dire : ni tableau de bord, ni formulaire de contact derrière une session.
     Le message pré-rempli nomme l'écran — « je n'arrive pas à réinitialiser
     mon mot de passe » — donc la demande arrive déjà qualifiée. --}}
<x-whatsapp-fab />
</body>
</html>
