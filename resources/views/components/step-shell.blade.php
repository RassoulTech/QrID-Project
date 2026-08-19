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
    'backLabel' => 'Retour',
    'submit' => 'Continuer',
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
        <p class="step-card__kicker">Étape {{ $step }} sur 3</p>

        <div class="step-bar" role="progressbar" aria-valuenow="{{ $step }}"
             aria-valuemin="1" aria-valuemax="3"
             aria-label="Étape {{ $step }} sur 3">
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
                <a href="{{ $back }}" class="step-back">&larr; {{ $backLabel }}</a>
            @else
                <span></span>
            @endif

            <button type="submit" class="btn-pill btn-dark">{{ $submit }}</button>
        </div>
    </div>
</form>
