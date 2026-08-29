{{--
  STATISTIQUES — l'usage réel des cartes.

  ÉCRAN SANS MAQUETTE. Sa raison d'être tient en une phrase : la vue
  d'ensemble dit où en est l'ENTREPRISE, celui-ci dit si les CARTES SERVENT.
  Un produit peut vendre beaucoup et n'être jamais utilisé — c'est ce que cet
  écran rend visible, et que le premier masquerait.

  Toutes les agrégations sont en SQL. Le classement des profils tient dans
  UNE requête à trois sous-agrégats, pas une par ligne.
--}}
<x-admin-layout
    :title="__('admin.statistiques.titre')"
    :subtitle="__('admin.statistiques.sous_titre')"
>
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.statistics') }}" class="adm-filtres adm-filtres--nu">
            <div class="adm-filtre">
            <label for="periode">{{ __('admin.commun.periode') }}</label>
            <select id="periode" name="periode" class="adm-select" onchange="this.form.submit()">
                @foreach ($periodes as $cle)
                    <option value="{{ $cle }}" @selected($cle === $periode)>{{ $libellesPeriode[$cle] }}</option>
                @endforeach
            </select>
            </div>
            <noscript><button type="submit" class="adm-btn adm-btn--clair">Appliquer</button></noscript>
        </form>

        <a href="{{ route('admin.statistics.export', ['periode' => $periode]) }}" class="adm-btn adm-btn--clair">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            {{ __('admin.commun.exporter_csv') }}
        </a>
    </x-slot:actions>

    {{-- ==================== LES QUATRE COMPTEURS ==================== --}}
    @php
        $cartes = [
            [__('admin.statistiques.interactions_totales'), $totaux['total'], true],
            ['Consultations', $totaux['vues'], false],
            [__('admin.statistiques.scans_qr'), $totaux['scans'], false],
            ['Enregistrements', $totaux['saves'], false],
        ];
    @endphp

    <div class="adm-stats" style="grid-template-columns:repeat(2,1fr)">
        @foreach ($cartes as [$libelle, $valeur, $vedette])
            <div @class(['adm-stat', 'adm-stat--vedette' => $vedette])>
                <span class="adm-stat__libelle">{{ $libelle }}</span>
                {{-- Le zéro est affiché : aucune interaction sur la période est
                     une information, pas une case à laisser vide. --}}
                <span class="adm-stat__valeur">{{ number_format($valeur, 0, ',', ' ') }}</span>

                @if (! $vedette && $totaux['total'] > 0)
                    <span class="adm-stat__var is-neutre">
                        {{ round($valeur / $totaux['total'] * 100) }} {{ __('admin.statistiques.part_interactions') }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ==================== SÉRIE JOURNALIÈRE ==================== --}}
    <div class="adm-bloc" style="margin-bottom:14px">
        <div class="adm-bloc__tete">
            <h2 class="adm-bloc__titre">{{ __('admin.statistiques.interactions_par_jour') }}</h2>
            <p class="adm-legende"><span class="adm-legende__puce"></span>{{ __('admin.statistiques.toutes_interactions') }}</p>
        </div>

        @if ($serie->sum('valeur') === 0)
            <x-empty-state icon="search"
                :title="__('admin.statistiques.aucune_interaction_titre')"
                :message="__('admin.statistiques.aucune_interaction_message')" />
        @else
            @php $max = max(1, $serie->max('valeur')); @endphp

            <div class="adm-chart" role="img"
                 aria-label="Interactions par jour : {{ $serie->map(fn ($p) => $p['libelle'].' : '.$p['valeur'])->implode(', ') }}">
                @foreach ($serie as $point)
                    <div class="adm-chart__col">
                        <div class="adm-chart__barre" style="height: {{ max(2, round($point['valeur'] / $max * 100)) }}%">
                            <span class="adm-chart__valeur">{{ $point['valeur'] }}</span>
                        </div>
                        <span class="adm-chart__libelle">{{ $point['libelle'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="adm-grille adm-grille--2-1">

        {{-- ==================== CLASSEMENT DES PROFILS ==================== --}}
        <section class="adm-bloc">
            <div class="adm-bloc__tete">
                <h2 class="adm-bloc__titre">{{ __('admin.statistiques.cartes_plus_consultees') }}</h2>
                <a class="adm-card__lien" href="{{ route('admin.profiles.index') }}">{{ __('admin.statistiques.tous_profils') }}</a>
            </div>

            @if ($classement->isEmpty())
                <x-empty-state icon="profile"
                    :title="__('admin.statistiques.aucune_carte_consultee_titre')"
                    :message="__('admin.statistiques.aucune_carte_consultee_message')" />
            @else
                <div class="table-scroll">
                    <table class="adm-table" style="min-width:520px">
                        <thead>
                            <tr>
                                <th scope="col">Carte</th>
                                <th scope="col">Vues</th>
                                <th scope="col">Scans</th>
                                <th scope="col">Enreg.</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classement as $ligne)
                                <tr>
                                    <td>
                                        <span class="adm-cell-id__texte">
                                            <span class="adm-table__principal">
                                                {{ trim($ligne->first_name.' '.$ligne->last_name) }}
                                            </span>
                                            <span class="adm-table__second">/p/{{ $ligne->slug }}</span>
                                        </span>
                                    </td>
                                    <td>{{ number_format((int) $ligne->vues, 0, ',', ' ') }}</td>
                                    <td>{{ number_format((int) $ligne->scans, 0, ',', ' ') }}</td>
                                    <td>{{ number_format((int) $ligne->saves, 0, ',', ' ') }}</td>
                                    <td class="adm-table__principal">
                                        {{ number_format((int) $ligne->total, 0, ',', ' ') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="adm-colonne">

            {{-- ==================== ÉTAT DE PUBLICATION ==================== --}}
            <section class="adm-bloc">
                <h2 class="adm-bloc__titre" style="margin-bottom:12px">{{ __('admin.statistiques.etat_profils') }}</h2>

                @php
                    $etats = [
                        [__('admin.statistiques.publies'), $publication['publies'], 'success'],
                        ['Brouillons', $publication['brouillons'], 'secondary'],
                        [__('admin.statistiques.desactives'), $publication['desactives'], 'danger'],
                    ];
                    $totalProfils = max(1, $publication['tous']);
                @endphp

                <ul class="adm-parts">
                    @foreach ($etats as [$libelle, $nombre, $ton])
                        <li class="adm-part">
                            <span class="adm-part__nom">{{ $libelle }}</span>
                            <span class="adm-part__chiffre">{{ $nombre }}</span>
                            <span class="adm-part__piste">
                                <span class="adm-part__barre"
                                      style="width: {{ round($nombre / $totalProfils * 100) }}%"></span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- ==================== RÉPARTITION PAR MODÈLE ==================== --}}
            <section class="adm-bloc">
                <h2 class="adm-bloc__titre" style="margin-bottom:12px">{{ __('admin.statistiques.modeles_utilises') }}</h2>

                @if ($parModele->isEmpty())
                    <x-empty-state icon="document"
                        :title="__('admin.statistiques.aucune_carte_publiee_titre')"
                        :message="__('admin.statistiques.aucune_carte_publiee_message')" />
                @else
                    @php $totalModeles = max(1, $parModele->sum('total')); @endphp

                    <ul class="adm-parts">
                        @foreach ($parModele as $m)
                            <li class="adm-part">
                                <span class="adm-part__nom">{{ $m->nom }}</span>
                                <span class="adm-part__chiffre">
                                    {{ round($m->total / $totalModeles * 100) }} %
                                </span>
                                <span class="adm-part__piste">
                                    <span class="adm-part__barre"
                                          style="width: {{ round($m->total / $totalModeles * 100) }}%"></span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-admin-layout>
