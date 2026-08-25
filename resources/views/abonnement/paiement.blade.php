{{--
  Paiement — choix de la formule et du moyen de paiement.

  ACTION PRINCIPALE : payer et publier sa carte.

  ═══════════════════════════════════════════════════════════════════════
  AUCUN JAVASCRIPT
  ═══════════════════════════════════════════════════════════════════════
  Les états de sélection sont portés par `:checked` et le sélecteur de frère
  adjacent. Un récapitulatif qui recalculerait un total en direct exigerait du
  script pour une information DÉJÀ écrite sur chaque carte de formule : le prix.
  Le panneau de droite ne porte donc que ce qui ne dépend pas du choix.

  C'est aussi ce qui rend l'écran utilisable si le script ne charge pas — règle
  permanente du système de design.
--}}
@php
    /*
     | L'ÉCONOMIE DE LA FORMULE LONGUE, CALCULÉE ET NON ÉCRITE À LA MAIN.
     |
     | La référence est la formule de 30 jours. Le jour où un prix bouge en
     | base, le pourcentage suit — un « -17 % » codé en dur deviendrait faux
     | sans que personne ne le voie.
     */
    $reference = $plans->firstWhere('duration_days', 30);

    $equivalentMensuel = function ($plan) {
        if ($plan->duration_days < 60 || $plan->isFree()) {
            return null;
        }

        return (int) round($plan->price_fcfa / ($plan->duration_days / 30));
    };

    $economie = function ($plan) use ($reference, $equivalentMensuel) {
        $mensuel = $equivalentMensuel($plan);

        if (! $reference || ! $mensuel || $reference->price_fcfa <= 0) {
            return null;
        }

        $ecart = (int) round(100 - ($mensuel * 100 / $reference->price_fcfa));

        return $ecart > 0 ? $ecart : null;
    };
@endphp

