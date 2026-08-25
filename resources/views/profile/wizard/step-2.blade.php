{{-- ÉTAPE 2 — Comment vous joindre.
     Un seul champ obligatoire : le téléphone. --}}
@php
    // Lignes de réseaux à afficher : celles déjà saisies, sinon une vide.
    $socials = old('socials', $wizard->get('data.socials', []));

    if (! is_array($socials) || $socials === []) {
        $socials = [['platform' => '', 'url' => '']];
    }
@endphp

<x-app-layout :title="__('profile.wizard.titre_2')">
    <x-step-shell
        :step="2"
        :title="__('profile.wizard.entete_2')"
        :subtitle="__('profile.wizard.sous_2')"
        :action="route('profile.store.step2')"
        :back="route('profile.create.step1')"
    >
        {{-- L'indicatif suit le pays choisi : le numéro est enregistré au
             format international complet, quel qu'il soit. --}}
        <x-phone-field name="phone" :label="__('common.champs.telephone')"
                       :value="$wizard->field('phone')" />

        <x-phone-field name="whatsapp" :label="__('profile.fiche.whatsapp')" optional
                       :value="$wizard->field('whatsapp')" />

        {{-- Le lien exact plutôt qu'une recherche devinée : « Sacré-Cœur 3 »
             tombe dans un quartier, pas devant la boutique. --}}
        <x-field name="maps_url" :label="__('profile.wizard.localisation')" type="url" optional
                 placeholder="https://maps.app.goo.gl/..."
                 :hint="__('profile.wizard.localisation_aide')"
                 :value="$wizard->field('maps_url')" />

        <x-field name="public_email" :label="__('profile.wizard.email_public')" type="email" optional
                 :placeholder="__('profile.wizard.email_public_exemple')" autocomplete="email"
                 :hint="__('profile.wizard.email_public_aide')"
                 :value="$wizard->field('public_email')" />

        <x-field name="website" :label="__('common.champs.site_web')" type="url" optional
                 placeholder="exemple.sn" :value="$wizard->field('website')" />

        <x-field name="address" :label="__('common.champs.adresse')" optional maxlength="160"
                 :placeholder="__('profile.wizard.adresse_exemple')" :value="$wizard->field('address')" />

        {{-- RÉSEAUX SOCIAUX --------------------------------------------------
             Sans JavaScript : « Ajouter un réseau » soumet le formulaire, le
             serveur renvoie la même étape avec une ligne de plus ; la
             suppression vide la ligne, le serveur l'ignore à l'enregistrement.
             Avec JavaScript : lignes ajoutées et retirées sur place. --}}
        <div class="f">
            <span class="f__label">
                {{ __('profile.wizard.reseaux') }}
                <span class="f__opt">{{ __('common.champs.optionnel') }}</span>
            </span>

            <div class="socials" data-socials data-max="6">
                @foreach ($socials as $i => $social)
                    <div class="social-row" data-social-row>
                        <select name="socials[{{ $i }}][platform]"
                                class="f__control social-row__platform"
                                aria-label="{{ __('profile.wizard.reseau_aria', ['n' => $i + 1]) }}">
                            <option value="">{{ __('profile.wizard.reseau_choisir') }}</option>
                            @foreach ($platforms as $key => $name)
                                <option value="{{ $key }}"
                                    @selected(($social['platform'] ?? '') === $key)>{{ $name }}</option>
                            @endforeach
                        </select>

                        <input type="url" name="socials[{{ $i }}][url]"
                               value="{{ $social['url'] ?? '' }}"
                               class="f__control social-row__url"
                               placeholder="{{ __('profile.wizard.reseau_lien') }}"
                               aria-label="{{ __('profile.wizard.reseau_lien_aria', ['n' => $i + 1]) }}">

                        {{-- type="button" : ce bouton ne soumet jamais le formulaire.
                             Sans JavaScript il reste inerte ; vider les deux champs
                             suffit à retirer la ligne. --}}
                        <button type="button" class="social-row__del"
                                data-social-remove aria-label="{{ __('profile.wizard.reseau_retirer') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <path d="M6 6l12 12M18 6L6 18"/>
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>

            @error('socials')<span class="f__error">{{ $message }}</span>@enderror
            @foreach ($errors->get('socials.*') as $message)
                <span class="f__error">{{ $message[0] }}</span>
            @endforeach

            {{-- Bouton de repli serveur : masqué dès que le module JS démarre. --}}
            <button type="submit" name="action" value="add_social"
                    class="social-add" data-social-add>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                {{ __('profile.wizard.reseau_ajouter') }}
            </button>
        </div>

        {{-- Gabarit de ligne, cloné par le module. Ignoré sans JavaScript. --}}
        <template data-social-template>
            <div class="social-row" data-social-row>
                <select name="socials[__i__][platform]" class="f__control social-row__platform"
                        aria-label="{{ __('profile.wizard.reseau_aria_nu') }}">
                    <option value="">{{ __('profile.wizard.reseau_choisir') }}</option>
                    @foreach ($platforms as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
                <input type="url" name="socials[__i__][url]" class="f__control social-row__url"
                       placeholder="{{ __('profile.wizard.reseau_lien') }}"
                       aria-label="{{ __('profile.wizard.reseau_lien_aria_nu') }}">
                <button type="button" class="social-row__del" data-social-remove
                        aria-label="{{ __('profile.wizard.reseau_retirer') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>
        </template>
    </x-step-shell>
</x-app-layout>
