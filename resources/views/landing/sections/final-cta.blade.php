{{-- APPEL À L'ACTION FINAL — fond vert très clair.
     ACTION PRINCIPALE : créer un compte. Props : $ctaUrl --}}
<section class="final">
  <div class="wrap">
    <h2 class="final__title">{!! __('Prêt à rayonner au Sénégal&nbsp;?') !!}</h2>

    <p class="final__text">
      {{ __('Rejoignez la communauté grandissante des leaders qui font confiance à :marque pour porter leur message.', ['marque' => config('app.name')]) }}
    </p>

    <x-button variant="dark" :href="$ctaUrl">{{ __('Démarrer mon aventure') }}</x-button>
  </div>
</section>
