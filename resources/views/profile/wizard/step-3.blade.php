{{-- ÉTAPE 3 — Votre style.
     Zéro saisie : tout est pré-sélectionné. On peut terminer d'un seul clic. --}}
@php
    $modeleChoisi = (int) $wizard->field('template_id', $templates->first()?->id);
    $varianteChoisie = $wizard->field('primary_color', \App\Enums\VarianteCarte::DEFAUT->value);

    // Classe d'aperçu par modèle : la miniature ne fait pas que changer de nom.
    $apercus = ['classique' => '', 'moderne' => ' tpl__mini--moderne', 'minimal' => ' tpl__mini--minimal'];
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
                 data-color-target style="--tpl-color:{{ \App\Enums\VarianteCarte::DEFAUT->value }}">
                @foreach ($templates as $template)
                    <label class="tpl">
                        <input type="radio" name="template_id" value="{{ $template->id }}"
                               class="tpl__input" @checked($modeleChoisi === $template->id)>

                        <span class="tpl__box">
                            <span class="tpl__mini{{ $apercus[$template->slug] ?? '' }}" aria-hidden="true">
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

        {{-- VARIANTE DE CARTE -------------------------------------------------
             DEUX CARTES, PAS UN NUANCIER, et la différence de présentation
             est le fond du sujet. Des pastilles de couleur invitent à
             composer une identité personnelle ; deux aperçus de carte
             invitent à choisir entre deux objets finis.

             Chaque carte imprimée est un support de communication pour la
             plateforme : cinq teintes au choix produisaient cinq marques.

             De vrais boutons radio, habillés en cartes : cela fonctionne au
             clavier et sans JavaScript. --}}
        <div class="f">
            <span class="f__label">Votre carte</span>

            <div class="varcard-grid" role="radiogroup" aria-label="Variante de carte">
                @foreach ($variantes as $variante)
                    <label class="varcard">
                        <input type="radio" name="primary_color" value="{{ $variante->value }}"
                               class="varcard__input" @checked($varianteChoisie === $variante->value)>

                        <span class="varcard__box">
                            {{-- Aperçu réduit du recto : fond et encre de la
                                 variante, aux proportions exactes d'une carte
                                 ID-1. Le carré figure le QR Code sans le
                                 générer — cet écran ne connaît pas encore le
                                 slug du profil. --}}
                            <span class="varcard__apercu" aria-hidden="true"
                                  style="--v-fond:{{ $variante->fond() }};--v-encre:{{ $variante->encre() }}">
                                <span class="varcard__nom">VOTRE NOM</span>
                                <span class="varcard__qr"></span>
                                <span class="varcard__fonction">VOTRE FONCTION</span>
                            </span>

                            <span class="varcard__texte">
                                <span class="varcard__titre">{{ $variante->libelle() }}</span>
                                <span class="varcard__desc">{{ $variante->description() }}</span>
                            </span>

                            <span class="varcard__check" aria-hidden="true">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('primary_color')<span class="f__error">{{ $message }}</span>@enderror
        </div>
    </x-step-shell>
</x-app-layout>
