{{--
  ABONNEMENTS.

  ÉCRAN SANS MAQUETTE — le menu validé porte l'entrée, aucune des huit
  maquettes ne la montre. Il reprend donc la grammaire des autres listes pour
  ne pas détonner, et rester simple à remplacer si la maquette diffère.

  CE QU'IL APPORTE que la liste des clients ne donne pas : les échéances,
  triées du plus urgent au plus lointain. On ouvre cet écran pour savoir qui
  arrive à terme cette semaine, pas pour chercher une personne.

  LECTURE SEULE. Un abonnement naît d'un encaissement, il ne se crée pas à la
  main. La seule écriture possible — la prolongation — vit sur la fiche
  client, avec motif obligatoire et journal.
--}}
@php use App\Models\Subscription; @endphp

<x-admin-layout
    title="Abonnements"
    :subtitle="__('admin.abonnements.sous_titre')"
>
    <x-slot:actions>
        <a href="{{ route('admin.subscriptions.export', request()->query()) }}" class="adm-btn adm-btn--clair">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            {{ __('admin.commun.exporter_csv') }}
        </a>
    </x-slot:actions>

    <nav class="adm-onglets" aria-label="{{ __('admin.abonnements.filtrer_statut') }}">
        @foreach ([
            '' => ['Tous', $compteurs['tous']],
            Subscription::STATUS_ACTIVE => ['Actifs', $compteurs[Subscription::STATUS_ACTIVE]],
            Subscription::STATUS_EXPIRED => ['Expirés', $compteurs[Subscription::STATUS_EXPIRED]],
            Subscription::STATUS_CANCELLED => ['Annulés', $compteurs[Subscription::STATUS_CANCELLED]],
        ] as $cle => [$libelle, $nombre])
            <a href="{{ route('admin.subscriptions.index', ['statut' => $cle ?: null]) }}"
               @class(['adm-onglet', 'is-active' => (string) $statut === (string) $cle])
               @if ((string) $statut === (string) $cle) aria-current="true" @endif>
                {{ $libelle }}
                <span class="adm-onglet__n">{{ number_format($nombre, 0, ',', ' ') }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="adm-filtres" data-auto-filtre>
        <input type="hidden" name="statut" value="{{ $statut }}">

        <div class="adm-filtre">
            <label for="plan">Formule</label>
            <select id="plan" name="plan" class="adm-select">
                <option value="">{{ __('admin.abonnements.toutes_formules') }}</option>
                @foreach ($plans as $p)
                    <option value="{{ $p->slug }}" @selected($plan === $p->slug)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- LE FILTRE QUI JUSTIFIE L'ÉCRAN : repérer ce qui arrive à terme
             avant que le client ne s'en aperçoive. --}}
        <div class="adm-filtre">
            <label for="echeance">{{ __('admin.commun.echeance') }}</label>
            <select id="echeance" name="echeance" class="adm-select">
                <option value="">{{ __('admin.abonnements.toutes_echeances') }}</option>
                <option value="3" @selected($echeance === '3')>{{ __('admin.abonnements.echoit_3') }}</option>
                <option value="7" @selected($echeance === '7')>{{ __('admin.abonnements.echoit_7') }}</option>
                <option value="30" @selected($echeance === '30')>{{ __('admin.abonnements.echoit_30') }}</option>
            </select>
        </div>

        <button type="submit" class="adm-btn adm-btn--vert" data-auto-filtre-bouton>Filtrer</button>

        @if ($plan || $echeance)
            <a href="{{ route('admin.subscriptions.index', ['statut' => $statut]) }}"
               class="adm-btn adm-btn--clair">{{ __('admin.commun.reinitialiser') }}</a>
        @endif
    </form>

    <div class="adm-bloc">
        {{-- Compteur et etat vide : un seul composant pour les deux, voir
             x-liste-resultats. --}}
        <x-liste-resultats :total="$abonnements->total()"
                           :filtre="(bool) ($statut || $plan || $echeance)"
                           :reset="route('admin.subscriptions.index')"
                           nom="abonnement" icon="payment"
                           :vide="__('admin.abonnements.vide')" />

        @if (! $abonnements->isEmpty())

            <div class="table-scroll">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Client</th>
                            <th scope="col">Formule</th>
                            <th scope="col">{{ __('admin.commun.debut') }}</th>
                            <th scope="col">{{ __('admin.commun.echeance') }}</th>
                            <th scope="col">Reste</th>
                            <th scope="col">{{ __('admin.commun.statut') }}</th>
                            <th scope="col" class="adm-table__actions">Fiche</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($abonnements as $abo)
                            @php $reste = $abo->daysRemaining(); @endphp

                            <tr>
                                <td>
                                    <span class="adm-cell-id">
                                        <span class="adm-avatar adm-avatar--sm" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($abo->user?->name ?? '?', 0, 2)) }}
                                        </span>
                                        <span class="adm-cell-id__texte">
                                            <span class="adm-table__principal">
                                                {{ $abo->user?->name ?? 'Compte supprimé' }}
                                            </span>
                                            <span class="adm-table__second">{{ $abo->user?->email }}</span>
                                        </span>
                                    </span>
                                </td>

                                <td>
                                    {{ $abo->plan?->name ?? '—' }}
                                    @if ($abo->isTrial())
                                        <span class="adm-table__second">essai</span>
                                    @endif
                                </td>

                                <td class="adm-table__second">{{ $abo->starts_at?->format('d/m/Y') ?? '—' }}</td>
                                <td class="adm-table__second">{{ $abo->ends_at?->format('d/m/Y') ?? 'sans terme' }}</td>

                                <td>
                                    {{-- Le nombre de jours restants est la colonne qu'on
                                         vient lire. Trois seuils, pas un dégradé : on
                                         agit ou on n'agit pas. --}}
                                    @if ($reste === null)
                                        <span class="adm-table__second">—</span>
                                    @elseif ($reste <= 3)
                                        <x-badge variant="danger">{{ $reste }} j</x-badge>
                                    @elseif ($reste <= 7)
                                        <x-badge variant="warning">{{ $reste }} j</x-badge>
                                    @else
                                        <span class="adm-table__second">{{ $reste }} j</span>
                                    @endif
                                </td>

                                <td><x-badge :status="$abo->status" /></td>

                                <td class="adm-table__actions">
                                    @if ($abo->user)
                                        <a class="adm-lien" href="{{ route('admin.clients.show', $abo->user) }}">
                                            Consulter
                                        </a>
                                    @else
                                        <span class="adm-table__second">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="adm-pied">
                <p class="adm-pied__compte">
                    {{ __('admin.commun.affichage', [
                        'debut' => $abonnements->firstItem(),
                        'fin' => $abonnements->lastItem(),
                    ]) }}
                    {{ trans_choice('admin.commun.entites.abonnements', $abonnements->total(), [
                        'compte' => \App\Support\Formats::nombre($abonnements->total()),
                    ]) }}
                </p>
                {{ $abonnements->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
