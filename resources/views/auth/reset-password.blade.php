{{-- ACTION PRINCIPALE : définir le nouveau mot de passe. --}}
<x-auth-layout
    title="Nouveau mot de passe"
    aside-tone="light"
    aside-title="Un mot de passe, et vous repartez."
    aside-text="Choisissez-en un que vous seul connaissez. L'indicateur vous dit où vous en êtes ; il ne vous impose rien que le formulaire n'accepte déjà."
    :aside-step="2">

    {{-- Visuel de CETTE page : portrait illustré et pastille de validation. --}}
    <x-slot:aside>
        <x-visual.portrait name="Awa Ndiaye" role="Architecte · Atelier Teranga" />
        <x-visual.chip icon="bouclier" label="Compte protégé" position="bas-droite" />
    </x-slot:aside>

    <h1 class="auth__title">Nouveau mot de passe</h1>
    <p class="auth__lead">Choisissez un nouveau mot de passe pour votre compte.</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate class="mt-4">
        @csrf

        {{-- Le jeton vient de l'URL du lien reçu par e-mail, jamais d'une saisie. --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-fields">
            <x-auth-field
                name="email"
                type="email"
                label="Adresse e-mail"
                :value="$request->email"
                autocomplete="username"
                inputmode="email"
                autofocus
            />

            <x-auth-password
                name="password"
                label="Nouveau mot de passe"
                autocomplete="new-password"
                hint="Au moins 8 caractères."
                :meter="true"
            />

            <x-auth-password
                name="password_confirmation"
                label="Confirmer le mot de passe"
                autocomplete="new-password"
            />

            <x-button :block="true">Réinitialiser mon mot de passe</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        <a href="{{ route('login') }}">Retour à la connexion</a>
    </p>
</x-auth-layout>
