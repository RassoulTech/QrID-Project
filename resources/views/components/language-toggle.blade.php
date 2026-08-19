{{--
  x-language-toggle — bascule français / anglais.

      <x-language-toggle />

  MÊME MÉCANIQUE QUE LA BASCULE DE THÈME, et pour les mêmes raisons : un vrai
  formulaire POST, aucune ligne de JavaScript, la langue posée par le serveur
  avant le premier rendu. Une bascule faite après affichage laisserait voir la
  page dans l'ancienne langue puis la retraduirait sous les yeux.

  POST et non GET : changer de langue MODIFIE un état. Un lien serait suivi par
  les robots d'indexation et les préchargeurs de navigateur, qui basculeraient
  la langue de visiteurs n'ayant rien demandé.

  Accessible aux invités comme aux comptes : la route est hors du groupe
  « auth ». Pour un compte, la préférence est écrite en base — c'est elle qui
  sert aussi aux e-mails, qui partent hors session et n'ont aucun cookie à
  lire ; pour un invité, dans un cookie d'un an.

  LE BOUTON AFFICHE LA LANGUE COURANTE, pas la destination. C'est l'inverse de
  la bascule de thème, et c'est voulu : une lune dit « aller vers le sombre »
  sans ambiguïté, tandis qu'un « EN » isolé ne dit pas s'il désigne la langue
  affichée ou celle qu'on obtiendrait. Le libellé complet de la destination est
  donc porté par le title et l'aria-label.
--}}
@php
    $courante = App\Support\Langue::courante();
    $vers = App\Support\Langue::inverse();
@endphp

<form method="POST" action="{{ route('preferences.langue') }}"
      {{ $attributes->merge(['class' => 'langue-form']) }}>
    @csrf
    <input type="hidden" name="langue" value="{{ $vers }}">

    <button type="submit" class="langue-toggle"
            aria-label="{{ __('Passer en :langue', ['langue' => App\Support\Langue::libelle($vers)]) }}"
            title="{{ App\Support\Langue::libelle($vers) }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
        </svg>

        <span class="langue-toggle__code">{{ App\Support\Langue::code($courante) }}</span>
    </button>
</form>