<x-app-layout :title="__('payment.titre')">

    <form method="POST" action="{{ route('abonnement.paiement.store') }}"
          class="checkout" novalidate>
        @csrf

        {{-- ═══════════════ COLONNE PRINCIPALE ═══════════════ --}}
        <div class="checkout__main">
            <header class="checkout__head">
                <p class="checkout__kicker">{{ __('payment.kicker') }}</p>

                <h1 class="checkout__title">
                    {{ $renewal ? __('payment.entete.renouveler') : __('payment.entete.choisir') }}
                </h1>

                <p class="checkout__sub">
                    @if ($renewal)
                        {{-- translatedFormat suit la locale posée par le
                             middleware : « 3 septembre 2026 » devient
                             « September 3, 2026 » sans rien de plus. --}}
                        {{ __('payment.entete.sous_renouveler', [
                            'date' => $subscription->ends_at?->translatedFormat('j F Y'),
                        ]) }}
                    @else
                        {{ __('payment.entete.sous_choisir') }}
                    @endif
                </p>
            </header>

            {{-- ─────────────── FORMULE ───────────────
                 Le prix vient de la base, jamais du formulaire. --}}
            <fieldset class="checkout__group">
                <legend class="checkout__legend">
                    <span class="checkout__step" aria-hidden="true">1</span>
                    {{ __('payment.formule.legende') }}
                </legend>

                <div class="plan-grid">
                    @foreach ($plans as $plan)
                        @php($remise = $economie($plan))
                        @php($parMois = $equivalentMensuel($plan))

                        <label class="plan-card">
                            <input type="radio" name="plan" value="{{ $plan->slug }}"
                                   class="plan-card__input"
                                   @checked(old('plan', $loop->first ? $plan->slug : null) === $plan->slug)
                                   required>

                            <span class="plan-card__box">
                                <span class="plan-card__top">
                                    <span class="plan-card__name">{{ $plan->name }}</span>

                                    @if ($remise)
                                        <span class="plan-card__badge">{{ __('payment.formule.economie', ['pourcent' => $remise]) }}</span>
                                    @endif
                                </span>

                                <span class="plan-card__price">{{ $plan->formattedPrice() }}</span>

                                <span class="plan-card__meta">
                                    {{ __('payment.formule.duree', [
                                        'periodicite' => $plan->periodicite(),
                                        'jours' => $plan->duration_days,
                                    ]) }}
                                    @if ($parMois)
                                        <br>{{ __('payment.formule.par_mois', [
                                            'montant' => number_format($parMois, 0, ',', ' '),
                                        ]) }}
                                    @endif
                                </span>

                                @if (! empty($plan->features))
                                    <span class="plan-card__features">
                                        @foreach ($plan->features as $atout)
                                            <span class="plan-card__feature">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="3"
                                                     stroke-linecap="round" stroke-linejoin="round"
                                                     aria-hidden="true">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                                {{ $atout }}
                                            </span>
                                        @endforeach
                                    </span>
                                @endif

                                {{-- La coche de sélection. aria-hidden : l'état
                                     réel est porté par le bouton radio, qu'un
                                     lecteur d'écran annonce déjà. --}}
                                <span class="plan-card__tick" aria-hidden="true">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="3.5"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('plan')
                    <span class="f__error">{{ $message }}</span>
                @enderror
            </fieldset>

            {{-- ─────────────── MOYEN DE PAIEMENT ───────────────
                 La marque de l'opérateur AVANT son nom : sur un écran de
                 paiement, on reconnaît une couleur avant de lire un mot.

                 MASQUÉ TANT QU'AUCUNE PASSERELLE N'ENCAISSE. Choisir Wave ou
                 Orange Money quand rien ne peut aboutir, c'est promettre un
                 paiement qui n'existe pas. --}}
            <fieldset class="checkout__group" @unless ($paiementDisponible) hidden @endunless>
                <legend class="checkout__legend">
                    <span class="checkout__step" aria-hidden="true">2</span>
                    {{ __('payment.moyen.legende') }}
                </legend>

                <div class="method-grid">
                    @foreach ($methods as $cle => $libelle)
                        <label class="pay-method">
                            <input type="radio" name="method" value="{{ $cle }}"
                                   class="pay-method__input"
                                   @checked(old('method', $loop->first ? $cle : null) === $cle)
                                   required>

                            <span class="pay-method__box">
                                <x-operator-mark :methode="$cle" />
                                <span class="pay-method__name">{{ $libelle }}</span>

                                <span class="pay-method__tick" aria-hidden="true">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="3.5"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('method')
                    <span class="f__error">{{ $message }}</span>
                @enderror
            </fieldset>
        </div>

        {{-- ═══════════════ PANNEAU DE CONFIANCE ═══════════════
             Il ne porte QUE ce qui ne dépend pas de la formule choisie : sans
             cela il faudrait du script pour le tenir à jour, et il mentirait
             le jour où le script ne charge pas.

             Collant sur grand écran, il suit le regard jusqu'au bouton ; sur
             téléphone il se place naturellement après les choix. --}}
        <aside class="checkout__aside">
            @unless ($paiementDisponible)
                {{-- ══════════════ AUCUNE PASSERELLE BRANCHÉE ══════════════
                     Ce panneau remplace le bouton « Payer », qui menait à une
                     page 500 disant « le problème vient de nous ». C'était
                     faux : rien n'est en panne, l'encaissement en ligne n'est
                     simplement pas encore ouvert.

                     Dire la vérité et donner la marche à suivre vaut mieux
                     qu'une erreur serveur au seul moment où le client sortait
                     son argent. --}}
                <div class="checkout__panel">
                    <h2 class="checkout__panel-title">{{ __('payment.manuel.titre') }}</h2>

                    <ul class="trust">
                        <li class="trust__item">
                            <span class="trust__icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.1A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5z"/>
                                </svg>
                            </span>
                            <span>
                                <strong>{{ __('payment.manuel.ferme_titre') }}</strong>
                                {{ __('payment.manuel.ferme_texte') }}
                            </span>
                        </li>

                        <li class="trust__item">
                            <span class="trust__icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="10" width="16" height="11" rx="2"/>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                </svg>
                            </span>
                            <span>
                                <strong>{{ __('payment.manuel.aucun_debit_titre') }}</strong>
                                {{ __('payment.manuel.aucun_debit_texte') }}
                            </span>
                        </li>
                    </ul>

                    <x-button :href="$supportWhatsapp" :block="true"
                              target="_blank" rel="noopener">
                        {{ __('payment.manuel.whatsapp') }}
                    </x-button>

                    @if ($ficheAdmin)
                        {{-- L'exploitant qui se heurte lui-même à cet écran a
                             déjà le pouvoir de le débloquer. Il lui manquait
                             le chemin — trois écrans plus loin, dans une
                             liste où il ne pensait pas se chercher. --}}
                        <a href="{{ $ficheAdmin }}" class="checkout__later">
                            {{ __('payment.manuel.admin') }}
                        </a>
                    @endif

                    <a href="{{ route('dashboard') }}" class="checkout__later">{{ __('payment.manuel.retour') }}</a>
                </div>
            @else
            <div class="checkout__panel">
                <h2 class="checkout__panel-title">{{ __('payment.confiance.titre') }}</h2>

                <ul class="trust">
                    <li class="trust__item">
                        <span class="trust__icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="10" width="16" height="11" rx="2"/>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            </svg>
                        </span>
                        <span>
                            <strong>{{ __('payment.confiance.debit_titre') }}</strong>
                            {{ __('payment.confiance.debit_texte') }}
                        </span>
                    </li>

                    <li class="trust__item">
                        <span class="trust__icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4v6h6"/>
                                <path d="M20 20v-6h-6"/>
                                <path d="M20 9a8 8 0 0 0-14-3L4 8"/>
                                <path d="M4 15a8 8 0 0 0 14 3l2-2"/>
                            </svg>
                        </span>
                        <span>
                            <strong>{{ __('payment.confiance.lien_titre') }}</strong>
                            {{ __('payment.confiance.lien_texte') }}
                        </span>
                    </li>

                    <li class="trust__item">
                        <span class="trust__icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </span>
                        <span>
                            <strong>{{ __('payment.confiance.ligne_titre') }}</strong>
                            {{ __('payment.confiance.ligne_texte') }}
                        </span>
                    </li>
                </ul>

                <x-button :block="true">
                    {{ $renewal ? __('payment.confiance.renouveler') : __('payment.confiance.payer') }}
                </x-button>

                <a href="{{ route('dashboard') }}" class="checkout__later">{{ __('payment.confiance.plus_tard') }}</a>
            </div>
            @endunless
        </aside>
    </form>
</x-app-layout>
