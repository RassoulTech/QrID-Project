{{--
  ACTION PRINCIPALE : relancer l'inscription. Une seule saisie.

  Le formulaire est un GET vers /register : l'adresse arrive en paramètre et
  le formulaire d'inscription la trouve déjà remplie. Rien n'est écrit ici,
  donc rien ne justifierait un POST.
--}}
<x-auth-layout
    :title="__('auth.expired.titre')"
    aside-tone="light"
    :aside-title="__('auth.expired.aside_titre')"
    :aside-text="__('auth.expired.aside_texte')"
    :aside-step="1">

    {{-- Visuel de CETTE page : carte verte à horloge. Aucune maquette ne m'a
         été fournie pour cet écran — composition dérivée de ses voisines. --}}
    <x-slot:aside>
        <div class="av-pile">
            <x-visual.badge-card
                icon="horloge"
                :title="__('auth.expired.visuel_titre')"
                :text="__('auth.expired.visuel_texte')" />

            <x-visual.profile-card :lines="2" :cta="false" />
        </div>
    </x-slot:aside>

    <span class="mail-icon" aria-hidden="true">
        <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
        </svg>
    </span>

    <h1 class="auth__title">{{ __('auth.expired.entete') }}</h1>
    <p class="auth__lead">{!! __('auth.expired.accroche') !!}</p>

    <form method="GET" action="{{ route('register') }}" novalidate class="mt-4">
        <div class="auth-fields">
            <x-auth-field
                name="email"
                type="email"
                :label="__('auth.champs.email')"
                :placeholder="__('auth.champs.email_exemple')"
                autocomplete="username"
                inputmode="email"
                :value="$email ?? null"
                autofocus
            />

            <x-button :block="true">{{ __('auth.expired.bouton') }}</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        <a href="{{ route('login') }}">{{ __('auth.liens.retour_connexion') }}</a>
    </p>
</x-auth-layout>
