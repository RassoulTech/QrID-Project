{{-- HERO — texte à gauche, téléphone à droite avec trois cartes flottantes.
     Toute la séquence d'entrée est en CSS, déclenchée au chargement.
     ACTION PRINCIPALE : créer un compte.
     Props : $ctaUrl, $heroProfile, $heroViews --}}
<section class="hero">
  <div class="wrap hero__grid">

    <div>
      <h1 class="hero__title hero-title">
        {!! __('Votre identité professionnelle <span class="hero-mark">:mot</span>', ['mot' => __('réinventée')]) !!}
      </h1>

      <p class="hero__lead hero-lead">
        {{ __("La plateforme d'identité numérique sécurisée pour l'élite professionnelle du Sénégal. Centralisez votre expertise et rayonnez avec élégance.") }}
      </p>

      <div class="hero__cta hero-actions">
        <x-button variant="dark" :href="$ctaUrl">
          {{ __("Démarrer l'essai gratuit") }}
          <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
          </svg>
        </x-button>

        <x-button variant="outline" :href="route('profile.demo')">{{ __('Voir un exemple') }}</x-button>
      </div>
    </div>

    <div class="stage">
      <div class="float float--qr">
        <span class="icon" aria-hidden="true">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M2 2h2v2H2z"/>
            <path d="M6 0v6H0V0zM5 1H1v4h4zM4 12H2v2h2z"/>
            <path d="M6 10v6H0v-6zm-5 1v4h4v-4zm11-9h2v2h-2z"/>
            <path d="M10 0v6h6V0zm5 1v4h-4V1zM8 8v2H6V8zm2 2V8h2v2zm-2 2v-2H6v2zm2 0h2v-2h-2zm4 0v2h-2v-2z"/>
          </svg>
        </span>
        <span class="label">{{ __('QR Code généré') }}</span>
      </div>

      <x-phone :profile="$heroProfile" size="lg" />

      <div class="float float--views">
        <div class="n">{{ $heroViews }}</div>
        <div class="l">{{ __('Vues totales') }}</div>
      </div>

      <div class="float float--saved">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
          <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4"/>
        </svg>
        <span class="label">{{ __('Contact enregistré') }}</span>
      </div>
    </div>

  </div>
</section>
