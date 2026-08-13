{{-- ACTION PRINCIPALE : se connecter. --}}
<x-auth-layout
    title="Connexion"
    description="Connectez-vous à votre espace {{ config('app.name') }}."
    aside-tone="dark"
    aside-title="Votre identité pro, partout avec vous."
    aside-text="Un lien, un QR code, et vos contacts ont tout : coordonnées, réseaux, présentation. Plus jamais de carte oubliée au bureau."
    :aside-step="1">

    {{-- Visuel de CETTE page : carte professionnelle, pastille QR et carte de
         statistiques, sur fond vert foncé. --}}
    <x-slot:aside>
        <x-visual.chip icon="qr" label="QR Code généré" position="haut-gauche" />
        <x-visual.profile-card />
        <x-visual.stat-card value="1.2k" label="Vues du mois" position="bas-droite" />
    </x-slot:aside>

    <h1 class="auth__title">Bienvenue sur {{ config('app.name') }}</h1>
    <p class="auth__lead">Connectez-vous pour retrouver votre espace.</p>

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
                label="Adresse e-mail"
                placeholder="vous@exemple.sn"
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
                    <span>
                        Mot de passe oublié&nbsp;?
                            <a href="{{ route('password.request') }}">Réinitialisez-le en une minute</a>.
                        Inutile de recréer un compte&nbsp;: vos informations sont conservées.
                    </span>
                </div>
            @enderror

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div class="form-check m-0">
                    {{-- Champ caché : une case décochée n'envoie rien, le serveur
                         ne pourrait pas distinguer « non » de « absent ». --}}
                    <input type="hidden" name="remember" value="0">
                    <input class="form-check-input" type="checkbox" name="remember"
                           id="remember" value="1" @checked(old('remember'))>
                    <label class="form-check-label f__hint" for="remember">Se souvenir de moi</label>
                </div>

                <a href="{{ route('password.request') }}" class="f__hint">Mot de passe oublié&nbsp;?</a>
            </div>

            <x-button :block="true">Se connecter</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        Pas encore de compte&nbsp;?
        <a href="{{ route('register') }}">Créer un compte</a>
    </p>
</x-auth-layout>
