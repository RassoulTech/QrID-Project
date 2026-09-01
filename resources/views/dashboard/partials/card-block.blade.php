{{--
  Bloc « Ma carte » — la carte PVC, le lien public et les téléchargements.

  Le champ du lien est en lecture seule : on le sélectionne et on le copie à
  la main si le module JavaScript n'est pas là. Le bouton « Copier » n'est
  qu'un raccourci.

  Le PDF n'apparaît QUE si la carte est réellement en ligne : proposer un
  fichier destiné à être imprimé en centaines d'exemplaires alors que le lien
  ne répond pas produirait des cartes physiques mortes à la sortie de
  l'imprimerie.
--}}
<section class="db-card">
    <div class="db-card__tete">
        <h2 class="db-card__titre">{{ __('dashboard.carte.titre') }}</h2>
        <x-badge :status="$profile->is_active ? 'published' : 'draft'" />
    </div>

    <div class="db-carte">
        {{-- La carte et son bouton de permutation, groupés : le bouton
             appartient à la carte, il la suit quelle que soit la largeur. --}}
        <div class="db-carte__visuel">
            <x-card-duo :profile="$profile" />
        </div>

        <div class="db-carte__cote">
            {{-- L'ADRESSE EST LUE EN ENTIER, OU ELLE NE SERT À RIEN.
                 Un <input> ne passe jamais à la ligne : quelle que soit la
                 largeur qu'on lui donne, une adresse plus longue finit coupée.
                 C'est ce qui affichait « https://qrid-uutz.onr ».

                 Un bloc de texte, lui, se replie. `user-select:all` rend la
                 sélection d'un seul geste — donc la copie manuelle reste
                 possible sans JavaScript, ce que le champ garantissait. --}}
            <div class="board-link">
                <span class="board-link__label">{{ __('dashboard.carte.lien_public') }}</span>

                <output class="board-link__valeur" id="lienPublic"
                        aria-label="{{ __('dashboard.carte.lien_aria') }}">{{ $publicUrl }}</output>

                <button type="button" class="board-link__copy"
                        data-copy="lienPublic" data-copy-done="{{ __('dashboard.carte.copie') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="9" y="9" width="13" height="13" rx="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    <span data-copy-label>{{ __('dashboard.carte.copier') }}</span>
                </button>
            </div>

            {{-- ═══════════════ ÉTAT DES IMAGES ═══════════════
                 IL FAUT POUVOIR VÉRIFIER SANS ALLER SUR LA PAGE PUBLIQUE.

                 Un client qui téléverse sa photo, la voit dans la vignette du
                 formulaire, puis la découvre absente de sa carte n'a aucun
                 moyen de savoir ce qui s'est passé. Il conclut que le produit
                 ne sait pas garder une photo — et il a raison de le conclure,
                 puisque rien ne lui dit le contraire.

                 Cette ligne dit ce que la BASE contient, pas ce que le
                 formulaire a affiché. C'est la seule chose qui compte. --}}
            <div class="board-medias">
                {{-- La phrase entière vient du fichier de langue, en deux clés
                     par média. « Photo enregistrée » / « Aucune photo » se
                     construisaient par concaténation : l'accord de l'adjectif y
                     était figé au féminin, et l'anglais n'y aurait rien accordé
                     du tout. --}}
                @foreach ([
                    ['photo', $profile->aUnePhoto()],
                    ['banniere', $profile->aUneCouverture()],
                ] as [$genre, $presente])
                    <span @class(['board-media', 'board-media--absente' => ! $presente])>
                        @if ($presente)
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            {{ __('dashboard.carte.'.$genre.'_ok') }}
                        @else
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/>
                            </svg>
                            {{ __('dashboard.carte.'.$genre.'_absente') }}
                        @endif
                    </span>
                @endforeach
            </div>

            <div class="board-downloads">
                <x-button :href="route('carte.qr.png')" variant="outline" size="sm">{{ __('dashboard.carte.qr_png') }}</x-button>
                <x-button :href="route('carte.qr.svg')" variant="outline" size="sm">{{ __('dashboard.carte.qr_svg') }}</x-button>

                @if ($profile->isPubliclyVisible())
                    <x-button :href="route('carte.imprimable')" variant="outline" size="sm">
                        {{ __('dashboard.carte.imprimable') }}
                    </x-button>
                @endif

                <x-button :href="route('profile.edit')" variant="outline" size="sm">
                    {{ __('dashboard.carte.modifier') }}
                </x-button>
            </div>
        </div>
    </div>
</section>
