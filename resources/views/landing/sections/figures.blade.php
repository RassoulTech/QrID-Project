{{-- TROIS CHIFFRES CLÉS — cartes blanches sur fond gris clair.
     Valeurs de configuration décrivant la promesse produit. Props : $figures --}}
<section class="figures">
  <div class="wrap figures__grid">
    @foreach ($figures as $figure)
      <div class="figure-card" data-reveal>
        <div class="figure-card__n">{{ $figure['number'] }}</div>
        <div class="figure-card__w">{{ __($figure['word']) }}</div>
        <div class="figure-card__l">{{ __($figure['label']) }}</div>
      </div>
    @endforeach
  </div>
</section>
