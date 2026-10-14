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
            aria-label="{{ $sombre ? __('common.theme.vers_clair') : __('common.theme.vers_sombre') }}"
            title="{{ $sombre ? __('common.theme.clair') : __('common.theme.sombre') }}">
        {{-- L'icône montre la DESTINATION : le soleil en sombre, la lune en clair. --}}
        <x-icone :name="$sombre ? 'sun' : 'moon'" />
    </button>
</form>
