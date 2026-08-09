{{--
  Zone sécurisée — confirmation du mot de passe.

  Huitième écran, hors des sept maquettes, mais posé sur le même gabarit :
  aucune page d'authentification ne doit détonner.
--}}
<x-auth-layout
    title="Confirmation requise"
    aside-tone="dark"
    aside-title="Une vérification de plus, une inquiétude de moins."
    aside-text="Avant une action sensible, nous redemandons votre mot de passe. Si quelqu'un s'assied devant votre écran, il ne pourra rien changer."
    :aside-step="2">

    {{-- Écran hors des maquettes fournies : visuel dérivé de la connexion. --}}
    <x-slot:aside>
        <x-visual.profile-card :cta="false" />
        <x-visual.chip icon="bouclier" label="Zone sécurisée" position="bas-droite" />
    </x-slot:aside>

    <h1 class="auth__title">Confirmation requise</h1>
    <p class="auth__lead">
        Zone sécurisée. Confirmez votre mot de passe pour continuer.
    </p>

    <form method="POST" action="{{ route('password.confirm.store') }}" novalidate class="mt-4">
        @csrf

        <div class="auth-fields">
            <x-auth-password name="password" autocomplete="current-password" autofocus />

            <x-button :block="true">Confirmer</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        <a href="{{ route('dashboard') }}">Retour à mon espace</a>
    </p>
</x-auth-layout>
