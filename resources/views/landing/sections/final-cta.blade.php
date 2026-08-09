{{-- APPEL À L'ACTION FINAL — fond vert très clair.
     ACTION PRINCIPALE : créer un compte. Props : $ctaUrl --}}
<section class="final">
  <div class="wrap">
    <h2 class="final__title">Prêt à rayonner au Sénégal&nbsp;?</h2>

    <p class="final__text">
      Rejoignez la communauté grandissante des leaders qui font confiance à
      {{ config('app.name') }} pour porter leur message.
    </p>

    <x-button variant="dark" :href="$ctaUrl">Démarrer mon aventure</x-button>
  </div>
</section>
