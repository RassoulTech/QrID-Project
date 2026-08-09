{{--
  x-checkbox — case à cocher unique.

  <x-checkbox name="cgv" label="J'accepte les conditions générales" :required="true" />
  <x-checkbox name="remember" label="Se souvenir de moi" :switch="true" />

  Props : name, label, value, checked, required, help, switch, errorBag
  Note : un champ caché à 0 est émis pour que la valeur décochée soit
  transmise au serveur (sinon rien n'est envoyé).
--}}
@props([
    'name',
    'label',
    'value' => 1,
    'checked' => false,
    'required' => false,
    'help' => null,
    'switch' => false,
    'errorBag' => null,
    'id' => null,
    'hidden' => true,
])

@php
    $fieldId = $id ?? $name;
    $bag = $errorBag ? $errors->{$errorBag} : $errors;
    $hasError = $bag->has($name);
    $isChecked = (bool) old($name, $checked);
@endphp

<div class="mb-3">
    @if ($hidden)
        <input type="hidden" name="{{ $name }}" value="0">
    @endif

    <div class="form-check {{ $switch ? 'form-switch' : '' }}">
        <input
            type="checkbox"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @checked($isChecked)
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            @required($required)
            {{ $attributes->merge(['class' => 'form-check-input' . ($hasError ? ' is-invalid' : '')]) }}
        >

        <label class="form-check-label" for="{{ $fieldId }}">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true">&nbsp;*</span>@endif
        </label>
    </div>

    @if ($help && ! $hasError)
        <div class="form-text">{{ $help }}</div>
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block" id="{{ $fieldId }}-error">{{ $bag->first($name) }}</div>
    @endif
</div>
