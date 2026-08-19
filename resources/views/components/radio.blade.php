{{--
  x-radio — groupe de boutons radio.

  <x-radio name="paiement" label="Moyen de paiement"
           :options="['wave' => 'Wave', 'om' => 'Orange Money', 'free' => 'Free Money']"
           :required="true" />

  Props : name, label (légende du groupe), options, selected, required,
          inline, help, errorBag
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'inline' => false,
    'help' => null,
    'errorBag' => null,
])

@php
    $bag = $errorBag ? $errors->{$errorBag} : $errors;
    $hasError = $bag->has($name);
    $current = old($name, $selected);
@endphp

<fieldset class="mb-3">
    @if ($label)
        <legend class="form-label fs-6 mb-2">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true">&nbsp;*</span>@endif
        </legend>
    @endif

    @foreach ($options as $optionValue => $optionLabel)
        @php $optionId = $name.'_'.\Illuminate\Support\Str::slug((string) $optionValue); @endphp

        <div class="form-check {{ $inline ? 'form-check-inline' : '' }}">
            <input
                type="radio"
                id="{{ $optionId }}"
                name="{{ $name }}"
                value="{{ $optionValue }}"
                @checked((string) $current === (string) $optionValue)
                @required($required)
        @if ($required) aria-required="true" @endif
                class="form-check-input {{ $hasError ? 'is-invalid' : '' }}"
            >
            <label class="form-check-label" for="{{ $optionId }}">{{ $optionLabel }}</label>
        </div>
    @endforeach

    @if ($help && ! $hasError)
        <div class="form-text">{{ $help }}</div>
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block">{{ $bag->first($name) }}</div>
    @endif
</fieldset>
