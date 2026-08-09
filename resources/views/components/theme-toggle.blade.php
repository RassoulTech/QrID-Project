{{--
  x-theme-toggle — bascule clair / sombre.

      <x-theme-toggle />

  UN VRAI FORMULAIRE POST, pas un lien ni un bouton JavaScript. La page se
  recharge et le serveur repose la classe sur <html> : zéro ligne de script,
  donc zéro écran resté clair parce qu'un fichier JS n'a pas chargé.

  Accessible aux invités comme aux comptes : la route est hors du groupe
  « auth ». Pour un compte, la préférence est écrite en base ; pour un
  invité, dans un cookie d'un an.

  L'icône montre la DESTINATION, pas l'état courant : en thème clair on
  affiche la lune, parce que c'est là qu'on va en cliquant.
--}}
@php
    $sombre = App\Support\Theme::estSombre();
@endphp

<form method="POST" action="{{ route('preferences.theme') }}"
      {{ $attributes->merge(['class' => 'theme-form']) }}>
    @csrf
    <input type="hidden" name="theme" value="{{ App\Support\Theme::inverse() }}">

    <button type="submit" class="theme-toggle"
            aria-label="{{ $sombre ? 'Passer en thème clair' : 'Passer en thème sombre' }}"
            title="{{ $sombre ? 'Thème clair' : 'Thème sombre' }}">
        @if ($sombre)
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708"/>
            </svg>
        @else
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
            </svg>
        @endif
    </button>
</form>
