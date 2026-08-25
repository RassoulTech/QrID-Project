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
    <button type="submit" tabindex="-1" aria-hidden="true"
            style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);border:0"></button>

    <div class="step-card">
        @php
            /* Résolus ICI et non dans @props : une valeur par défaut de
               @props est une expression évaluée à la compilation du
               composant, et __() y figerait la langue du premier rendu. */
            $intituleRetour = $backLabel ?? __('profile.wizard.retour');
            $intituleEnvoi = $submit ?? __('profile.wizard.continuer');
            $rang = __('profile.wizard.etape_sur', ['n' => $step, 'total' => 3]);
        @endphp

        <p class="step-card__kicker">{{ $rang }}</p>

        <div class="step-bar" role="progressbar" aria-valuenow="{{ $step }}"
             aria-valuemin="1" aria-valuemax="3"
             aria-label="{{ $rang }}">
            @for ($i = 1; $i <= 3; $i++)
                <span @class(['step-bar__seg', 'is-done' => $i <= $step])></span>
            @endfor
        </div>

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
