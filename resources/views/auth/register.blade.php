{{--
  ACTION PRINCIPALE : créer son compte.

  Cinq champs, pas un de plus : nom, e-mail, téléphone, mot de passe et sa
  confirmation. AUCUN champ professionnel ici — fonction, entreprise, réseaux
  appartiennent à la carte professionnelle, pas aux identifiants d'accès.
--}}
<x-auth-layout
    :title="__('auth.register.titre')"
    :description="__('auth.register.description', ['marque' => config('app.name')])"
    aside-tone="light"
    :aside-title="__('auth.register.aside_titre')"
    :aside-text="__('auth.register.aside_texte')"
    :aside-step="2">

    {{-- Visuel de CETTE page : cartes empilées sur fond clair. --}}
    <x-slot:aside>
        <div class="av-pile">
            <x-visual.badge-card
                icon="bouclier"
                :title="__('auth.register.visuel_titre')"
                :text="__('auth.register.visuel_texte')" />

            <x-visual.profile-card :lines="2" :cta="false" />
        </div>

        <x-visual.chip icon="check" :label="__('auth.register.visuel_securise')" position="bas-droite" />
    </x-slot:aside>

    <h1 class="auth__title">{{ __('auth.register.titre') }}</h1>
    <p class="auth__lead">{{ __('auth.register.accroche') }}</p>

    <x-auth-tabs active="register" />

    {{-- AVANT le formulaire, et c'est encore plus vrai ici : Google supprime
         le lien de confirmation, donc l'attente devant une boîte de réception,
         donc la dépendance à une messagerie qui doit fonctionner. Proposer ce
         raccourci APRÈS six champs déjà remplis n'a aucun sens. --}}
    <x-google-button :label="__('auth.google.inscrire')" />

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        <x-form-legende />

        {{-- Idempotence : une seconde soumission ne recrée rien. --}}
        <input type="hidden" name="_idem" value="{{ $idempotencyToken }}">

        <div class="auth-fields">
            <x-auth-field
                name="name"
                :label="__('auth.champs.nom_complet')"
                :placeholder="__('auth.champs.nom_exemple')"
                autocomplete="name"
                maxlength="255"
                autofocus
            />

            <x-auth-field
                name="email"
                type="email"
                :label="__('auth.champs.email')"
                :placeholder="__('auth.champs.email_exemple')"
                autocomplete="username"
                inputmode="email"
                :value="$prefillEmail ?? null"
            />

            {{-- L'INDICATIF EST CHOISI, PLUS SUPPOSÉ. Le préfixe « +221 » figé
                 rendait l'inscription impossible à un client ivoirien ou à un
                 Sénégalais de la diaspora. Toute saisie raisonnable est
                 acceptée puis normalisée au format international complet. --}}
            <x-phone-field name="phone" :label="__('auth.champs.telephone')" :value="old('phone')" />

            <x-auth-password
                name="password"
                autocomplete="new-password"
                :hint="__('auth.champs.huit_caracteres')"
                :meter="true"
            />

            <x-auth-password
                name="password_confirmation"
                :label="__('auth.champs.confirmer_mot_de_passe')"
                autocomplete="new-password"
            />

            <x-button :block="true">{{ __('auth.register.bouton') }}</x-button>
        </div>
    </form>

    <p class="f__hint text-center mt-4 mb-0">
        {!! __('auth.liens.deja_inscrit') !!} <a href="{{ route('login') }}">{{ __('auth.liens.se_connecter') }}</a>
    </p>
</x-auth-layout>
