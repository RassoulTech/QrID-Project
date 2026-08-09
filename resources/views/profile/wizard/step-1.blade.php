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
        @php $photo = $wizard->get('data.photo_path'); @endphp

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
        </div>
    </x-step-shell>
</x-app-layout>
