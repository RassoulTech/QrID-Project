@props([
    'name' => 'password',
    'label' => 'Mot de passe',
    // Jamais « •••••••• » : huit puces dans un champ VIDE sont indiscernables
    // d'un mot de passe de huit caractères. L'utilisateur croit avoir saisi,
    // envoie un champ vide, et ne comprend pas le message d'erreur.
    'placeholder' => null,
    'autocomplete' => 'current-password',
    'required' => true,
    'help' => null,
    'errorBag' => null,   // ex. 'updatePassword' pour les formulaires du profil
    'id' => null,
])

@php
    $fieldId = $id ?? $name;
    $bag = $errorBag ? $errors->{$errorBag} : $errors;
    $hasError = $bag->has($name);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $fieldId }}" class="form-label fw-medium">
            {{ $label }}@if ($required)<span class="text-danger">&nbsp;*</span>@endif
        </label>
    @endif

    <div class="input-group">
        {{-- Le champ reste type="password" : sans JavaScript, il demeure masqué. --}}
        <input
            type="password"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            autocomplete="{{ $autocomplete }}"
            @required($required)
        @if ($required) aria-required="true" @endif
            {{ $attributes->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
        >

        <button
            type="button"
            class="btn btn-outline-secondary"
            data-password-toggle="{{ $fieldId }}"
            aria-label="{{ __('common.champs.afficher_mot_de_passe') }}"
            aria-pressed="false"
            tabindex="-1"
        >
            {{-- Œil ouvert : état masqué, cliquer pour afficher --}}
            <svg data-icon-show xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                 viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
            </svg>

            {{-- Œil barré : état affiché, cliquer pour masquer --}}
            <svg data-icon-hide class="d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                 viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
                <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
                <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
            </svg>
        </button>

        @if ($hasError)
            <div class="invalid-feedback">{{ $bag->first($name) }}</div>
        @endif
    </div>

    @if ($help && ! $hasError)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
