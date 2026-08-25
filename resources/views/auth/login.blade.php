{{-- ACTION PRINCIPALE : se connecter. --}}
<x-auth-layout
    :title="__('auth.login.titre')"
    :description="__('auth.login.description', ['marque' => config('app.name')])"
    aside-tone="dark"
    :aside-title="__('auth.login.aside_titre')"
    :aside-text="__('auth.login.aside_texte')"
    :aside-step="1">

    {{-- Visuel de CETTE page : carte professionnelle, pastille QR et carte de
         statistiques, sur fond vert foncé. --}}
    <x-slot:aside>
        <x-visual.chip icon="qr" :label="__('auth.login.visuel_qr')" position="haut-gauche" />
        <x-visual.profile-card />
        <x-visual.stat-card value="1.2k" :label="__('auth.login.visuel_vues')" position="bas-droite" />
    </x-slot:aside>

    <h1 class="auth__title">{{ __('auth.login.bienvenue', ['marque' => config('app.name')]) }}</h1>
    <p class="auth__lead">{{ __('auth.login.accroche') }}</p>

    <x-auth-tabs active="login" />

    {{-- AVANT le formulaire. Placé après, il tombait sous la ligne de
         flottaison sur un écran ordinaire : il fallait faire défiler pour
         découvrir le chemin le plus rapide. --}}
    <x-google-button />

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="auth-fields">
            <x-auth-field
                name="email"
                type="email"
                :label="__('auth.champs.email')"
                :placeholder="__('auth.champs.email_exemple')"
                autocomplete="username"
                inputmode="email"
                autofocus
            />

            <x-auth-password name="password" autocomplete="current-password" />

            {{--
              SORTIE DE SECOURS, affichée dès le premier échec.

              Sans elle, l'utilisateur bloqué n'avait sous les yeux qu'un
              message d'erreur et un bouton « Créer un compte ». Il recréait
              donc un compte qu'il possédait déjà. La réinitialisation existait,
              mais reléguée en petit à côté de « Se souvenir de moi ».

              Aucune fuite : ce bloc apparaît à CHAQUE échec, que le compte
              existe ou non. Il ne dit rien de l'état de l'adresse.
            --}}
            @error('email')
                <div class="mail-spam">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
                    </svg>
                    {{-- Le lien est DANS la traduction : l'anglais ne place pas
                         la proposition relative au même endroit de la phrase.
                         Découper en trois morceaux imposerait l'ordre français
                         à toutes les langues. --}}
                    <span>{!! __('auth.login.secours', ['lien' => route('password.request')]) !!}</span>
                </div>
            @enderror

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div class="form-check m-0">
                    {{-- Champ caché : une case décochée n'envoie rien, le serveur
                         ne pourrait pas distinguer « non » de « absent ». --}}
                    <input type="hidden" name="remember" value="0">
                    <input class="form-check-input" type="checkbox" name="remember"
                           id="remember" value="1" @checked(old('remember'))>
                    <label class="form-check-label f__hint" for="remember">{{ __('auth.champs.se_souvenir') }}</label>
                </div>

                <a href="{{ route('password.request') }}" class="f__hint">{!! __('auth.liens.mot_de_passe_oublie') !!}</a>
            </div>

            <x-button :block="true">{{ __('auth.login.bouton') }}</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        {!! __('auth.liens.pas_de_compte') !!}
        <a href="{{ route('register') }}">{{ __('auth.liens.creer_compte') }}</a>
    </p>
</x-auth-layout>
