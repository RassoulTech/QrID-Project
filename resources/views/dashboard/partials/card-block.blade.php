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
                {{-- UN SEUL MÉDIA, PARCE QU'ON N'EN DEMANDE QU'UN.

                     Cette liste en affichait deux : « photo » et « bannière ».
                     Or l'assistant ne demande plus de portrait depuis
                     longtemps — le client lisait donc « Aucune photo » pour
                     une image que rien ne lui permettait de fournir, et
                     concluait que le produit avait perdu son téléversement. --}}
                @foreach ([
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

                {{-- ═══════════════ PARTAGER SA PROPRE CARTE ═══════════════
                     LE GESTE QUE LE PRODUIT VEND, ET IL MANQUAIT ICI.

                     Le client pouvait copier son lien, télécharger son QR
                     Code, imprimer sa carte — mais pas la partager. Il lui
                     fallait ouvrir sa page publique pour y trouver le bouton,
                     c'est-à-dire faire un détour pour l'action la plus
                     fréquente de tout son parcours.

                     Le message est construit côté serveur par
                     App\Support\Whatsapp : il porte le nom affiché et
                     l'adresse publique, rien d'autre.

                     Il n'apparaît QUE si la carte est en ligne. Partager un
                     lien qui répond « carte indisponible » est pire que ne
                     pas proposer le partage : le prospect a déjà cliqué. --}}
                @if ($profile->isPubliclyVisible())
                    <x-button :href="\App\Support\Whatsapp::partageDeLaCarte($profile, $publicUrl)"
                              variant="outline" size="sm"
                              target="_blank" rel="noopener noreferrer">
                        {{ __('dashboard.carte.partager_whatsapp') }}
                    </x-button>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════ LE LIEN PUBLIC ═══════════════
         IL PASSE SOUS LA CARTE, EN PLEINE LARGEUR.

         Il vivait dans la colonne de droite, qui ne fait que 339px à 1440px
         de large : l'adresse s'y repliait sur trois lignes étroites, serrée
         entre les pastilles d'état et les boutons. C'est pourtant la seule
         chose que le client vient chercher ici.

         Sous la carte, il dispose de toute la largeur du bloc : l'adresse
         tient sur une ligne dès qu'il y a la place, et le bouton se pose à
         côté plutôt qu'en dessous.

         L'ADRESSE EST UN BLOC DE TEXTE, jamais un <input> : un champ ne passe
         pas à la ligne, et finit donc toujours par tronquer. `user-select:all`
         conserve ce que le champ garantissait — un geste sélectionne tout,
         donc la copie manuelle reste possible sans JavaScript. --}}
    <div class="board-link board-link--large">
        <span class="board-link__label">{{ __('dashboard.carte.lien_public') }}</span>

        {{-- UN SEUL CONTRÔLE, PAS TROIS OBJETS.
             Le libellé, une boîte grise et un bouton posé en dessous : trois
             éléments pour un seul geste, celui de copier. Le bouton entre
             donc DANS le cadre, séparé par un trait — la forme qu'ont tous
             les champs « copier ce lien », et qu'on reconnaît sans lire. --}}
        <div class="board-link__champ">
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
    </div>
</section>
