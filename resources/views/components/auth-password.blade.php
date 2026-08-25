{{--
  x-auth-password — champ mot de passe des écrans d'authentification.

      <x-auth-password name="password" label="Mot de passe" autocomplete="current-password" />
      <x-auth-password name="password" label="Nouveau mot de passe" :meter="true" />

  SANS JAVASCRIPT :
  · le champ reste type="password", donc masqué — l'œil ne fait rien mais
    ne trompe personne, la saisie et l'envoi fonctionnent ;
  · l'indicateur de robustesse porte [hidden] et n'apparaît pas : aucune place
    perdue, aucun cadre vide.

  La valeur n'est JAMAIS repositionnée : un mot de passe ne revient pas dans
  le HTML, même après une erreur de validation sur un autre champ.
--}}
{{-- Le libellé par défaut est résolu dans le corps, pas dans @props :
     une valeur par défaut de @props n'est évaluée qu'une fois par requête,
     ce qui suffit ici, mais la règle vaut pour tout le fichier. --}}
@props([
    'name' => 'password',
    'label' => null,

    /*
     | AUCUN marque-place par défaut, et surtout pas « •••••••• ».
     |
     | Huit puces dans un champ vide sont indiscernables d'un mot de passe de
     | huit caractères : l'utilisateur croit avoir saisi, envoie un champ vide,
     | et lit « Le champ mot de passe est obligatoire » devant ce qui ressemble
     | à un champ rempli. Le piège est encore plus net au retour d'une erreur,
     | puisque le mot de passe n'est jamais repositionné.
     |
     | Le libellé suffit à dire ce qu'on attend.
     */
    'placeholder' => null,
    'autocomplete' => 'current-password',
    'hint' => null,
    'meter' => false,
    'required' => true,
    'id' => null,
])

@php
    $intitule = $label ?? __('auth.champs.mot_de_passe');
    $fieldId = $id ?? 'a-'.str_replace(['[', ']', '.'], '-', $name);
    $enErreur = $errors->has($name);
    $decritPar = $enErreur ? $fieldId.'-err' : ($hint ? $fieldId.'-hint' : null);
@endphp

<div class="f">
    <label class="f__label" for="{{ $fieldId }}">
        {{ $intitule }}
        @if ($required)
            <span class="f__requis" aria-hidden="true">*</span>
        @endif
    </label>

    <span class="f__wrap">
        <input
            type="password"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            @class(['f__control', 'is-invalid' => $enErreur])
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            autocomplete="{{ $autocomplete }}"
            @if ($decritPar) aria-describedby="{{ $decritPar }}" @endif
            @if ($enErreur) aria-invalid="true" @endif
            @if ($meter) data-pw-meter-input @endif
            @required($required)
            @if ($required) aria-required="true" @endif
            {{ $attributes }}
        >

        @if ($enErreur)
            {{-- --decale : l'œil occupe déjà la droite du champ. --}}
            <span class="f__alert f__alert--decale" aria-hidden="true">
                <svg width="17" height="17" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                </svg>
            </span>
        @endif

        {{-- tabindex="-1" : le champ juste avant suffit au clavier, l'œil n'est
             qu'un raccourci de relecture à la souris. --}}
        <button type="button"
                class="f__eye"
                data-password-toggle="{{ $fieldId }}"
                aria-label="{{ __('auth.champs.afficher_mot_de_passe') }}"
                aria-pressed="false"
                tabindex="-1">
            <svg data-icon-show width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
            </svg>
            <svg data-icon-hide class="d-none" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
                <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
                <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
            </svg>
        </button>
    </span>

    @if ($meter)
        {{-- hidden est retiré par le module JavaScript. Sans lui, rien ne
             s'affiche : la robustesse n'est qu'une indication de confort. --}}
        <div class="pw-meter" data-pw-meter hidden>
            <div class="pw-meter__track" aria-hidden="true">
                <span class="pw-meter__seg"></span>
                <span class="pw-meter__seg"></span>
                <span class="pw-meter__seg"></span>
                <span class="pw-meter__seg"></span>
            </div>
            {{-- aria-live polite : annoncé sans couper la saisie en cours. --}}
            <span class="pw-meter__label" data-pw-meter-label aria-live="polite"></span>
        </div>
    @endif

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
