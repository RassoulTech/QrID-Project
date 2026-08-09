{{-- ACTION PRINCIPALE : recevoir le lien de réinitialisation. Un seul champ. --}}
<x-auth-layout
    title="Mot de passe oublié"
    aside-tone="light"
    aside-title="Nous vous remettons en selle."
    aside-text="Un lien valable une heure, envoyé à votre adresse. Personne d'autre ne peut l'utiliser, et votre mot de passe actuel reste valable jusqu'à ce que vous en choisissiez un nouveau."
    :aside-step="1">

    {{-- Visuel de CETTE page : carte verte à clé, puis illustration de portrait. --}}
    <x-slot:aside>
        <div class="av-pile">
            <x-visual.badge-card
                icon="cle"
                title="Bon retour parmi nous"
                text="Un seul lien suffit à reprendre la main sur votre compte." />

            <x-visual.portrait name="Awa Ndiaye" role="Architecte · Atelier Teranga" />
        </div>
    </x-slot:aside>

    <h1 class="auth__title">Mot de passe oublié&nbsp;?</h1>
    <p class="auth__lead">
        Indiquez votre adresse e-mail : nous vous envoyons un lien pour en définir un nouveau.
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate class="mt-4">
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

            <x-button :block="true">Recevoir le lien</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        <a href="{{ route('login') }}">Retour à la connexion</a>
    </p>
</x-auth-layout>
