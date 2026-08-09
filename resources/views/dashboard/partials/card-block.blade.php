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
        <h2 class="db-card__titre">Ma carte</h2>
        <x-badge :status="$profile->is_active ? 'published' : 'draft'" />
    </div>

    <div class="db-carte">
        {{-- La carte et son bouton de permutation, groupés : le bouton
             appartient à la carte, il la suit quelle que soit la largeur. --}}
        <div class="db-carte__visuel">
            <x-pvc-card :profile="$profile" size="md" />
        </div>

        <div class="db-carte__cote">
            <label class="board-link">
                <span class="board-link__label">Lien public</span>
                <span class="board-link__row">
                    <input type="text" class="board-link__input" readonly
                           id="lienPublic" value="{{ $publicUrl }}"
                           aria-label="Lien public de votre carte">
                    <button type="button" class="board-link__copy"
                            data-copy="lienPublic" data-copy-done="Copié">
                        Copier
                    </button>
                </span>
            </label>

            <div class="board-downloads">
                <x-button :href="route('carte.qr.png')" variant="outline" size="sm">QR en PNG</x-button>
                <x-button :href="route('carte.qr.svg')" variant="outline" size="sm">QR en SVG</x-button>

                @if ($profile->isPubliclyVisible())
                    <x-button :href="route('carte.imprimable')" variant="outline" size="sm">
                        Carte imprimable
                    </x-button>
                @endif

                <x-button :href="route('profile.edit')" variant="outline" size="sm">
                    Modifier ma carte
                </x-button>
            </div>
        </div>
    </div>
</section>
