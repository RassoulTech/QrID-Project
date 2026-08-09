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

<x-admin-layout :title="$client->name" subtitle="Fiche client">
    <x-slot:actions>
        @if ($profil && ! $profil->isDeactivated())
            <x-admin-action-form
                :action="route('admin.profiles.deactivate', $profil)"
                libelle="Désactiver le profil"
                confirmation="Désactiver"
                ton="danger"
                id="fiche-desact"
                titre="Désactiver ce profil"
                texte="La carte cesse immédiatement d'être accessible publiquement. Le contenu n'est ni modifié ni supprimé, et le client garde son accès." />
        @elseif ($profil)
            <x-admin-action-form
                :action="route('admin.profiles.reactivate', $profil)"
                methode="DELETE"
                libelle="Réactiver le profil"
                confirmation="Lever la désactivation"
                id="fiche-react"
                titre="Lever la désactivation"
                texte="Le profil restera en brouillon : c'est à son propriétaire de le republier." />
        @endif

        @if ($client->isBlocked())
            <x-admin-action-form
                :action="route('admin.clients.unblock', $client)"
                methode="DELETE"
                libelle="Débloquer le compte"
                confirmation="Débloquer"
                id="fiche-debloc"
                titre="Débloquer ce compte"
                :texte="'Motif du blocage en cours : '.($client->blocked_reason ?? 'non renseigné').'.'" />
        @else
            <x-admin-action-form
                :action="route('admin.clients.block', $client)"
                libelle="Bloquer le compte"
                confirmation="Bloquer"
                ton="danger"
                id="fiche-bloc"
                titre="Bloquer ce compte"
                texte="Le client ne pourra plus se connecter et ses sessions ouvertes seront fermées. Sa carte publiée reste en ligne tant que son profil n'est pas désactivé." />
        @endif
    </x-slot:actions>

    {{-- FIL D'ARIANE — la liste, puis la fiche. Deux niveaux, pas plus. --}}
    <nav class="adm-fil" aria-label="Fil d'ariane">
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
                    <x-badge variant="danger">Compte bloqué</x-badge>
                @else
                    <x-badge variant="success">Compte actif</x-badge>
                @endif

                @if ($prolongeable)
                    <x-badge :variant="$prolongeable->isActive() ? 'success' : 'secondary'">
                        {{ $prolongeable->plan?->name ?? 'Abonnement' }} —
                        {{ $prolongeable->isActive() ? 'en cours' : 'terminé' }}
                    </x-badge>
                @endif

                <span class="adm-table__second">
                    Inscrit le {{ $client->created_at?->format('d/m/Y') ?? '—' }}
                </span>
            </p>

            {{-- Le motif du blocage est affiché sur la fiche, pas seulement au
                 journal : c'est la première question posée quand le client
                 appelle. --}}
            @if ($client->isBlocked() && $client->blocked_reason)
                <p class="adm-fiche__motif">Motif du blocage : {{ $client->blocked_reason }}</p>
            @endif
        </div>
    </div>

    <div class="adm-grille adm-grille--2-1" style="grid-template-columns:1fr">
        <div class="adm-grille adm-grille--1-1" style="margin-bottom:0">

            {{-- ==================== COLONNE GAUCHE ==================== --}}
            <section class="adm-bloc">
                <div class="adm-bloc__tete">
                    <h2 class="adm-bloc__titre">Identité professionnelle</h2>
                </div>

                @if ($profil === null)
                    <x-empty-state icon="profile"
                        title="Aucun profil créé"
                        message="Ce compte existe mais son propriétaire n'a pas encore rempli sa carte." />
                @else
                    <dl class="adm-defs">
                        <dt>Nom complet</dt><dd>{{ $profil->full_name }}</dd>
                        <dt>Fonction</dt><dd>{{ $profil->job_title ?? '—' }}</dd>
                        <dt>Entreprise</dt><dd>{{ $profil->company ?? '—' }}</dd>
                        <dt>Identifiant public</dt>
                        <dd>
                            @if ($profil->is_active)
                                <a class="adm-lien" href="{{ route('profile.public', $profil) }}"
                                   target="_blank" rel="noopener">/p/{{ $profil->slug }}</a>
                            @else
                                /p/{{ $profil->slug }}
                            @endif
                        </dd>
                        <dt>Modèle</dt><dd>{{ $profil->template?->name ?? '—' }}</dd>
                        <dt>État</dt>
                        <dd>
                            @if ($profil->isDeactivated())
                                <x-badge variant="danger">Désactivé</x-badge>
                            @elseif ($profil->is_active)
                                <x-badge variant="success">Publié</x-badge>
                            @else
                                <x-badge variant="secondary">Brouillon</x-badge>
                            @endif
                        </dd>
                        <dt>Créé le</dt><dd>{{ $profil->created_at?->format('d/m/Y') ?? '—' }}</dd>

                        @if ($profil->deactivated_reason)
                            <dt>Motif de désactivation</dt>
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
                                confirmation="Prolonger l'abonnement"
                                id="fiche-prolong"
                                titre="Prolonger manuellement l'abonnement"
                                :texte="'Geste commercial, tracé au journal. La prolongation part de l\'échéance en cours lorsqu\'elle est future, de maintenant sinon : le client ne perd donc aucun jour déjà payé.'">
                                <x-slot:champs>
                                    <label for="jours-prolong" class="adm-modale__label">
                                        Nombre de jours
                                        <span class="adm-modale__obligatoire">obligatoire</span>
                                    </label>
                                    <input type="number" id="jours-prolong" name="jours"
                                           class="adm-modale__champ" required
                                           min="1" max="{{ $joursMax }}" value="{{ old('jours', 15) }}">
                                    <span class="adm-champ__aide">
                                        Au-delà de {{ $joursMax }} jours, ce n'est plus un geste
                                        commercial : passez par une formule, qui laisse une
                                        trace comptable.
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
                            title="Aucun abonnement"
                            message="Ce client n'a jamais souscrit." />
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
                            title="Aucune transaction"
                            message="Ce client n'a jamais payé." />
                    @endforelse
                </section>

                {{-- --- JOURNAL D'ACTIVITÉ --- --}}
                <section class="adm-bloc">
                    <div class="adm-bloc__tete">
                        <h2 class="adm-bloc__titre">Journal d'activité</h2>
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
                            title="Aucune action administrative"
                            message="Aucun blocage, aucune désactivation, aucune prolongation sur ce compte." />
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</x-admin-layout>
