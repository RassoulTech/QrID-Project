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
  SELECT NATIF, AUCUN JAVASCRIPT OBLIGATOIRE
  ═══════════════════════════════════════════════════════════════════════
  Sur téléphone, le sélecteur natif est un panneau plein écran ou une roue —
  bien meilleurs que toute liste redessinée, et utilisables au clavier comme
  au lecteur d'écran. Le champ fonctionne donc sans une ligne de script :
  le drapeau, la longueur maximale et l'aide sont déjà justes au rendu
  serveur. Le module telephone.js ne fait que les SUIVRE quand on change de
  pays, et espacer les chiffres pendant la frappe.

  ═══════════════════════════════════════════════════════════════════════
  MOBILE FIRST
  ═══════════════════════════════════════════════════════════════════════
  En dessous de 640px, le pays passe AU-DESSUS du numéro plutôt qu'à côté :
  à 320px, deux contrôles côte à côte laissaient au numéro moins de la
  moitié de l'écran, soit quatre chiffres visibles sur neuf.

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

    if (! \App\Support\IndicatifsPays::existe($codePays)) {
        $codePays = \App\Support\IndicatifsPays::DEFAUT;
    }

    $erreur = $errors->has($name) || $errors->has($champPays);
    $gabarit = \App\Support\IndicatifsPays::gabarit($codePays);

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

    // L'aide du gabarit ne remplace jamais celle qu'un écran a écrite : elle
    // la complète, et ne s'affiche seule que s'il n'y en a pas.
    $aide = $help ?: $gabarit['aide'];
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

    <div class="tel" data-tel data-gabarits="{{ \App\Support\IndicatifsPays::gabaritsJson() }}">
        {{-- Le drapeau est un SVG posé PAR-DESSUS le sélecteur : un <option>
             ne peut contenir que du texte, et l'émoji drapeau se dégrade en
             deux lettres grises sur Windows. --}}
        <div class="tel__pays-boite">
            <span class="tel__drapeau" data-tel-drapeau>
                <x-drapeau :code="$codePays" :taille="22" />
            </span>

            <select id="{{ $champPays }}" name="{{ $champPays }}"
                    class="f__control tel__pays" data-tel-pays
                    aria-label="{{ __('common.champs.indicatif') }}">
                @foreach (\App\Support\IndicatifsPays::options() as $code => $libelle)
                    <option value="{{ $code }}" @selected($codePays === $code)>{{ $libelle }}</option>
                @endforeach
            </select>

            <span class="tel__chevron" aria-hidden="true">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </span>
        </div>

        {{-- inputmode="numeric" et non "tel" : le clavier « tel » ouvre le
             pavé d'appel avec *, # et +, dont aucun n'a sa place ici puisque
             l'indicatif est déjà choisi à côté. --}}
        <input type="tel"
               id="{{ $name }}"
               name="{{ $name }}"
               value="{{ old($name, $national) }}"
               @class(['f__control', 'tel__numero', 'is-invalid' => $erreur])
               data-tel-numero
               inputmode="numeric"
               autocomplete="tel-national"
               maxlength="{{ $gabarit['max'] + count($gabarit['groupes']) - 1 }}"
               placeholder="{{ $gabarit['exemple'] }}"
               @if ($required && ! $optional) required aria-required="true" @endif
               aria-describedby="{{ $name }}-aide{{ $erreur ? ' '.$name.'-err' : '' }}"
               @if ($erreur) aria-invalid="true" @endif>
    </div>

    {{-- L'aide annonce le format AVANT la faute, pas après : « 9 chiffres
         après +221 » évite l'aller-retour d'un formulaire refusé. --}}
    {{-- « fige » : une aide écrite par un écran (« pour la livraison »)
         reste la sienne. Seule l'aide générée par le gabarit se met à jour
         quand on change de pays. --}}
    <p class="f__help" id="{{ $name }}-aide"
       data-tel-aide="{{ $help ? 'fige' : 'auto' }}">{{ $aide }}</p>

    @error($name)
        <p class="f__error" id="{{ $name }}-err">{{ $message }}</p>
    @enderror

    @error($champPays)
        <p class="f__error">{{ $message }}</p>
    @enderror
</div>
