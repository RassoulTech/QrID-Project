{{--
  x-phone-field — un numéro de téléphone avec son indicatif pays.

      <x-phone-field name="phone" label="Téléphone" :value="old('phone')" />

  ═══════════════════════════════════════════════════════════════════════
  UN SEUL COMPOSANT, PARTOUT
  ═══════════════════════════════════════════════════════════════════════
  Inscription, création de profil, compte, adresse de livraison : le même
  rendu et la même validation. Le préfixe « +221 » figé rendait le produit
  inutilisable pour un client ivoirien ou un Sénégalais de la diaspora — et
  chaque écran l'écrivait à sa façon.

  ═══════════════════════════════════════════════════════════════════════
  SELECT NATIF, AUCUN JAVASCRIPT
  ═══════════════════════════════════════════════════════════════════════
  Sur téléphone, le sélecteur natif est un panneau plein écran ou une roue —
  bien meilleurs que toute liste redessinée, et utilisables au clavier comme
  au lecteur d'écran. Le composant fonctionne donc sans une ligne de script.

  Props : name, label, value, pays, required, optional, help
--}}
@props([
    'name' => 'phone',
    'label' => 'Téléphone',
    'value' => null,
    'pays' => null,
    'required' => true,
    'optional' => false,
    'help' => null,
])

@php
    $champPays = $name.'_pays';
    $codePays = old($champPays, $pays ?: \App\Support\IndicatifsPays::DEFAUT);
    $erreur = $errors->has($name) || $errors->has($champPays);

    /*
     | LE NUMÉRO EST RÉAFFICHÉ SANS SON INDICATIF.
     |
     | Il est stocké au format international complet. Le remettre tel quel dans
     | le champ ferait ressaisir « +221 » devant un sélecteur qui l'affiche
     | déjà : le client corrigerait à la main, et on obtiendrait « +221+221… ».
     */
    $national = $value;

    if ($national && \App\Support\IndicatifsPays::existe($codePays)) {
        $indicatif = \App\Support\IndicatifsPays::indicatif($codePays);

        if ($indicatif && str_starts_with((string) $national, $indicatif)) {
            $national = mb_substr((string) $national, mb_strlen($indicatif));
        }
    }
@endphp

<div class="f">
    <label class="f__label" for="{{ $name }}">
        {{ $label }}
        @if ($optional)
            <span class="f__opt">optionnel</span>
        @elseif ($required)
            <span class="f__requis" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="tel">
        <select id="{{ $champPays }}" name="{{ $champPays }}"
                class="f__control tel__pays" aria-label="Indicatif du pays">
            @foreach (\App\Support\IndicatifsPays::options() as $code => $libelle)
                <option value="{{ $code }}" @selected($codePays === $code)>{{ $libelle }}</option>
            @endforeach
        </select>

        <input type="tel"
               id="{{ $name }}"
               name="{{ $name }}"
               value="{{ old($name, $national) }}"
               @class(['f__control', 'tel__numero', 'is-invalid' => $erreur])
               inputmode="tel"
               autocomplete="tel-national"
               placeholder="77 383 13 64"
               @if ($required && ! $optional) required aria-required="true" @endif
               @if ($erreur) aria-invalid="true" aria-describedby="{{ $name }}-err" @endif>
    </div>

    @if ($help)
        <p class="f__help">{{ $help }}</p>
    @endif

    @error($name)
        <p class="f__error" id="{{ $name }}-err">{{ $message }}</p>
    @enderror

    @error($champPays)
        <p class="f__error">{{ $message }}</p>
    @enderror
</div>
