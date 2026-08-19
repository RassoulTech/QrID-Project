{{--
  x-select — liste déroulante.

  <x-select name="plan" label="Formule" :options="['mensuel' => 'Mensuel', 'annuel' => 'Annuel']"
            placeholder="Choisir une formule" :required="true" />

  Props : name, label, options (tableau valeur => libellé), selected,
          placeholder, required, optional, help, errorBag
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'errorBag' => null,
    'id' => null,
])

@php
    $fieldId = $id ?? $name;
    $bag = $errorBag ? $errors->{$errorBag} : $errors;
    $hasError = $bag->has($name);
    $current = old($name, $selected);
    $describedBy = $hasError ? "{$fieldId}-error" : ($help ? "{$fieldId}-help" : null);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $fieldId }}" class="form-label">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true">&nbsp;*</span>@endif
            @if ($optional)<span class="optional">(facultatif)</span>@endif
        </label>
    @endif

    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        @required($required)
        @if ($required) aria-required="true" @endif
        {{ $attributes->merge(['class' => 'form-select' . ($hasError ? ' is-invalid' : '')]) }}
    >
        @if ($placeholder)
            <option value="" @selected($current === null || $current === '')>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($help && ! $hasError)
        <div class="form-text" id="{{ $fieldId }}-help">{{ $help }}</div>
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block" id="{{ $fieldId }}-error">{{ $bag->first($name) }}</div>
    @endif
</div>
