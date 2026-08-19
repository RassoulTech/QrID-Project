{{--
  x-input — champ de saisie sur une ligne.

  <x-input name="email" type="email" label="Adresse e-mail" :required="true" />
  <x-input name="ville" label="Ville" :optional="true" help="Facultatif." />

  Props : name, label, type, value, placeholder, required, optional,
          autocomplete, help, inputmode, errorBag
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'optional' => false,
    'autocomplete' => null,
    'inputmode' => null,
    'help' => null,
    'errorBag' => null,
    'id' => null,
])

@php
    $fieldId = $id ?? $name;
    $bag = $errorBag ? $errors->{$errorBag} : $errors;
    $hasError = $bag->has($name);
    $describedBy = $hasError ? "{$fieldId}-error" : ($help ? "{$fieldId}-help" : null);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $fieldId }}" class="form-label">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true">&nbsp;*</span>@endif
            @if ($optional)<span class="optional">(facultatif)</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        id="{{ $fieldId }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        @required($required)
        @if ($required) aria-required="true" @endif
        {{ $attributes->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
    >

    @if ($help && ! $hasError)
        <div class="form-text" id="{{ $fieldId }}-help">{{ $help }}</div>
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block" id="{{ $fieldId }}-error">{{ $bag->first($name) }}</div>
    @endif
</div>
