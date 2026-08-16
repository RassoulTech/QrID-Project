{{--
  Paiement sécurisé — choix de la formule et du moyen de paiement.

  STRUCTURE PROVISOIRE : les textes et la mise en page définitive viennent de
  la maquette « Paiement sécurisé », que je ne peux pas encore lire. Cet écran
  est complet côté logique (formules réelles, montants en base, trois moyens,
  Payment créé en « pending ») et sera réhabillé.

  ACTION PRINCIPALE : payer et publier sa carte.
--}}
<x-app-layout title="Paiement sécurisé">

    <div class="step-card">
        <p class="step-card__kicker">Abonnement</p>

        <h1 class="step-card__title">
            {{ $renewal ? 'Renouveler mon abonnement' : 'Choisir ma formule' }}
        </h1>

        <p class="step-card__sub">
            @if ($renewal)
                Votre abonnement court jusqu'au
                {{ $subscription->ends_at?->translatedFormat('j F Y') }}.
                Un renouvellement s'ajoute à ce qui reste dû, il ne le remplace pas.
            @else
                Votre carte est prête. Choisissez la formule qui vous convient
                pour la mettre en ligne.
            @endif
        </p>

        <form method="POST" action="{{ route('abonnement.paiement.store') }}" novalidate>
            @csrf

            {{-- ---------------------------------------------------------------
                 FORMULE — le prix vient de la base, jamais du formulaire.
            ---------------------------------------------------------------- --}}
            <fieldset class="step-fields">
                <legend class="f__label">Formule</legend>

                @foreach ($plans as $plan)
                    <label class="pay-option">
                        <input type="radio" name="plan" value="{{ $plan->slug }}"
                               class="pay-option__input"
                               @checked(old('plan', $loop->first ? $plan->slug : null) === $plan->slug)
                               required>
                        <span class="pay-option__box">
                            <span class="pay-option__name">{{ $plan->name }}</span>
                            <span class="pay-option__price">{{ $plan->formattedPrice() }}</span>
                            <span class="pay-option__meta">{{ $plan->duration_days }} jours</span>
                        </span>
                    </label>
                @endforeach

                @error('plan')
                    <span class="f__error">{{ $message }}</span>
                @enderror
            </fieldset>

            {{-- ---------------------------------------------------------------
                 MOYEN DE PAIEMENT
            ---------------------------------------------------------------- --}}
            <fieldset class="step-fields mt-4">
                <legend class="f__label">Moyen de paiement</legend>

                @foreach ($methods as $cle => $libelle)
                    <label class="pay-option">
                        <input type="radio" name="method" value="{{ $cle }}"
                               class="pay-option__input"
                               @checked(old('method', $loop->first ? $cle : null) === $cle)
                               required>
                        {{-- La marque de l'opérateur AVANT son nom : sur un
                             écran de paiement, on reconnaît une couleur avant
                             de lire un mot. Voir x-operator-mark pour ce qui
                             s'affiche tant que les logos officiels ne sont pas
                             déposés. --}}
                        <span class="pay-option__box">
                            <x-operator-mark :methode="$cle" />
                            <span class="pay-option__name">{{ $libelle }}</span>
                        </span>
                    </label>
                @endforeach

                @error('method')
                    <span class="f__error">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="step-card__foot">
                <a href="{{ route('dashboard') }}" class="step-back">Plus tard</a>
                <x-button>{{ $renewal ? 'Renouveler' : 'Payer et publier ma carte' }}</x-button>
            </div>
        </form>
    </div>

    <p class="pay-note">
        Aucune somme n'est débitée tant que vous n'avez pas confirmé chez votre opérateur.
    </p>
</x-app-layout>
