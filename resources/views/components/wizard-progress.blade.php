{{--
  x-wizard-progress — LES ÉTAPES D'UN ASSISTANT (maquette A : « 1 Identité — 2 Coordonnées — 3 Apparence »).

      <x-wizard-progress :step="2" />
      <x-wizard-progress :step="2" :labels="['Qui êtes-vous ?', 'Comment vous joindre ?', 'Votre style']" />

  Une étape FAITE porte une coche sur baobab ; l'étape COURANTE un anneau
  et l'encre pleine ; une étape À VENIR reste en grès. Trois signaux qui ne
  dépendent pas de la couleur : la coche, l'anneau, le numéro.

  `aria-current="step"` sur l'étape courante ; le rang « Étape 2 sur 3 »
  est écrit en toutes lettres pour qui ne voit pas la frise.
--}}
@props(['step', 'total' => 3, 'labels' => []])

<div {{ $attributes->merge(['class' => 'etapes']) }}>
    <ol class="etapes__liste" aria-label="{{ __('profile.wizard.progression', ['n' => $step, 'total' => $total]) }}">
        @for ($i = 1; $i <= $total; $i++)
            <li @class(['etapes__item', 'is-faite' => $i < $step, 'is-courante' => $i === (int) $step])
                @if ($i === (int) $step) aria-current="step" @endif>
                <span class="etapes__rang">
                    @if ($i < $step)
                        <x-icone name="check" />
                        <span class="visually-hidden">{{ $i }}</span>
                    @else
                        {{ $i }}
                    @endif
                </span>
                @if (! empty($labels[$i - 1]))
                    <span class="etapes__libelle">{{ $labels[$i - 1] }}</span>
                @endif
            </li>
        @endfor
    </ol>
    <p class="etapes__resume">{{ __('profile.wizard.etape_sur', ['n' => $step, 'total' => $total]) }}</p>
</div>
