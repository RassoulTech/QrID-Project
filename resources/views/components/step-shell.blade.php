{{--
  x-step-shell — carte du parcours de création.

  Entête (compteur + progression), titre, sous-titre, champs, pied de carte.
  Un seul bouton principal par écran, et il est toujours à droite.

  <x-step-shell :step="1" title="…" subtitle="…" :action="route('…')" :back="route('…')">
--}}
@props([
    'step',
    'title',
    'subtitle',
    'action',
    'back' => null,
    'backLabel' => null,
    'submit' => null,
    'multipart' => false,
])

<form method="POST" action="{{ $action }}"
      @if ($multipart) enctype="multipart/form-data" @endif
      novalidate>
    @csrf

    {{-- UNE SEULE LEGENDE POUR LES TROIS ETAPES. La poser dans chaque vue
         d'etape aurait garanti qu'elle finisse par diverger de l'une d'elles. --}}
    <x-form-legende />

    {{-- La touche Entrée déclenche le PREMIER bouton submit du formulaire.
         Sans ce leurre, ce serait « Ajouter un réseau » à l'étape 2.
         Ici, Entrée vaut toujours « Continuer ». --}}
    <button type="submit" tabindex="-1" aria-hidden="true" class="visually-hidden"></button>

    <div class="step-card">
        @php
            /* Résolus ICI et non dans @props : une valeur par défaut de
               @props est une expression évaluée à la compilation du
               composant, et __() y figerait la langue du premier rendu. */
            $intituleRetour = $backLabel ?? __('profile.wizard.retour');
            $intituleEnvoi = $submit ?? __('profile.wizard.continuer');
            $rang = __('profile.wizard.etape_sur', ['n' => $step, 'total' => 3]);
        @endphp

        {{-- LES ÉTAPES : la frise A (coche, anneau, numéro) et le rang en
             toutes lettres. Voir x-wizard-progress. --}}
        <x-wizard-progress :step="$step" :total="3" class="step-card__etapes" :labels="[
            __('profile.wizard.entete_1'),
            __('profile.wizard.entete_2'),
            __('profile.wizard.entete_3'),
        ]" />

        <h1 class="step-card__title">{{ $title }}</h1>
        <p class="step-card__sub">{{ $subtitle }}</p>

        <div class="step-fields">
            {{ $slot }}
        </div>

        <div class="step-card__foot">
            @if ($back)
                <a href="{{ $back }}" class="step-back">&larr; {{ $intituleRetour }}</a>
            @else
                <span></span>
            @endif

            <button type="submit" class="btn-pill btn-dark">{{ $intituleEnvoi }}</button>
        </div>
    </div>
</form>
