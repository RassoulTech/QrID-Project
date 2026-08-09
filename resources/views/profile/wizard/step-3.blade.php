{{-- ÉTAPE 3 — Votre style.
     Zéro saisie : tout est pré-sélectionné. On peut terminer d'un seul clic. --}}
@php
    $modeleChoisi = (int) $wizard->field('template_id', $templates->first()?->id);
    $couleurChoisie = $wizard->field('primary_color', array_key_first($colors));

    // Classe d'aperçu par modèle : la miniature ne fait pas que changer de nom.
    $variantes = ['classique' => '', 'moderne' => ' tpl__mini--moderne', 'minimal' => ' tpl__mini--minimal'];
@endphp

<x-app-layout title="Créer mon profil — étape 3">
    <x-step-shell
        :step="3"
        title="Votre style"
        subtitle="Tout est déjà choisi. Modifiez si vous le souhaitez, ou terminez."
        :action="route('profile.store.step3')"
        :back="route('profile.create.step2')"
        submit="Terminer"
    >
        {{-- MODÈLES ---------------------------------------------------------
             Trois boutons radio habillés en cartes. Fonctionne au clavier et
             sans JavaScript : ce sont de vrais champs de formulaire. --}}
        <div class="f">
            <span class="f__label">Modèle</span>

            {{-- La teinte des miniatures vit dans une variable CSS : le rendu
                 initial est correct sans JavaScript, le module ne fait que la
                 mettre à jour au clic. --}}
            <div class="tpl-grid" role="radiogroup" aria-label="Modèle de profil"
                 data-color-target style="--tpl-color:{{ $couleurChoisie }}">
                @foreach ($templates as $template)
                    <label class="tpl">
                        <input type="radio" name="template_id" value="{{ $template->id }}"
                               class="tpl__input" @checked($modeleChoisi === $template->id)>

                        <span class="tpl__box">
                            <span class="tpl__mini{{ $variantes[$template->slug] ?? '' }}" aria-hidden="true">
                                <span class="tpl__mini-head">
                                    <span class="tpl__mini-ava"></span>
                                </span>
                                <span class="tpl__mini-body">
                                    <span class="tpl__mini-line tpl__mini-line--w60"></span>
                                    <span class="tpl__mini-line tpl__mini-line--w40"></span>
                                    <span class="tpl__mini-btns">
                                        <span class="tpl__mini-btn"></span>
                                        <span class="tpl__mini-btn"></span>
                                        <span class="tpl__mini-btn"></span>
                                    </span>
                                </span>
                            </span>

                            <span class="tpl__name">{{ $template->name }}</span>

                            <span class="tpl__check" aria-hidden="true">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('template_id')<span class="f__error">{{ $message }}</span>@enderror
        </div>

        {{-- COULEUR ----------------------------------------------------------
             Liste fermée : impossible de produire un profil illisible.
             Avec JavaScript, les miniatures se teintent instantanément. --}}
        <div class="f">
            <span class="f__label">Couleur</span>

            <div class="swatches" role="radiogroup" aria-label="Couleur du profil"
                 data-color-preview data-target="[data-color-target]">
                @foreach ($colors as $hex => $nom)
                    <label class="swatch">
                        <input type="radio" name="primary_color" value="{{ $hex }}"
                               class="swatch__input" @checked($couleurChoisie === $hex)>
                        <span class="swatch__dot" style="background:{{ $hex }}"></span>
                        <span class="visually-hidden">{{ $nom }}</span>
                    </label>
                @endforeach
            </div>

            @error('primary_color')<span class="f__error">{{ $message }}</span>@enderror
        </div>
    </x-step-shell>
</x-app-layout>
