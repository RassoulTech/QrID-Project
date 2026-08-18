{{--
  LISTE DES PAIEMENTS — écran 4.

  LA LIGNE DE TOTAL porte le montant des paiements RÉUSSIS du filtre courant,
  jamais celui de la page affichée. Un total de page se lirait comme un total
  tout court et fausserait toute lecture au-delà des quinze premières lignes.

  LA VÉRIFICATION MANUELLE est le cœur de cet écran : elle sert le cas où le
  client a payé, l'opérateur a débité, mais le retour vers l'application s'est
  perdu. Elle exige un motif, elle est journalisée, et elle est idempotente.
--}}
@php use App\Models\Payment; @endphp

<x-admin-layout
    title="Liste des paiements"
    subtitle="Suivi des transactions financières, avec vérification manuelle auprès de l'opérateur."
>
    <x-slot:actions>
        <a href="{{ route('admin.payments.export', request()->query()) }}" class="adm-btn adm-btn--clair">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            Exporter CSV
        </a>
    </x-slot:actions>

    {{-- ==================== ONGLETS À COMPTEURS ==================== --}}
    {{-- Des LIENS, pas des boutons : chaque onglet a son URL, donc son
         signet et son partage. Les autres filtres sont conservés. --}}
    <nav class="adm-onglets" aria-label="Filtrer par statut">
        @php
            $onglets = [
                '' => ['Tous', $compteurs['tous']],
                Payment::STATUS_SUCCESS => ['Réussis', $compteurs[Payment::STATUS_SUCCESS]],
                Payment::STATUS_PENDING => ['En attente', $compteurs[Payment::STATUS_PENDING]],
                Payment::STATUS_FAILED => ['Échoués', $compteurs[Payment::STATUS_FAILED]],
            ];
        @endphp

        @foreach ($onglets as $cle => [$libelle, $nombre])
            <a href="{{ route('admin.payments.index', array_merge(request()->query(), ['statut' => $cle ?: null, 'page' => null])) }}"
               @class(['adm-onglet', 'is-active' => (string) $statut === (string) $cle])
               @if ((string) $statut === (string) $cle) aria-current="true" @endif>
                {{ $libelle }}
                <span class="adm-onglet__n">{{ number_format($nombre, 0, ',', ' ') }}</span>
            </a>
        @endforeach
    </nav>

    {{-- ==================== FILTRES ==================== --}}
    <form method="GET" action="{{ route('admin.payments.index') }}" class="adm-filtres">
        {{-- L'onglet actif survit au filtrage : sans ce champ, filtrer par
             moyen de paiement ramènerait silencieusement sur « Tous ». --}}
        <input type="hidden" name="statut" value="{{ $statut }}">

        <div class="adm-filtre">
            <label for="moyen">Moyen de paiement</label>
            <select id="moyen" name="moyen" class="adm-select">
                <option value="">Tous les moyens</option>
                @foreach ($methodes as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($moyen === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        {{-- UNE PÉRIODE, PAS DEUX DATES. Deux champs demandent deux saisies
             pour un seul résultat, ouvrent deux fois le sélecteur natif sur
             téléphone, et laissent saisir une fin antérieure au début — la
             liste se vide alors sans que l'écran dise pourquoi. --}}
        <div class="adm-filtre">
            <label for="periode">Période</label>
            <select id="periode" name="periode" class="adm-select">
                @foreach ($periodes as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($periode === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="adm-btn adm-btn--vert">Filtrer</button>

        @if ($moyen || $periode)
            <a href="{{ route('admin.payments.index', ['statut' => $statut]) }}"
               class="adm-btn adm-btn--clair">Réinitialiser</a>
        @endif
    </form>

    {{-- ==================== TABLEAU ==================== --}}
    <div class="adm-bloc">
        {{-- Compteur et etat vide : un seul composant pour les deux, voir
             x-liste-resultats. --}}
        <x-liste-resultats :total="$paiements->total()"
                           :filtre="(bool) ($statut || $moyen || $periode)"
                           :reset="route('admin.payments.index')"
                           nom="paiement" icon="payment"
                           vide="Les paiements apparaîtront ici dès le premier encaissement." />

        @if (! $paiements->isEmpty())

            <div class="table-scroll">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Référence et date</th>
                            <th scope="col">Client</th>
                            <th scope="col">Formule</th>
                            <th scope="col">Moyen</th>
                            <th scope="col">Montant</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="adm-table__actions">Vérification</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($paiements as $paiement)
                            <tr>
                                <td>
                                    <span class="adm-cell-id__texte">
                                        <span class="adm-table__principal">
                                            {{ $paiement->provider_ref ?? 'PAY-'.$paiement->id }}
                                        </span>
                                        <span class="adm-table__second">
                                            {{ $paiement->created_at?->format('d/m/Y H:i') ?? '—' }}
                                        </span>
                                    </span>
                                </td>

                                <td>
                                    @if ($paiement->user)
                                        <a class="adm-lien" href="{{ route('admin.clients.show', $paiement->user) }}">
                                            {{ $paiement->user->name }}
                                        </a>
                                    @else
                                        {{-- Le paiement survit à la suppression du compte :
                                             c'est une pièce comptable, pas une donnée
                                             de profil. --}}
                                        <span class="adm-table__second">Compte supprimé</span>
                                    @endif
                                </td>

                                <td class="adm-table__second">
                                    {{ $paiement->subscription?->plan?->name
                                        ?? ($paiement->payload['plan_slug'] ?? '—') }}
                                </td>

                                {{-- La marque rend la colonne lisible en
                                     diagonale : on repère un opérateur dans
                                     une liste de cent lignes sans lire. --}}
                                <td>
                                    <span class="pay-inline pay-mark--sm">
                                        <x-operator-mark :methode="$paiement->method" />
                                        {{ $paiement->method_label }}
                                    </span>
                                </td>

                                <td class="adm-table__principal">{{ $paiement->formattedAmount() }}</td>

                                <td>
                                    <x-badge :status="$paiement->status === 'success' ? 'active' : $paiement->status">
                                        {{ \App\Http\Controllers\Admin\PaymentController::statutLibelle($paiement->status) }}
                                    </x-badge>
                                </td>

                                <td class="adm-table__actions">
                                    {{-- Un paiement réussi n'a plus rien à vérifier :
                                         proposer le bouton laisserait croire qu'il
                                         reste quelque chose à faire. --}}
                                    @if ($paiement->isSuccessful())
                                        <span class="adm-table__second">—</span>
                                    @else
                                        <x-admin-action-form
                                            :action="route('admin.payments.verify', $paiement)"
                                            libelle="Vérifier"
                                            titre="Vérifier ce paiement auprès de l'opérateur"
                                            :id="'verif-'.$paiement->id"
                                            texte="La passerelle est interrogée avec la référence
                                                   {{ $paiement->provider_ref ?? $paiement->id }}. Si elle
                                                   confirme l'encaissement, l'abonnement est ouvert
                                                   immédiatement. L'opération est sans effet si le paiement
                                                   a déjà été encaissé." />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- LIGNE DE TOTAL — sur le filtre entier, pas sur la page. --}}
            <div class="adm-pied">
                <p class="adm-pied__compte">
                    Affichage {{ $paiements->firstItem() }} à {{ $paiements->lastItem() }}
                    sur {{ number_format($paiements->total(), 0, ',', ' ') }} paiement{{ $paiements->total() > 1 ? 's' : '' }}
                    <br>
                    <strong class="adm-table__principal">
                        Total encaissé sur ce filtre : {{ number_format($total, 0, ',', ' ') }} FCFA
                    </strong>
                </p>

                {{ $paiements->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
