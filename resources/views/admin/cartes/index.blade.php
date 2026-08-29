{{--
  CARTES À PRODUIRE — l'écran de l'atelier.

  ON N'ENVOIE PAS UNE CARTE À L'IMPRIMEUR, ON ENVOIE UN LOT. Cet écran existe
  pour regrouper, pas pour traiter à l'unité.

  LES COMMANDES SANS ADRESSE SONT MONTRÉES, PAS CACHÉES : un client qui a payé
  puis fermé l'onglet a droit à sa carte, et ce sont justement celles-là qu'il
  faut relancer.
--}}
@php use App\Models\CardOrder; @endphp

<x-admin-layout
    :title="__('admin.cartes.titre')"
    :subtitle="__('admin.cartes.sous_titre')"
>
    <x-slot:actions>
        <a href="{{ route('admin.cards.export', request()->query()) }}" class="adm-btn adm-btn--clair">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            {{ __('admin.cartes.export_imprimeur') }}
        </a>
    </x-slot:actions>

    {{-- ══════════════ CE QUI DÉCIDE DE LANCER UNE PRODUCTION ══════════════
         Le nombre en attente ne suffit pas : vingt commandes d'hier ne posent
         aucun problème, une seule qui attend depuis six semaines en pose un. --}}
    <div class="adm-cartes-tete">
        <div class="adm-cartes-jauge">
            <p class="adm-cartes-jauge__n">
                {{ $compteurs[CardOrder::STATUS_PENDING] }}<span>/{{ $seuil }}</span>
            </p>
            <p class="adm-cartes-jauge__l">en attente de production</p>

            @if ($compteurs[CardOrder::STATUS_PENDING] >= $seuil)
                <span class="adm-cartes-jauge__pret">{{ __('admin.cartes.seuil_atteint') }}</span>
            @endif
        </div>

        <div class="adm-cartes-jauge">
            <p class="adm-cartes-jauge__n">{{ $compteurs['plus_ancienne'] }}<span> j</span></p>
            <p class="adm-cartes-jauge__l">la plus ancienne attend depuis</p>

            @if ($compteurs['plus_ancienne'] > config('cartes.delai_jours'))
                <span class="adm-cartes-jauge__retard">{{ __('admin.cartes.delai_depasse') }}</span>
            @endif
        </div>

        <div class="adm-cartes-jauge">
            <p class="adm-cartes-jauge__n">{{ $economie['taux_renouvellement'] }}<span> %</span></p>
            <p class="adm-cartes-jauge__l">{{ __('admin.cartes.renouvellent') }}</p>
            <span class="adm-cartes-jauge__note">{{ $economie['clients_payants'] }} {{ __('admin.cartes.clients_payants') }}</span>
        </div>

        <div class="adm-cartes-jauge">
            <p class="adm-cartes-jauge__n">{{ number_format($economie['marge'], 0, ',', ' ') }}<span> FCFA</span></p>
            <p class="adm-cartes-jauge__l">marge nette</p>
            <span class="adm-cartes-jauge__note">
                {{ \App\Support\Formats::montant($economie['revenu'], false) }} {{ __('admin.cartes.encaisses') }} −
                {{ number_format($economie['cout_cartes'], 0, ',', ' ') }} de cartes
            </span>
        </div>
    </div>

    {{-- ══════════════ ONGLETS ══════════════ --}}
    <nav class="adm-onglets" aria-label="{{ __('admin.cartes.filtrer_etat') }}">
        <a href="{{ route('admin.cards.index') }}"
           @class(['adm-onglet', 'is-active' => ! $statut])>
            Toutes <span class="adm-onglet__n">{{ $compteurs['tous'] }}</span>
        </a>

        @foreach ($statuts as $cle => $libelle)
            <a href="{{ route('admin.cards.index', ['statut' => $cle]) }}"
               @class(['adm-onglet', 'is-active' => $statut === $cle])>
                {{ $libelle }} <span class="adm-onglet__n">{{ $compteurs[$cle] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="adm-bloc">
        <x-liste-resultats :total="$commandes->total()"
                           :filtre="(bool) ($statut || $lot)"
                           :reset="route('admin.cards.index')"
                           nom="commande" icon="payment"
                           :vide="__('admin.cartes.vide')" />

        @if (! $commandes->isEmpty())
            <form method="POST" action="{{ route('admin.cards.batch') }}">
                @csrf

                <div class="table-scroll">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th scope="col" class="adm-table__actions">Lot</th>
                                <th scope="col">Client</th>
                                <th scope="col">Livraison</th>
                                <th scope="col">Attente</th>
                                <th scope="col">{{ __('admin.commun.etat') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($commandes as $commande)
                                <tr>
                                    <td>
                                        {{-- Seules les commandes PRÊTES sont cochables :
                                             une carte sans adresse partirait chez
                                             l'imprimeur sans qu'on sache où l'envoyer. --}}
                                        @if ($commande->status === CardOrder::STATUS_PENDING && $commande->adresseComplete())
                                            <input type="checkbox" name="commandes[]"
                                                   value="{{ $commande->id }}"
                                                   aria-label="Inclure la commande {{ $commande->id }}">
                                        @elseif ($commande->batch_id)
                                            <a class="adm-lien" href="{{ route('admin.cards.index', ['lot' => $commande->batch_id]) }}">
                                                {{ $commande->batch_id }}
                                            </a>
                                        @else
                                            <span class="adm-table__second">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        <a class="adm-lien" href="{{ route('admin.clients.show', $commande->user_id) }}">
                                            {{ $commande->user?->name ?? 'Compte supprimé' }}
                                        </a>
                                        <span class="adm-table__second">{{ $commande->profile?->slug ?? '—' }}</span>
                                    </td>

                                    <td>
                                        @if ($commande->adresseComplete())
                                            <span class="adm-table__principal">{{ $commande->recipient_name }}</span>
                                            <span class="adm-table__second">
                                                {{ $commande->address_line }}, {{ $commande->city }}
                                            </span>
                                        @else
                                            {{-- Ce n'est pas un détail : le client a payé,
                                                 il attend une carte, et personne ne peut
                                                 l'expédier. C'est une relance à faire. --}}
                                            <span class="adm-badge adm-badge--attention">{{ __('admin.cartes.adresse_manquante') }}</span>
                                        @endif
                                    </td>

                                    <td class="adm-table__second">{{ $commande->anciennete() }} j</td>

                                    <td><x-badge :status="$commande->status">{{ $commande->statutLibelle() }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="adm-cartes-actions">
                    <button type="submit" class="adm-btn adm-btn--vert">
                        {{ __('admin.cartes.creer_lot') }}
                    </button>
                    <span class="adm-cartes-actions__note">
                        {{ __('admin.cartes.creer_lot_aide') }}
                    </span>
                </div>
            </form>

            {{-- ══════════════ FAIRE AVANCER UN LOT ══════════════ --}}
            @if ($lot)
                <form method="POST" action="{{ route('admin.cards.advance') }}" class="adm-cartes-lot">
                    @csrf
                    <input type="hidden" name="batch_id" value="{{ $lot }}">

                    <label for="statut_lot">{{ __('admin.cartes.faire_passer') }} {{ $lot }} à</label>

                    <select id="statut_lot" name="statut" class="adm-select">
                        @foreach ($statuts as $cle => $libelle)
                            <option value="{{ $cle }}">{{ $libelle }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="adm-btn adm-btn--vert">Appliquer</button>
                </form>
            @endif

            <x-pagination :paginator="$commandes" />
        @endif
    </div>
</x-admin-layout>
