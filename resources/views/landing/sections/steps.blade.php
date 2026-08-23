{{-- COMMENT ÇA MARCHE — titre souligné d'un trait vert, trois colonnes,
     numéro dans un cercle fin bordé. Props : $steps --}}
<section class="section" id="ressources">
  <div class="wrap">
    <h2 class="section-title section-title--underlined">{{ __('Comment ça marche') }}</h2>

    <div class="steps">
      @foreach ($steps as $step)
        <div class="step" data-reveal>
          <div class="step__num">{{ $loop->iteration }}</div>
          <h3 class="step__title">{{ __($step['title']) }}</h3>
          <p class="step__text">{{ __($step['text']) }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
