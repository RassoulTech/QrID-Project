{{--
  x-auth-field — un champ des écrans d'authentification.

      <x-auth-field name="email" type="email" label="Adresse e-mail"
                    placeholder="vous@exemple.sn" autocomplete="username" autofocus />

  ÉTAT D'ERREUR (maquette « Erreur de saisie ») :
  bordure rouge + triangle d'alerte dans le champ + message explicite SOUS le
  champ concerné. Jamais de récapitulatif d'erreurs en haut de formulaire :
  l'utilisateur doit lire le message à l'endroit où il doit corriger.

  Réutilise .f / .f__label / .f__control / .f__error du parcours de création.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'autocomplete' => null,
    'inputmode' => null,
    'maxlength' => null,
    'hint' => null,
    'required' => true,
    'id' => null,
])

@php
    $fieldId = $id ?? 'a-'.str_replace(['[', ']', '.'], '-', $name);
    $enErreur = $errors->has($name);

    // Un champ en erreur décrit son message ; sinon il décrit son indice.
    $decritPar = $enErreur ? $fieldId.'-err' : ($hint ? $fieldId.'-hint' : null);
@endphp

<div class="f">
    <label class="f__label" for="{{ $fieldId }}">
        {{ $label }}
        @if ($required)
            <span class="f__requis" aria-hidden="true">*</span>
        @else
            <span class="f__opt">optionnel</span>
        @endif
    </label>

    <span class="f__wrap">
        <input
            type="{{ $type }}"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @class(['f__control', 'is-invalid' => $enErreur])
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @if ($maxlength) maxlength="{{ $maxlength }}" @endif
            @if ($decritPar) aria-describedby="{{ $decritPar }}" @endif
            @if ($enErreur) aria-invalid="true" @endif
            @required($required)
            @if ($required) aria-required="true" @endif
            {{ $attributes }}
        >

        @if ($enErreur)
            {{-- Décorative : le message écrit juste dessous porte l'information. --}}
            <span class="f__alert" aria-hidden="true">
                <svg width="17" height="17" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                </svg>
            </span>
        @endif
    </span>

    @if ($hint && ! $enErreur)
        <span class="f__hint" id="{{ $fieldId }}-hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="f__error" id="{{ $fieldId }}-err">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" style="flex:0 0 auto;margin-top:2px">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
            </svg>
            <span>{{ $message }}</span>
        </span>
    @enderror
</div>
