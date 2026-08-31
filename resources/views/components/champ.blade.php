{{--
  x-champ — L'ANATOMIE D'UN CHAMP, DÉCRITE UNE SEULE FOIS.

      <x-champ name="email" :label="__('auth.champs.email')">
          <input type="email" id="email" name="email" class="f__control">
      </x-champ>

  ═══════════════════════════════════════════════════════════════════════
  CE QU'IL SUPPRIME
  ═══════════════════════════════════════════════════════════════════════
  L'audit a compté QUATORZE racines CSS distinctes pour le champ de
  formulaire, et trois systèmes parallèles — public (`.f__*`),
  authentification (`.auth-*`), administration (`.adm-champ`). Une
  correction de hauteur tactile devait être faite trois fois.

  Le balisage vivait lui aussi en trois exemplaires : `x-input` rendait du
  Bootstrap nu (`.mb-3`, `.form-label`, `.invalid-feedback d-block`),
  `x-field` rendait `.f__*`, et les vues d'administration écrivaient leur
  champ à la main.

  Ce composant porte l'anatomie ; les autres ne portent plus que leur
  contrôle.

  ═══════════════════════════════════════════════════════════════════════
  L'ANATOMIE, DANS L'ORDRE
  ═══════════════════════════════════════════════════════════════════════
      [ libellé ]  [ * rouge si requis ]  [ « optionnel » gris sinon ]
                        ↕ esp(2)
      [ le contrôle — 48px de haut, 16px de police ]
                        ↕ esp(2)
      [ aide en --texte-3   OU   erreur en --danger ]

  ═══════════════════════════════════════════════════════════════════════
  L'ASTÉRISQUE N'EST JAMAIS ÉCRIT DANS UNE VUE
  ═══════════════════════════════════════════════════════════════════════
  Il vient de la règle de validation, via `App\Support\Design\Champs`.
  Passé à la main, rien ne garantissait qu'il corresponde au serveur : un
  champ obligatoire pouvait s'afficher facultatif, l'utilisateur
  l'ignorait, et découvrait l'erreur après envoi.

  `required` reste acceptable en secours — pour un formulaire dont aucune
  FormRequest n'a été déclarée — mais la règle prime toujours.

  ═══════════════════════════════════════════════════════════════════════
  L'ERREUR RESTE SOUS SON CHAMP
  ═══════════════════════════════════════════════════════════════════════
  Jamais regroupée en haut de page. Un message loin de sa cause oblige à
  chercher lequel des douze champs est concerné, et cette recherche est
  exactement ce qui fait abandonner un formulaire.
--}}
@props([
    'name',
    'label' => null,
    'required' => null,
    'optional' => false,
    'help' => null,
    'errorBag' => null,
    'id' => null,
])

@php
    $champId = $id ?? $name;
    $sac = $errorBag ? $errors->{$errorBag} : $errors;
    $enErreur = $sac->has($name);

    /* LA RÈGLE DE VALIDATION PRIME SUR CE QUE LA VUE AFFIRME.
       Si aucune n'est déclarée, on retombe sur la prop — un formulaire
       sans FormRequest doit rester affichable. */
    $obligatoire = App\Support\Design\Champs::gouverne()
        ? App\Support\Design\Champs::estObligatoire($name)
        : (bool) $required;

    $decritPar = $enErreur
        ? $champId.'-erreur'
        : ($help ? $champId.'-aide' : null);
@endphp

<div class="f" data-champ="{{ $name }}">
    @if ($label)
        <label class="f__label" for="{{ $champId }}">
            {{ $label }}

            @if ($obligatoire)
                {{-- aria-hidden : le lecteur d'écran l'apprend de
                     `aria-required` sur le contrôle, pas d'un signe
                     typographique qu'il énoncerait « astérisque ». --}}
                <span class="f__requis" aria-hidden="true">*</span>
            @elseif ($optional)
                <span class="f__opt">{{ __('common.champs.optionnel') }}</span>
            @endif
        </label>
    @endif

    {{-- Le contrôle reçoit son identifiant, son état et sa description. --}}
    {{ $slot }}

    @if ($enErreur)
        <p class="f__error" id="{{ $champId }}-erreur">{{ $sac->first($name) }}</p>
    @elseif ($help)
        <p class="f__hint" id="{{ $champId }}-aide">{{ $help }}</p>
    @endif
</div>
