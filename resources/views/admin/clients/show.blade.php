{{--
  FICHE CLIENT — écran 3.

  DEUX COLONNES : l'identité professionnelle à gauche, l'historique à droite.
  Les trois volets de droite — abonnements, paiements, journal — sont des
  <details> natifs, pas des onglets JavaScript : sans script, tout reste
  ouvert et lisible plutôt que réduit à un seul volet inaccessible.

  L'ADMINISTRATION NE MODIFIE RIEN DU PROFIL. Les seules actions possibles
  sont le blocage du compte, la désactivation du profil et la prolongation de
  l'abonnement — toutes avec motif obligatoire, toutes journalisées.
--}}
@php use App\Support\AdminActionType; @endphp

<x-admin-layout :title="$client->name" :subtitle="__('admin.fiche.titre')">
    <x-slot:actions>
        @if ($profil && ! $profil->isDeactivated())
            <x-admin-action-form
                :action="route('admin.profiles.deactivate', $profil)"
                :libelle="__('admin.fiche.desactiver_profil')"
                :confirmation="__('admin.profils.desactiver')"
                ton="danger"
                id="fiche-desact"
                :titre="__('admin.profils.desactiver_ce_profil')"
                :texte="__('admin.fiche.desactiver_texte')" />
        @elseif ($profil)
            <x-admin-action-form
                :action="route('admin.profiles.reactivate', $profil)"
                methode="DELETE"
                :libelle="__('admin.fiche.reactiver_profil')"
                :confirmation="__('admin.fiche.lever_desactivation')"
                id="fiche-react"
                :titre="__('admin.fiche.lever_desactivation')"
                :texte="__('admin.fiche.reactiver_texte')" />
        @endif

        @if ($client->isBlocked())
            <x-admin-action-form
                :action="route('admin.clients.unblock', $client)"
                methode="DELETE"
                :libelle="__('admin.fiche.debloquer_compte')"
                :confirmation="__('admin.fiche.debloquer')"
                id="fiche-debloc"
                :titre="__('admin.fiche.debloquer_titre')"
                :texte="__('admin.fiche.debloquer_texte', ['motif' => $client->blocked_reason ?? __('admin.fiche.motif_non_renseigne')])" />
        @else
            <x-admin-action-form
                :action="route('admin.clients.block', $client)"
                :libelle="__('admin.fiche.bloquer_compte')"
                confirmation="Bloquer"
                ton="danger"
                id="fiche-bloc"
                :titre="__('admin.fiche.bloquer_titre')"
                :texte="__('admin.fiche.bloquer_texte')" />
        @endif
    </x-slot:actions>

    {{-- FIL D'ARIANE — la liste, puis la fiche. Deux niveaux, pas plus. --}}
    <nav class="adm-fil" aria-label="{{ __('admin.commun.fil_ariane') }}">
        <a href="{{ route('admin.clients.index') }}">Clients</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">{{ $client->name }}</span>
    </nav>

    {{-- ==================== EN-TÊTE ==================== --}}
    <div class="adm-bloc adm-fiche__tete">
        <span class="adm-avatar adm-avatar--xl" aria-hidden="true">
            {{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}
        </span>

        <div class="adm-fiche__ident">
            <h2 class="adm-fiche__nom">{{ $client->name }}</h2>

            <p class="adm-fiche__coord">
                <span>{{ $client->email }}</span>
                <span>{{ $client->phone ?? 'Téléphone non renseigné' }}</span>
                @if ($profil?->address)
                    <span>{{ $profil->address }}</span>
                @endif
            </p>

            <p class="adm-fiche__etats">
                @if ($client->isBlocked())
                    <x-badge variant="danger">{{ __('admin.clients.compte_bloque') }}</x-badge>
                @else
                    <x-badge variant="success">{{ __('admin.clients.compte_actif') }}</x-badge>
                @endif

                @if ($prolongeable)
                    <x-badge :variant="$prolongeable->isActive() ? 'success' : 'secondary'">
                        {{ $prolongeable->plan?->name ?? 'Abonnement' }} —
                        {{ $prolongeable->isActive() ? 'en cours' : 'terminé' }}
                    </x-badge>
                @endif

                <span class="adm-table__second">
                    {{ __('admin.fiche.inscrit_le') }} {{ $client->created_at ? \App\Support\Formats::date($client->created_at) : '—' }}
                </span>
            </p>

            {{-- Le motif du blocage est affiché sur la fiche, pas seulement au
                 journal : c'est la première question posée quand le client
                 appelle. --}}
            @if ($client->isBlocked() && $client->blocked_reason)
                <p class="adm-fiche__motif">{{ __('admin.fiche.motif_blocage') }} {{ $client->blocked_reason }}</p>
            @endif
        </div>
    </div>

    <div class="adm-grille adm-grille--2-1" style="grid-template-columns:1fr">
        <div class="adm-grille adm-grille--1-1" style="margin-bottom:0">

            {{-- ==================== COLONNE GAUCHE ==================== --}}
            <section class="adm-bloc">
                <div class="adm-bloc__tete">
                    <h2 class="adm-bloc__titre">{{ __('admin.fiche.identite_pro') }}</h2>
                </div>

                @if ($profil === null)
                    <x-empty-state icon="profile"
                        :title="__('admin.fiche.aucun_profil_titre')"
                        :message="__('admin.fiche.aucun_profil_message')" />
                @else
                    <dl class="adm-defs">
                        <dt>{{ __('admin.commun.nom_complet') }}</dt><dd>{{ $profil->full_name }}</dd>
                        <dt>Fonction</dt><dd>{{ $profil->job_title ?? '—' }}</dd>
                        <dt>Entreprise</dt><dd>{{ $profil->company ?? '—' }}</dd>
                        <dt>{{ __('admin.commun.identifiant_public') }}</dt>
                        <dd>
                            @if ($profil->is_active)
                                <a class="adm-lien" href="{{ route('profile.public', $profil) }}"
                                   target="_blank" rel="noopener">/p/{{ $profil->slug }}</a>
                            @else
                                /p/{{ $profil->slug }}
                            @endif
                        </dd>
                        <dt>{{ __('admin.commun.modele') }}</dt><dd>{{ $profil->template?->name ?? '—' }}</dd>
                        <dt>{{ __('admin.commun.etat') }}</dt>
                        <dd>
                            @if ($profil->isDeactivated())
                                <x-badge variant="danger">{{ __('admin.commun.desactive') }}</x-badge>
                            @elseif ($profil->is_active)
                                <x-badge variant="success">{{ __('admin.commun.publie') }}</x-badge>
                            @else
                                <x-badge variant="secondary">Brouillon</x-badge>
                            @endif
                        </dd>
                        <dt>{{ __('admin.commun.cree_le') }}</dt><dd>{{ $profil->created_at?->format('d/m/Y') ?? '—' }}</dd>

                        @if ($profil->deactivated_reason)
                            <dt>{{ __('admin.fiche.motif_desactivation') }}</dt>
                            <dd>{{ $profil->deactivated_reason }}</dd>
                        @endif
                    </dl>
                @endif
            </section>

            {{-- ==================== COLONNE DROITE ==================== --}}
            <div class="adm-colonne">

                {{-- --- ABONNEMENTS --- --}}
                <section class="adm-bloc">
                    <div class="adm-bloc__tete">
                        <h2 class="adm-bloc__titre">Abonnements</h2>

                        @if ($prolongeable)
                            <x-admin-action-form
                                :action="route('admin.clients.subscription.extend', $client)"
                                libelle="Prolonger"
                                :confirmation="__('admin.fiche.prolonger')"
                                id="fiche-prolong"
                                :titre="__('admin.fiche.prolonger_titre')"
                                :texte="'Geste commercial, tracé au journal. La prolongation part de l\'échéance en cours lorsqu\'elle est future, de maintenant sinon : le client ne perd donc aucun jour déjà payé.'">
                                <x-slot:champs>
                                    <label for="jours-prolong" class="adm-modale__label">
                                        {{ __('admin.fiche.nombre_jours') }}
                                        <span class="adm-modale__obligatoire">obligatoire</span>
                                    </label>
                                    <input type="number" id="jours-prolong" name="jours"
                                           class="adm-modale__champ" required
                                           min="1" max="{{ $joursMax }}" value="{{ old('jours', 15) }}">
                                    <span class="adm-champ__aide">
                                        {{ __('admin.fiche.prolonger_aide', ['jours' => $joursMax]) }}
                                    </span>
                                    @error('jours')<span class="adm-modale__erreur">{{ $message }}</span>@enderror
                                </x-slot:champs>
                            </x-admin-action-form>
                        @endif
                    </div>

                    @forelse ($abonnements as $abo)
                        <div class="adm-ligne">
                            <span class="adm-ligne__texte">
                                <span class="adm-ligne__nom">{{ $abo->plan?->name ?? 'Formule supprimée' }}</span>
                                <span class="adm-ligne__meta">
                                    {{ $abo->starts_at?->format('d/m/Y') ?? '—' }}
                                    →
                                    {{ $abo->ends_at?->format('d/m/Y') ?? 'sans terme' }}
                                </span>
                            </span>
                            <span class="adm-ligne__droite">
                                <x-badge :status="$abo->status" />
                            </span>
                        </div>
                    @empty
                        <x-empty-state icon="payment"
                            :title="__('admin.fiche.aucun_abonnement_titre')"
                            :message="__('admin.fiche.aucun_abonnement_message')" />
                    @endforelse
                </section>

                {{-- --- PAIEMENTS --- --}}
                <section class="adm-bloc">
                    <div class="adm-bloc__tete">
                        <h2 class="adm-bloc__titre">Paiements</h2>
                        <a class="adm-card__lien"
                           href="{{ route('admin.payments.index', ['q' => $client->email]) }}">Tout voir</a>
                    </div>

                    @forelse ($paiements as $paiement)
                        <div class="adm-ligne">
                            <span class="adm-ligne__texte">
                                <span class="adm-ligne__nom">
                                    {{ $paiement->provider_ref ?? 'PAY-'.$paiement->id }}
                                </span>
                                <span class="adm-ligne__meta">
                                    {{ $paiement->created_at?->format('d/m/Y H:i') ?? '—' }} ·
                                    {{ $paiement->method_label }}
                                </span>
                            </span>
                            <span class="adm-ligne__droite">
                                <span class="adm-ligne__montant">{{ $paiement->formattedAmount() }}</span>
                                <x-badge :status="$paiement->status === 'success' ? 'active' : $paiement->status">
                                    {{ \App\Http\Controllers\Admin\PaymentController::statutLibelle($paiement->status) }}
                                </x-badge>
                            </span>
                        </div>
                    @empty
                        <x-empty-state icon="payment"
                            :title="__('admin.fiche.aucune_transaction_titre')"
                            :message="__('admin.fiche.aucune_transaction_message')" />
                    @endforelse
                </section>

                {{-- --- JOURNAL D'ACTIVITÉ --- --}}
                <section class="adm-bloc">
                    <div class="adm-bloc__tete">
                        <h2 class="adm-bloc__titre">{{ __('admin.fiche.journal_activite') }}</h2>
                    </div>

                    @forelse ($journal as $entree)
                        <div class="adm-ligne">
                            <span class="adm-ligne__texte">
                                <span class="adm-ligne__nom">{{ AdminActionType::libelle($entree->action) }}</span>
                                <span class="adm-ligne__meta" style="white-space:normal">
                                    {{ $entree->reason ?? 'Sans motif' }}
                                </span>
                            </span>
                            <span class="adm-ligne__droite">
                                <span class="adm-ligne__meta">
                                    {{ $entree->created_at?->format('d/m/Y') ?? '' }}
                                </span>
                                <span class="adm-ligne__meta">
                                    {{ $entree->admin?->name ?? 'Compte supprimé' }}
                                </span>
                            </span>
                        </div>
                    @empty
                        <x-empty-state icon="document"
                            :title="__('admin.fiche.aucune_action_titre')"
                            :message="__('admin.fiche.aucune_action_message')" />
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</x-admin-layout>
