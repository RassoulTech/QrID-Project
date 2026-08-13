{{--
  ACTION PRINCIPALE : créer son compte.

  Cinq champs, pas un de plus : nom, e-mail, téléphone, mot de passe et sa
  confirmation. AUCUN champ professionnel ici — fonction, entreprise, réseaux
  appartiennent à la carte professionnelle, pas aux identifiants d'accès.
--}}
<x-auth-layout
    title="Créer un compte"
    description="Créez votre compte {{ config('app.name') }} en moins de trois minutes."
    aside-tone="light"
    aside-title="Protégez votre identité professionnelle."
    aside-text="Vos coordonnées restent à vous. Vous choisissez ce qui s'affiche, vous le modifiez quand vous voulez, et vous le partagez d'un seul geste."
    :aside-step="2">

    {{-- Visuel de CETTE page : cartes empilées sur fond clair. --}}
    <x-slot:aside>
        <div class="av-pile">
            <x-visual.badge-card
                icon="bouclier"
                title="Vos données vous appartiennent"
                text="Rien n'est publié sans votre accord, rien n'est revendu." />

            <x-visual.profile-card :lines="2" :cta="false" />
        </div>

        <x-visual.chip icon="check" label="Compte sécurisé" position="bas-droite" />
    </x-slot:aside>

    <h1 class="auth__title">Créer un compte</h1>
    <p class="auth__lead">Quinze jours d'essai gratuit, sans carte bancaire.</p>

    <x-auth-tabs active="register" />

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        {{-- Idempotence : une seconde soumission ne recrée rien. --}}
        <input type="hidden" name="_idem" value="{{ $idempotencyToken }}">

        <div class="auth-fields">
            <x-auth-field
                name="name"
                label="Nom complet"
                placeholder="Awa Ndiaye"
                autocomplete="name"
                maxlength="255"
                autofocus
            />

            <x-auth-field
                name="email"
                type="email"
                label="Adresse e-mail"
                placeholder="vous@exemple.sn"
                autocomplete="username"
                inputmode="email"
                :value="$prefillEmail ?? null"
            />

            {{-- Toute saisie raisonnable est acceptée puis normalisée côté
                 serveur au format +221XXXXXXXXX. --}}
            <x-auth-field
                name="phone"
                type="tel"
                label="Téléphone"
                placeholder="77 383 13 64"
                autocomplete="tel"
                inputmode="tel"
                maxlength="32"
            />

            <x-auth-password
                name="password"
                autocomplete="new-password"
                hint="Au moins 8 caractères."
                :meter="true"
            />

            <x-auth-password
                name="password_confirmation"
                label="Confirmer le mot de passe"
                autocomplete="new-password"
            />

            <x-button :block="true">Recevoir mon lien de confirmation</x-button>
        </div>
    </form>

    {{-- Sur CET écran, Google fait gagner davantage : il supprime le lien de
         confirmation, donc l'attente devant une boîte de réception, donc la
         dépendance à une messagerie qui doit fonctionner. --}}
    <x-google-button label="S'inscrire avec Google" />

    <p class="f__hint text-center mt-4 mb-0">
        Déjà inscrit&nbsp;? <a href="{{ route('login') }}">Se connecter</a>
    </p>
</x-auth-layout>
