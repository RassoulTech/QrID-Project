{{-- ÉTAPE 1 — Qui êtes-vous.
     Trois champs obligatoires, deux optionnels. Rien d'autre. --}}
<x-app-layout title="Créer mon profil — étape 1">
    <x-step-shell
        :step="1"
        title="Qui êtes-vous ?"
        subtitle="Ces informations apparaissent en haut de votre profil."
        :action="route('profile.store.step1')"
        :back="route('dashboard')"
        back-label="Tableau de bord"
        :multipart="true"
    >
        <div class="f-row">
            <x-field name="first_name" label="Prénom" autocomplete="given-name"
                     :value="$wizard->field('first_name')" autofocus />

            <x-field name="last_name" label="Nom" autocomplete="family-name"
                     :value="$wizard->field('last_name')" />
        </div>

        <x-field name="job_title" label="Fonction" placeholder="Commercial, avocate, gérant…"
                 autocomplete="organization-title" :value="$wizard->field('job_title')" />

        <x-field name="company" label="Entreprise" optional autocomplete="organization"
                 :value="$wizard->field('company')" />

        {{-- PHOTO ------------------------------------------------------------
             Sans JavaScript : le champ fichier fonctionne, la photo part au
             « Continuer », l'aperçu apparaît simplement une page plus tard.
             Avec JavaScript : la vignette se remplit immédiatement. --}}
        @php
            $photo = $wizard->get('data.photo_path');
            $couverture = $wizard->get('data.cover_path');
        @endphp

        <div class="f">
            <span class="f__label">
                Photo
                <span class="f__opt">optionnel</span>
            </span>

            <label class="drop" data-photo-drop>
                <span class="drop__thumb" data-photo-thumb>
                    @if ($photo)
                        <img src="{{ Storage::url($photo) }}" alt="Photo actuelle">
                    @else
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2.5"/>
                            <circle cx="12" cy="12" r="3.2"/><path d="M8 5l1.2-2h5.6L16 5"/>
                        </svg>
                    @endif
                </span>

                <span>
                    <span class="drop__text" data-photo-label>
                        {{ $photo ? 'Changer la photo' : 'Ajouter une photo' }}
                    </span>
                    <span class="drop__hint">JPG, PNG ou WEBP — 2 Mo maximum</span>
                </span>

                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                       class="drop__input" data-photo-input>
            </label>

            @error('photo')
                <span class="f__error">{{ $message }}</span>
            @enderror

            <p class="f__help">
                Sans photo, votre carte affiche un portrait dessiné — jamais un vide.
            </p>
        </div>

        {{-- ═══════════════ BANNIÈRE ═══════════════
             LE HAUT DE LA CARTE PUBLIQUE APPARTIENT AU PORTEUR.

             C'était un dégradé vert identique chez tout le monde. Or cette
             bande est l'endroit le plus visible de la page : la première
             chose qu'on voit après un scan. La laisser inchangée d'un profil
             à l'autre revient à imprimer la même carte pour tous les clients
             et à leur demander de la reconnaître.

             Elle reste FACULTATIVE : personne ne doit être bloqué à la
             création parce qu'il n'a pas d'image sous la main. Sans bannière,
             la carte porte un décor composé aux couleurs de la marque. --}}
        <div class="f">
            <span class="f__label">
                Bannière de couverture
                <span class="f__opt">optionnel</span>
            </span>

            <label class="drop drop--banniere" data-cover-drop>
                <span class="drop__thumb drop__thumb--banniere" data-cover-thumb>
                    @if ($couverture)
                        <img src="{{ Storage::url($couverture) }}" alt="Bannière actuelle">
                    @else
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="6" width="20" height="12" rx="2.5"/>
                            <path d="m2 15 5-4 4 3 4-4 7 5"/><circle cx="8" cy="10" r="1.4"/>
                        </svg>
                    @endif
                </span>

                <span>
                    <span class="drop__text" data-cover-label>
                        {{ $couverture ? 'Changer la bannière' : 'Ajouter une bannière' }}
                    </span>
                    <span class="drop__hint">JPG, PNG ou WEBP — 4 Mo maximum</span>
                </span>

                <input type="file" name="cover" accept="image/jpeg,image/png,image/webp"
                       class="drop__input" data-cover-input>
            </label>

            @error('cover')
                <span class="f__error">{{ $message }}</span>
            @enderror

            <p class="f__help">
                Une image large, au format paysage. Sans bannière, votre carte
                porte le décor de {{ config('app.name') }}.
            </p>
        </div>
    </x-step-shell>
</x-app-layout>
