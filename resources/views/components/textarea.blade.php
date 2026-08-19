{{--
  x-textarea — champ de saisie multiligne.

  <x-textarea name="bio" label="Présentation" :rows="5" :maxlength="500"
              help="500 caractères maximum." />

  Props : name, label, value, placeholder, rows, required, optional,
          maxlength, help, errorBag
--}}
@props([
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 4,
    'required' => false,
    'optional' => false,
    'maxlength' => null,
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

    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        @required($required)
        @if ($required) aria-required="true" @endif
        {{ $attributes->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($help && ! $hasError)
        <div class="form-text" id="{{ $fieldId }}-help">{{ $help }}</div>
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block" id="{{ $fieldId }}-error">{{ $bag->first($name) }}</div>
    @endif
</div>
