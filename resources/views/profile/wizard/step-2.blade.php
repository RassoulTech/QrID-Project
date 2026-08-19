{{-- ÉTAPE 2 — Comment vous joindre.
     Un seul champ obligatoire : le téléphone. --}}
@php
    // Lignes de réseaux à afficher : celles déjà saisies, sinon une vide.
    $socials = old('socials', $wizard->get('data.socials', []));

    if (! is_array($socials) || $socials === []) {
        $socials = [['platform' => '', 'url' => '']];
    }
@endphp

<x-app-layout title="Créer mon profil — étape 2">
    <x-step-shell
        :step="2"
        title="Comment vous joindre ?"
        subtitle="Seul le téléphone est nécessaire. Vous compléterez le reste plus tard."
        :action="route('profile.store.step2')"
        :back="route('profile.create.step1')"
    >
        {{-- L'indicatif suit le pays choisi : le numéro est enregistré au
             format international complet, quel qu'il soit. --}}
        <x-phone-field name="phone" label="Téléphone"
                       :value="$wizard->field('phone')" />

        <x-phone-field name="whatsapp" label="WhatsApp" optional
                       :value="$wizard->field('whatsapp')" />

        <x-field name="public_email" label="E-mail public" type="email" optional
                 placeholder="contact@exemple.sn" autocomplete="email"
                 hint="Affiché sur votre profil. Différent de votre e-mail de connexion."
                 :value="$wizard->field('public_email')" />

        <x-field name="website" label="Site web" type="url" optional
                 placeholder="exemple.sn" :value="$wizard->field('website')" />

        <x-field name="address" label="Adresse" optional maxlength="160"
                 placeholder="Sacré-Cœur 3, Dakar" :value="$wizard->field('address')" />

        {{-- RÉSEAUX SOCIAUX --------------------------------------------------
             Sans JavaScript : « Ajouter un réseau » soumet le formulaire, le
             serveur renvoie la même étape avec une ligne de plus ; la
             suppression vide la ligne, le serveur l'ignore à l'enregistrement.
             Avec JavaScript : lignes ajoutées et retirées sur place. --}}
        <div class="f">
            <span class="f__label">
                Réseaux sociaux
                <span class="f__opt">optionnel</span>
            </span>

            <div class="socials" data-socials data-max="6">
                @foreach ($socials as $i => $social)
                    <div class="social-row" data-social-row>
                        <select name="socials[{{ $i }}][platform]"
                                class="f__control social-row__platform"
                                aria-label="Réseau social {{ $i + 1 }}">
                            <option value="">Choisir…</option>
                            @foreach ($platforms as $key => $name)
                                <option value="{{ $key }}"
                                    @selected(($social['platform'] ?? '') === $key)>{{ $name }}</option>
                            @endforeach
                        </select>

                        <input type="url" name="socials[{{ $i }}][url]"
                               value="{{ $social['url'] ?? '' }}"
                               class="f__control social-row__url"
                               placeholder="Lien vers votre page"
                               aria-label="Lien du réseau {{ $i + 1 }}">

                        {{-- type="button" : ce bouton ne soumet jamais le formulaire.
                             Sans JavaScript il reste inerte ; vider les deux champs
                             suffit à retirer la ligne. --}}
                        <button type="button" class="social-row__del"
                                data-social-remove aria-label="Retirer ce réseau">
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
                Ajouter un réseau
            </button>
        </div>

        {{-- Gabarit de ligne, cloné par le module. Ignoré sans JavaScript. --}}
        <template data-social-template>
            <div class="social-row" data-social-row>
                <select name="socials[__i__][platform]" class="f__control social-row__platform"
                        aria-label="Réseau social">
                    <option value="">Choisir…</option>
                    @foreach ($platforms as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
                <input type="url" name="socials[__i__][url]" class="f__control social-row__url"
                       placeholder="Lien vers votre page" aria-label="Lien du réseau">
                <button type="button" class="social-row__del" data-social-remove
                        aria-label="Retirer ce réseau">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>
        </template>
    </x-step-shell>
</x-app-layout>
