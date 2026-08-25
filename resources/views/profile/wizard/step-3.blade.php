{{-- ÉTAPE 3 — Votre style.
     Zéro saisie : tout est pré-sélectionné. On peut terminer d'un seul clic. --}}
@php
    $modeleChoisi = (int) $wizard->field('template_id', $templates->first()?->id);
    $varianteChoisie = $wizard->field('primary_color', \App\Enums\VarianteCarte::DEFAUT->value);

    /*
     | LE PROFIL D'APERÇU EST CELUI QU'ON EST EN TRAIN DE CRÉER.
     |
     | Les étapes 1 et 2 ont déjà recueilli le nom et la fonction : les
     | montrer ici coûte une ligne et transforme une maquette en aperçu.
     | Le profil n'est pas enregistré — c'est un objet en mémoire, jamais
     | écrit, qui sert uniquement au rendu des deux faces.
     */
    $apercuProfil = new \App\Models\Profile([
        'first_name' => $wizard->field('first_name', __('profile.wizard.apercu_prenom')),
        'last_name' => $wizard->field('last_name', __('profile.wizard.apercu_nom')),
        'job_title' => $wizard->field('job_title', __('profile.wizard.apercu_fonction')),
        'company' => $wizard->field('company'),
        'slug' => auth()->user()?->profile?->slug ?? 'apercu',
    ]);

    // Classe d'aperçu par modèle : la miniature ne fait pas que changer de nom.
    $apercus = ['classique' => '', 'moderne' => ' tpl__mini--moderne', 'minimal' => ' tpl__mini--minimal'];
@endphp

<x-app-layout :title="__('profile.wizard.titre_3')">
    <x-step-shell
        :step="3"
        :title="__('profile.wizard.entete_3')"
        :subtitle="__('profile.wizard.sous_3')"
        :action="route('profile.store.step3')"
        :back="route('profile.create.step2')"
        :submit="__('profile.wizard.terminer')"
    >
        {{-- MODÈLES ---------------------------------------------------------
             Trois boutons radio habillés en cartes. Fonctionne au clavier et
             sans JavaScript : ce sont de vrais champs de formulaire. --}}
        <div class="f">
            <span class="f__label">{{ __('profile.wizard.modele') }}</span>

            {{-- La teinte des miniatures vit dans une variable CSS : le rendu
                 initial est correct sans JavaScript, le module ne fait que la
                 mettre à jour au clic. --}}
            <div class="tpl-grid" role="radiogroup" aria-label="{{ __('profile.wizard.modele_aria') }}"
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
            <span class="f__label">{{ __('profile.wizard.carte') }}</span>

            <div class="varcard-grid" role="radiogroup" aria-label="{{ __('profile.wizard.carte_aria') }}">
                @foreach ($variantes as $variante)
                    <label class="varcard">
                        <input type="radio" name="primary_color" value="{{ $variante->value }}"
                               class="varcard__input" @checked($varianteChoisie === $variante->value)>

                        <span class="varcard__box">
                            {{-- ═══════════════════════════════════════════
                                 LES VRAIES CARTES, RECTO ET VERSO
                                 ═══════════════════════════════════════════
                                 C'était une maquette schématique : un carré
                                 gris à la place du QR, « VOTRE NOM » en
                                 majuscules, et le verso absent. On choisissait
                                 donc une variante sans l'avoir vue — et la
                                 vraie carte arrivait deux écrans plus loin.

                                 Ce sont maintenant les composants servis à
                                 l'impression, rendus aux couleurs de la
                                 variante. Ce qu'on choisit est ce qu'on
                                 recevra.

                                 aria-hidden : les deux faces sont décoratives
                                 ici. Le libellé et la description qui suivent
                                 portent seuls l'information, et c'est le
                                 bouton radio qui est annoncé. --}}
                            <span class="varcard__faces" aria-hidden="true"
                                  style="--pvc-fond:{{ $variante->fond() }};--pvc-encre:{{ $variante->encre() }};--pvc-relief:{{ $variante->relief() }};--pvc-chevron:{{ $variante->accent() }};--pvc-onde:{{ $variante->accent() }}">
                                <span class="varcard__face">
                                    <x-card face="recto" :profile="$apercuProfil" :variant="$variante->carte()" />
                                </span>
                                <span class="varcard__face">
                                    <x-card face="verso" :profile="$apercuProfil" :variant="$variante->carte()" />
                                </span>
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
