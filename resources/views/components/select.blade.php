{{--
  x-select — LA LISTE DÉROULANTE.

      <x-select name="plan" :label="__('payment.champs.formule')"
                :options="$formules" :placeholder="__('common.actions.choisir')" />

      Avec des groupes — le sélecteur d'indicatif met l'Afrique de
      l'Ouest en tête, ce qui est exactement un cas d'optgroup :

      <x-select name="pays" :groupes="$parRegion" />

  Props : name, label, options, groupes, selected, placeholder, optional,
          help, errorBag, id

  ═══════════════════════════════════════════════════════════════════════
  TROIS PIÈGES DÉJÀ RÉGLÉS PAR LE SOCLE — NE PAS LES DÉFAIRE
  ═══════════════════════════════════════════════════════════════════════
  1. LA FLÈCHE NATIVE EST REMPLACÉE par un chevron déclaré PAR THÈME. Sur
     Windows, la flèche du système reste noire sur fond sombre. Il faut
     DEUX images encodées : une image ne peut pas lire une propriété CSS.

  2. `option` REÇOIT UN `background` ET UN `color` EXPLICITES. Sans ces
     deux lignes, une liste ouverte en thème sombre s'affiche clair sur
     clair sous Windows. Les options sont dessinées par le système : on ne
     peut leur imposer que ces deux propriétés, et il faut le faire.

  3. LE SELECT RESTE NATIF. Sur téléphone, le sélecteur du système est un
     panneau plein écran ou une roue — meilleur que toute liste réécrite,
     et utilisable au clavier comme au lecteur d'écran. Aucune liste
     déroulante maison, nulle part.

  ═══════════════════════════════════════════════════════════════════════
  CE QUI CHANGE ICI
  ═══════════════════════════════════════════════════════════════════════
  · L'anatomie passe par `x-champ` : plus de `.mb-3`, plus de
    `.form-label`, plus de `.invalid-feedback d-block`.
  · « (facultatif) » était écrit en clair, donc jamais traduit.
  · L'astérisque vient de la règle de validation, plus de la vue.
  · Les `optgroup` sont enfin possibles, et habillés.
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'groupes' => [],
    'selected' => null,
    'placeholder' => null,
    'optional' => false,
    'help' => null,
    'errorBag' => null,
    'id' => null,
])

@php
    $champId = $id ?? $name;
    $sac = $errorBag ? $errors->{$errorBag} : $errors;
    $enErreur = $sac->has($name);
    $courant = old($name, $selected);
    $obligatoire = App\Support\Design\Champs::gouverne()
        ? App\Support\Design\Champs::estObligatoire($name)
        : false;

    $decritPar = $enErreur
        ? $champId.'-erreur'
        : ($help ? $champId.'-aide' : null);
@endphp

<x-champ
    :name="$name"
    :label="$label"
    :optional="$optional"
    :help="$help"
    :error-bag="$errorBag"
    :id="$champId"
>
    <select
        id="{{ $champId }}"
        name="{{ $name }}"
        @if ($obligatoire) required aria-required="true" @endif
        @if ($decritPar) aria-describedby="{{ $decritPar }}" @endif
        @if ($enErreur) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'f__select'.($enErreur ? ' is-invalid' : '')]) }}
    >
        @if ($placeholder)
            {{-- Le marque-place est DÉSACTIVÉ une fois qu'on a choisi :
                 il nomme l'action, il n'est pas une réponse valable. --}}
            <option value="" disabled @selected($courant === null || $courant === '')>
                {{ $placeholder }}
            </option>
        @endif

        @foreach ($options as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected((string) $courant === (string) $valeur)>
                {{ $libelle }}
            </option>
        @endforeach

        @foreach ($groupes as $titre => $entrees)
            <optgroup label="{{ $titre }}">
                @foreach ($entrees as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected((string) $courant === (string) $valeur)>
                        {{ $libelle }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach

        {{ $slot }}
    </select>
</x-champ>
