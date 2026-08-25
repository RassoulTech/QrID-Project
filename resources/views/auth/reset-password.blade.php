{{-- ACTION PRINCIPALE : définir le nouveau mot de passe. --}}
<x-auth-layout
    :title="__('auth.reset.titre')"
    aside-tone="light"
    :aside-title="__('auth.reset.aside_titre')"
    :aside-text="__('auth.reset.aside_texte')"
    :aside-step="2">

    {{-- Visuel de CETTE page : portrait illustré et pastille de validation. --}}
    <x-slot:aside>
        <x-visual.portrait name="Awa Ndiaye" :role="__('auth.forgot.visuel_role')" />
        <x-visual.chip icon="bouclier" :label="__('auth.reset.visuel_protege')" position="bas-droite" />
    </x-slot:aside>

    <h1 class="auth__title">{{ __('auth.reset.titre') }}</h1>
    <p class="auth__lead">{{ __('auth.reset.accroche') }}</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate class="mt-4">
        @csrf

        {{-- Le jeton vient de l'URL du lien reçu par e-mail, jamais d'une saisie. --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-fields">
            <x-auth-field
                name="email"
                type="email"
                :label="__('auth.champs.email')"
                :value="$request->email"
                autocomplete="username"
                inputmode="email"
                autofocus
            />

            <x-auth-password
                name="password"
                :label="__('auth.champs.nouveau_mot_de_passe')"
                autocomplete="new-password"
                :hint="__('auth.champs.huit_caracteres')"
                :meter="true"
            />

            <x-auth-password
                name="password_confirmation"
                :label="__('auth.champs.confirmer_mot_de_passe')"
                autocomplete="new-password"
            />

            <x-button :block="true">{{ __('auth.reset.bouton') }}</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        <a href="{{ route('login') }}">{{ __('auth.liens.retour_connexion') }}</a>
    </p>
</x-auth-layout>
