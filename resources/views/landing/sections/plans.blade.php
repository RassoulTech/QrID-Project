{{-- TARIFS — les trois plans sont lus depuis la table plans, jamais en dur.
     La carte du milieu (annuel) est mise en avant : bordure verte, badge
     BEST VALUE, bouton vert foncé plein. Props : $plans, $ctaUrl --}}
<section class="section section--tint" id="tarifs">
  <div class="wrap">
    <h2 class="section-title">Tarifs simples &amp; transparents</h2>
    <p class="section-sub">Payable via Wave, Orange Money et Free Money.</p>

    <div class="plans">
      @foreach ($plans as $plan)
        @php
            $featured = $plan->slug === 'annuel';

            $period = match (true) {
                $plan->isFree() => 'Pendant '.$plan->duration_days.' jours',
                $featured => 'Par an (2 mois offerts)',
                default => 'Par mois',
            };

            $cta = match (true) {
                $plan->isFree() => 'Essayer gratuitement',
                $featured => "Choisir l'Annuel",
                default => "S'abonner",
            };
        @endphp

        <div class="plan{{ $featured ? ' plan--featured' : '' }}" data-reveal>
          @if ($featured)
            <span class="plan__badge">Best value</span>
          @endif

          <div class="plan__name">{{ $plan->name }}</div>

          <div class="plan__price">
            {{ number_format($plan->price_fcfa, 0, ',', ' ') }}<small> FCFA</small>
          </div>

          <div class="plan__period">{{ $period }}</div>

          @if (! empty($plan->features))
            <ul class="plan__list">
              @foreach ($plan->features as $index => $feature)
                {{-- Sur la formule d'essai, la dernière inclusion est grisée. --}}
                <li class="{{ $plan->isFree() && $loop->last ? 'is-muted' : '' }}">
                  <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05"/>
                  </svg>
                  <span>{{ $feature }}</span>
                </li>
              @endforeach
            </ul>
          @endif

          <x-button :variant="$featured ? 'dark' : 'outline'" :href="$ctaUrl" :block="true">
            {{ $cta }}
          </x-button>
        </div>
      @endforeach
    </div>
  </div>
</section>
