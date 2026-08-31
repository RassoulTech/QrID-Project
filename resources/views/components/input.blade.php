{{--
  x-input — LE CHAMP CANONIQUE.

      <x-input name="email" type="email" :label="__('auth.champs.email')" />
      <x-input name="ville" :label="__('profile.champs.ville')" :optional="true" />

  Props : name, label, type, value, placeholder, optional, autocomplete,
          inputmode, help, errorBag, id, maxlength

  ═══════════════════════════════════════════════════════════════════════
  CE QU'IL NE PORTE PLUS
  ═══════════════════════════════════════════════════════════════════════
  Il rendait du Bootstrap nu : `.mb-3`, `.form-label`, `.form-text`,
  `.invalid-feedback d-block`. Trois conséquences, toutes réelles.

  · `mb-3` FIXAIT LE RYTHME DES FORMULAIRES DU PRODUIT ENTIER depuis un
    utilitaire de framework — 16px là où l'échelle impose esp(5) = 20px
    entre deux groupes.
  · `d-block` était un contournement : il forçait l'affichage d'un
    `.invalid-feedback` que Bootstrap masque par défaut. Un contournement
    signale toujours qu'on se bat contre l'outil.
  · `(facultatif)` était écrit en clair, donc jamais traduit. La version
    anglaise affichait « (facultatif) ».

  L'anatomie est maintenant dans `x-champ`, et ce composant ne porte plus
  que son contrôle.

  ═══════════════════════════════════════════════════════════════════════
  `required` N'EST PLUS UNE PROP
  ═══════════════════════════════════════════════════════════════════════
  Il vient de la règle de validation. Voir `x-champ` et
  `App\Support\Design\Champs`.
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'optional' => false,
    'autocomplete' => null,
    'inputmode' => null,
    'maxlength' => null,
    'help' => null,
    'errorBag' => null,
    'id' => null,
])

@php
    $champId = $id ?? $name;
    $sac = $errorBag ? $errors->{$errorBag} : $errors;
    $enErreur = $sac->has($name);
    $obligatoire = App\Support\Design\Champs::gouverne()
        ? App\Support\Design\Champs::estObligatoire($name)
        : false;

    $decritPar = $enErreur
        ? $champId.'-erreur'
        : ($help ? $champId.'-aide' : null);

    /* LE CLAVIER MOBILE SUIT LE TYPE, sans qu'on ait à y penser.
       Un champ e-mail qui ouvre le clavier alphabétique complet oblige à
       chercher l'arobase ; un champ téléphone qui n'ouvre pas le pavé
       numérique fait saisir un numéro lettre par lettre. */
    $clavier = $inputmode ?? match ($type) {
        'email' => 'email',
        'tel' => 'tel',
        'url' => 'url',
        'number' => 'numeric',
        default => null,
    };
@endphp

<x-champ
    :name="$name"
    :label="$label"
    :optional="$optional"
    :help="$help"
    :error-bag="$errorBag"
    :id="$champId"
>
    <input
        type="{{ $type }}"
        id="{{ $champId }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($clavier) inputmode="{{ $clavier }}" @endif
        @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        @if ($obligatoire) required aria-required="true" @endif
        @if ($decritPar) aria-describedby="{{ $decritPar }}" @endif
        @if ($enErreur) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'f__control'.($enErreur ? ' is-invalid' : '')]) }}
    >
</x-champ>
