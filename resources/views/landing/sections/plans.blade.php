{{-- TARIFS — les trois plans sont lus depuis la table plans, jamais en dur.
     La carte du milieu (annuel) est mise en avant : bordure verte, badge
     BEST VALUE, bouton vert foncé plein. Props : $plans, $ctaUrl --}}
<section class="section section--tint" id="tarifs">
  <div class="wrap">
    <h2 class="section-title">{!! __('landing.tarifs.titre') !!}</h2>
    <p class="section-sub">{{ __('landing.tarifs.sous_titre') }}</p>

    <div class="plans">
      @foreach ($plans as $plan)
        @php
            /*
             | LA MISE EN AVANT SUIT LE PRIX, PAS UN SLUG.
             |
             | Elle visait « annuel », une formule retirée du catalogue : plus
             | aucune carte n'était mise en avant, et le Standard tombait dans
             | le cas par défaut — d'où le « Par mois » affiché sur un
             | abonnement de 90 jours.
             */
            $featured = ! $plan->isFree();

            // La phrase vient du MODÈLE : un seul endroit la décide.
            $period = $plan->periodeFacturation();

            $cta = $plan->isFree() ? __('landing.tarifs.essayer') : __('landing.tarifs.abonner');
        @endphp

        <div class="plan{{ $featured ? ' plan--featured' : '' }}" data-reveal>
          @if ($featured)
            <span class="plan__badge">{{ __('landing.tarifs.best_value') }}</span>
          @endif

          {{-- LE NOM DE LA FORMULE N'EST PAS TRADUIT : il vient de la table
               `plans` et se modifie depuis l'administration. Le passer par
               __() donnait l'illusion d'une traduction là où il n'y en avait
               aucune — et aurait figé dans le code ce qui doit rester
               modifiable sans déploiement. --}}
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
                  {{-- Les inclusions viennent elles aussi de la base. --}}
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
