{{-- ACTION PRINCIPALE : recevoir le lien de réinitialisation. Un seul champ. --}}
<x-auth-layout
    :title="__('auth.forgot.titre')"
    aside-tone="light"
    :aside-title="__('auth.forgot.aside_titre')"
    :aside-text="__('auth.forgot.aside_texte')"
    :aside-step="1">

    {{-- Visuel de CETTE page : carte verte à clé, puis illustration de portrait. --}}
    <x-slot:aside>
        <div class="av-pile">
            <x-visual.badge-card
                icon="cle"
                :title="__('auth.forgot.visuel_titre')"
                :text="__('auth.forgot.visuel_texte')" />

            {{-- Le NOM reste tel quel : c'est un nom propre, pas de l'interface. --}}
            <x-visual.portrait name="Awa Ndiaye" :role="__('auth.forgot.visuel_role')" />
        </div>
    </x-slot:aside>

    <h1 class="auth__title">{!! __('auth.forgot.question') !!}</h1>
    <p class="auth__lead">{{ __('auth.forgot.accroche') }}</p>

    <form method="POST" action="{{ route('password.email') }}" novalidate class="mt-4">
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

            <x-button :block="true">{{ __('auth.forgot.bouton') }}</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        <a href="{{ route('login') }}">{{ __('auth.liens.retour_connexion') }}</a>
    </p>
</x-auth-layout>
