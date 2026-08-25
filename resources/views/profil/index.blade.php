{{--
  MON PROFIL — consultation de la carte et de ses informations.

  L'ÉDITION passe par le parcours en trois étapes (profile.edit), qui recharge
  le profil en session. Reconstruire ici un formulaire reprenant les mêmes
  champs aurait dupliqué la validation, la normalisation des numéros, le
  traitement de la photo et la gestion des réseaux — pour le même résultat,
  avec deux fois plus de surface de bugs.
--}}
<x-app-layout :title="__('profile.fiche.titre')">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">{{ __('profile.fiche.titre') }}</h1>
            <p class="db-tete__sous">{{ __('profile.fiche.sous') }}</p>
        </div>

        <x-button :href="route('profile.edit')" size="sm">{{ __('profile.fiche.modifier') }}</x-button>
    </div>

    <div class="db-grille">
        <div class="db-principal">

            {{-- ===================== IDENTITÉ ===================== --}}
            <section class="db-card">
                <div class="db-card__tete">
                    <h2 class="db-card__titre">{{ __('profile.fiche.identite') }}</h2>
                    <x-badge :status="$profile->is_active ? 'published' : 'draft'" />
                </div>

                <dl class="fiche">
                    <div class="fiche__ligne">
                        <dt>{{ __('profile.fiche.nom_complet') }}</dt>
                        <dd>{{ $profile->full_name }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>{{ __('common.champs.fonction') }}</dt>
                        <dd>{{ $profile->job_title }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>{{ __('common.champs.entreprise') }}</dt>
                        <dd>{{ $profile->company ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- ===================== COORDONNÉES ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">{{ __('profile.fiche.coordonnees') }}</h2>

                <dl class="fiche">
                    <div class="fiche__ligne">
                        <dt>{{ __('common.champs.telephone') }}</dt>
                        <dd>{{ $profile->formatted_phone ?: '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>{{ __('profile.fiche.whatsapp') }}</dt>
                        <dd>{{ $profile->whatsapp ? \App\Models\Profile::formatSenegalPhone($profile->whatsapp) : '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>{{ __('profile.fiche.email_public') }}</dt>
                        <dd>{{ $profile->public_email ?: '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>{{ __('common.champs.site_web') }}</dt>
                        <dd>{{ $profile->website ?: '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>{{ __('common.champs.adresse') }}</dt>
                        <dd>{{ $profile->address ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- ===================== RÉSEAUX ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">{{ __('profile.fiche.reseaux') }}</h2>

                @forelse ($profile->socialLinks as $lien)
                    <div class="visite">
                        <span class="visite__pastille" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($lien->platform, 0, 1)) }}
                        </span>
                        <span class="visite__texte">
                            <span class="visite__type">
                                {{ \App\Services\ProfileWizardService::PLATFORMS[$lien->platform] ?? $lien->platform }}
                            </span>
                            <span class="visite__date">{{ $lien->url }}</span>
                        </span>
                    </div>
                @empty
                    <p class="db-vide__texte db-vide__texte--serre">
                        {{ __('profile.fiche.aucun_reseau') }}
                    </p>
                @endforelse
            </section>

            {{-- ===================== APPARENCE ET LIEN ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">{{ __('profile.fiche.apparence') }}</h2>

                <dl class="fiche">
                    <div class="fiche__ligne">
                        <dt>{{ __('profile.fiche.modele') }}</dt>
                        <dd>{{ $profile->template?->name ?? '—' }}</dd>
                    </div>
                    {{-- On nomme la variante, on n'affiche plus un code
                         hexadécimal : « #0B3B2E » ne dit rien à personne, et
                         suggérait que la valeur se règle librement — ce qui
                         n'est plus le cas. --}}
                    <div class="fiche__ligne">
                        <dt>{{ __('profile.fiche.carte') }}</dt>
                        <dd>
                            <span class="fiche__pastille"
                                  style="background:{{ $profile->variante()->fond() }}" aria-hidden="true"></span>
                            {{ $profile->variante()->libelle() }}
                        </dd>
                    </div>
                </dl>

                <label class="board-link mt-3">
                    <span class="board-link__label">{{ __('dashboard.carte.lien_public') }}</span>
                    <span class="board-link__row">
                        <input type="text" class="board-link__input" readonly
                               id="lienProfil" value="{{ $publicUrl }}"
                               aria-label="{{ __('dashboard.carte.lien_aria') }}">
                        <button type="button" class="board-link__copy"
                                data-copy="lienProfil"
                                data-copy-done="{{ __('dashboard.carte.copie') }}">{{ __('dashboard.carte.copier') }}</button>
                    </span>
                </label>

                {{-- Avertissement franc : ce lien est imprimé sur des cartes
                     physiques. Le modifier casse tout ce qui a déjà circulé. --}}
                <div class="mail-spam mt-3">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg>
                    <span>
                        @if ($profile->canChangeSlug())
                            {!! __('profile.fiche.lien_modifiable') !!}
                        @else
                            {!! __('profile.fiche.lien_definitif') !!}
                        @endif
                    </span>
                </div>
            </section>
        </div>

        {{-- ===================== APERÇU ===================== --}}
        <aside class="db-rail">
            <section class="db-card">
                <h2 class="db-card__titre">{{ __('profile.fiche.apercu') }}</h2>

                <div class="db-carte__visuel">
                    <x-card-duo :profile="$profile" />
                </div>
            </section>
        </aside>
    </div>
</x-app-layout>
