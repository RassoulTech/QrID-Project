{{--
  x-field — un champ du parcours de création.

  <x-field name="first_name" label="Prénom" :value="$wizard->field('first_name')" required />
  <x-field name="company" label="Entreprise" :value="..." optional />

  La mention « optionnel » est portée par le libellé, pas par un astérisque :
  on indique ce qu'on peut sauter, pas ce qu'on doit remplir.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'optional' => false,
    'hint' => null,
    'placeholder' => null,
    'autocomplete' => null,
    'inputmode' => null,
    'maxlength' => null,
])

@php $id = 'f-'.str_replace(['[', ']', '.'], '-', $name); @endphp

<div class="f">
    {{-- L'ASTERISQUE EST DEDUIT, JAMAIS ECRIT A LA MAIN.
         Ce champ est requis SAUF s'il est declare optionnel : la marque suit
         donc exactement l'attribut required pose plus bas, et les deux ne
         peuvent pas se contredire. --}}
    <label class="f__label" for="{{ $id }}">
        {{ $label }}
        @if ($optional)
            <span class="f__opt">optionnel</span>
        @else
            <span class="f__requis" aria-hidden="true">*</span>
        @endif
    </label>

    <input
        type="{{ $type }}"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        @class(['f__control', 'is-invalid' => $errors->has($name)])
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        @if (! $optional) required aria-required="true" @endif
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-err" @endif
        {{ $attributes }}
    >

    @if ($hint && ! $errors->has($name))
        <span class="f__hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="f__error" id="{{ $id }}-err">{{ $message }}</span>
    @enderror
</div>
