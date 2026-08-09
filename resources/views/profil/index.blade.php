{{--
  MON PROFIL — consultation de la carte et de ses informations.

  L'ÉDITION passe par le parcours en trois étapes (profile.edit), qui recharge
  le profil en session. Reconstruire ici un formulaire reprenant les mêmes
  champs aurait dupliqué la validation, la normalisation des numéros, le
  traitement de la photo et la gestion des réseaux — pour le même résultat,
  avec deux fois plus de surface de bugs.
--}}
<x-app-layout title="Mon profil">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">Mon profil</h1>
            <p class="db-tete__sous">Les informations que verront vos contacts.</p>
        </div>

        <x-button :href="route('profile.edit')" size="sm">Modifier mes informations</x-button>
    </div>

    <div class="db-grille">
        <div class="db-principal">

            {{-- ===================== IDENTITÉ ===================== --}}
            <section class="db-card">
                <div class="db-card__tete">
                    <h2 class="db-card__titre">Identité</h2>
                    <x-badge :status="$profile->is_active ? 'published' : 'draft'" />
                </div>

                <dl class="fiche">
                    <div class="fiche__ligne">
                        <dt>Nom complet</dt>
                        <dd>{{ $profile->full_name }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>Fonction</dt>
                        <dd>{{ $profile->job_title }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>Entreprise</dt>
                        <dd>{{ $profile->company ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- ===================== COORDONNÉES ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">Coordonnées</h2>

                <dl class="fiche">
                    <div class="fiche__ligne">
                        <dt>Téléphone</dt>
                        <dd>{{ $profile->formatted_phone ?: '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>WhatsApp</dt>
                        <dd>{{ $profile->whatsapp ? \App\Models\Profile::formatSenegalPhone($profile->whatsapp) : '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>E-mail public</dt>
                        <dd>{{ $profile->public_email ?: '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>Site web</dt>
                        <dd>{{ $profile->website ?: '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>Adresse</dt>
                        <dd>{{ $profile->address ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- ===================== RÉSEAUX ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">Réseaux sociaux</h2>

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
                        Aucun réseau ajouté. Vous pouvez en ajouter jusqu'à six
                        depuis la modification.
                    </p>
                @endforelse
            </section>

            {{-- ===================== APPARENCE ET LIEN ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">Apparence et lien</h2>

                <dl class="fiche">
                    <div class="fiche__ligne">
                        <dt>Modèle</dt>
                        <dd>{{ $profile->template?->name ?? '—' }}</dd>
                    </div>
                    <div class="fiche__ligne">
                        <dt>Couleur</dt>
                        <dd>
                            <span class="fiche__pastille"
                                  style="background:{{ $profile->primary_color }}" aria-hidden="true"></span>
                            {{ $profile->primary_color }}
                        </dd>
                    </div>
                </dl>

                <label class="board-link mt-3">
                    <span class="board-link__label">Lien public</span>
                    <span class="board-link__row">
                        <input type="text" class="board-link__input" readonly
                               id="lienProfil" value="{{ $publicUrl }}"
                               aria-label="Lien public de votre carte">
                        <button type="button" class="board-link__copy"
                                data-copy="lienProfil" data-copy-done="Copié">Copier</button>
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
                            Votre lien peut être modifié <strong>une seule fois</strong>,
                            depuis la modification. Après changement, les cartes déjà
                            imprimées et les QR Codes en circulation cesseront de
                            fonctionner&nbsp;: ils pointeront vers une page introuvable.
                        @else
                            Votre lien a déjà été modifié une fois&nbsp;: il est
                            définitif. C'est ce qui garantit que les cartes déjà
                            imprimées continueront de fonctionner.
                        @endif
                    </span>
                </div>
            </section>
        </div>

        {{-- ===================== APERÇU ===================== --}}
        <aside class="db-rail">
            <section class="db-card">
                <h2 class="db-card__titre">Aperçu</h2>

                <div class="db-carte__visuel">
                    <x-pvc-card :profile="$profile" size="sm" />
                </div>
            </section>
        </aside>
    </div>
</x-app-layout>
