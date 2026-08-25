{{-- APPEL À L'ACTION FINAL — fond vert très clair.
     ACTION PRINCIPALE : créer un compte. Props : $ctaUrl --}}
<section class="final">
  <div class="wrap">
    <h2 class="final__title">{!! __('landing.final.titre') !!}</h2>

    <p class="final__text">
      {{ __('landing.final.texte', ['marque' => config('app.name')]) }}
    </p>

    <x-button variant="dark" :href="$ctaUrl">{{ __('landing.final.cta') }}</x-button>
  </div>
</section>
