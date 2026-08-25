{{-- ÉTAPE 1 — Qui êtes-vous.
     Trois champs obligatoires, deux optionnels. Rien d'autre. --}}
<x-app-layout :title="__('profile.wizard.titre_1')">
    <x-step-shell
        :step="1"
        :title="__('profile.wizard.entete_1')"
        :subtitle="__('profile.wizard.sous_1')"
        :action="route('profile.store.step1')"
        :back="route('dashboard')"
        :back-label="__('profile.wizard.retour_tableau')"
        :multipart="true"
    >
        <div class="f-row">
            <x-field name="first_name" :label="__('common.champs.prenom')" autocomplete="given-name"
                     :value="$wizard->field('first_name')" autofocus />

            <x-field name="last_name" :label="__('common.champs.nom')" autocomplete="family-name"
                     :value="$wizard->field('last_name')" />
        </div>

        <x-field name="job_title" :label="__('common.champs.fonction')"
                 :placeholder="__('profile.wizard.fonction_exemple')"
                 autocomplete="organization-title" :value="$wizard->field('job_title')" />

        <x-field name="company" :label="__('common.champs.entreprise')" optional autocomplete="organization"
                 :value="$wizard->field('company')" />

        {{-- PHOTO ------------------------------------------------------------
             Sans JavaScript : le champ fichier fonctionne, la photo part au
             « Continuer », l'aperçu apparaît simplement une page plus tard.
             Avec JavaScript : la vignette se remplit immédiatement. --}}
        @php $couverture = $wizard->get('data.cover_path'); @endphp

        {{-- ═══════════════ IMAGE DE COUVERTURE ═══════════════
             C'EST LE SEUL VISUEL DE LA CARTE, ET C'EST UN CHANGEMENT.

             Il y avait deux champs : une photo de portrait et une bannière.
             Le porteur en remplissait rarement deux, et sa page montrait le
             plus souvent un médaillon d'initiales sur un dégradé — deux
             replis empilés, c'est-à-dire un vide décoré.

             Une seule image désormais. Elle occupe toute la largeur du haut
             de la page, et le nom se pose dessus.

             Elle reste FACULTATIVE : personne ne doit être bloqué à la
             création parce qu'il n'a pas d'image sous la main. Sans elle, la
             carte porte un décor composé aux couleurs de la marque, et le nom
             s'y affiche de la même façon. --}}
        <div class="f">
            <span class="f__label">
                {{ __('profile.wizard.couverture') }}
                <span class="f__opt">{{ __('common.champs.optionnel') }}</span>
            </span>

            <label class="drop drop--banniere" data-cover-drop>
                <span class="drop__thumb drop__thumb--banniere" data-cover-thumb>
                    @if ($couverture)
                        <img src="{{ Storage::url($couverture) }}" alt="{{ __('profile.wizard.couverture_actuelle') }}">
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
                        {{ $couverture ? __('profile.wizard.couverture_changer') : __('profile.wizard.couverture_ajouter') }}
                    </span>
                    <span class="drop__hint">{{ __('profile.wizard.couverture_formats') }}</span>
                </span>

                <input type="file" name="cover" accept="image/jpeg,image/png,image/webp"
                       class="drop__input" data-cover-input>
            </label>

            @error('cover')
                <span class="f__error">{{ $message }}</span>
            @enderror

            <p class="f__help">
                {{ __('profile.wizard.couverture_aide', ['marque' => config('app.name')]) }}
            </p>
        </div>
    </x-step-shell>
</x-app-layout>
