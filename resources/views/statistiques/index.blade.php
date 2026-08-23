{{--
  STATISTIQUES — chiffres réels, agrégés en SQL.

  Sans aucune donnée, on n'affiche PAS un graphique vide : on explique quoi
  faire pour qu'il se remplisse.
--}}
<x-app-layout title="Statistiques">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">Statistiques</h1>
            <p class="db-tete__sous">L'audience de votre carte, jour par jour.</p>
        </div>

        {{-- MÊME CONTRÔLE QUE DANS L'ADMINISTRATION. Les segments d'origine
             tenaient à trois périodes ; le jour où l'on en ajoute une
             quatrième, ils débordent sur téléphone. Une liste déroulante ne
             grandit pas, et c'est déjà celle des six écrans de listes.

             Formulaire GET : la période reste dans l'URL, le lien se partage.
             Sans JavaScript, le bouton du <noscript> prend le relais. --}}
        <form method="GET" action="{{ route('statistiques') }}" class="adm-filtres adm-filtres--nu">
            <div class="adm-filtre">
                <label for="periode">Période affichée</label>
                <select id="periode" name="periode" class="adm-select" onchange="this.form.submit()">
                    @foreach ($periodes as $p)
                        <option value="{{ $p }}" @selected($p === $periode)>{{ $p }} derniers jours</option>
                    @endforeach
                </select>
            </div>

            <noscript><button type="submit" class="adm-btn adm-btn--vert">Appliquer</button></noscript>
        </form>
    </div>

    <div class="db-grille">
        <div class="db-principal">

            {{-- ===================== TOTAUX ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">Sur {{ $periode }} jours</h2>

                <div class="stat-grid">
                    <div class="stat-tuile stat-tuile--1">
                        <span class="stat-tuile__n">{{ number_format($totaux['views'], 0, ',', ' ') }}</span>
                        <span class="stat-tuile__l">Vues directes</span>
                    </div>
                    <div class="stat-tuile stat-tuile--2">
                        <span class="stat-tuile__n">{{ number_format($totaux['scans'], 0, ',', ' ') }}</span>
                        <span class="stat-tuile__l">Scans du QR Code</span>
                    </div>
                    <div class="stat-tuile stat-tuile--3">
                        <span class="stat-tuile__n">{{ number_format($totaux['saves'], 0, ',', ' ') }}</span>
                        <span class="stat-tuile__l">Contacts enregistrés</span>
                    </div>
                    <div class="stat-tuile stat-tuile--4">
                        <span class="stat-tuile__n">{{ number_format($totaux['total'], 0, ',', ' ') }}</span>
                        <span class="stat-tuile__l">Total des événements</span>
                    </div>
                </div>
            </section>

            {{-- ===================== ÉVOLUTION ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">Évolution</h2>

                @if ($serie)
                    @php
                        $max = max(array_map(fn ($j) => $j['vues'] + $j['scans'], $serie)) ?: 1;
                    @endphp

                    <div class="chart" role="img"
                         aria-label="Vues et scans sur les {{ $periode }} derniers jours">
                        @foreach ($serie as $jour)
                            <span class="chart__col"
                                  title="{{ $jour['jour'] }} · {{ $jour['vues'] }} vue(s), {{ $jour['scans'] }} scan(s)">
                                <span class="chart__pile">
                                    @if ($jour['scans'] > 0)
                                        <span class="chart__barre chart__barre--scan"
                                              style="height:{{ round($jour['scans'] / $max * 100) }}%"></span>
                                    @endif
                                    @if ($jour['vues'] > 0)
                                        <span class="chart__barre"
                                              style="height:{{ round($jour['vues'] / $max * 100) }}%"></span>
                                    @endif
                                </span>
                                <span class="chart__jour">{{ $jour['libelle'] }}</span>
                            </span>
                        @endforeach
                    </div>

                    <div class="chart-legende">
                        <span><span class="chart-legende__puce"></span> Vues directes</span>
                        <span><span class="chart-legende__puce chart-legende__puce--scan"></span> Scans</span>
                    </div>

                    {{-- Voir dashboard/partials/activity-chart :
                         « height:1px » ne borne pas un <table>. --}}
                    <div class="visually-hidden">
                    <table>
                        <caption>Vues et scans par jour</caption>
                        <thead><tr><th>Jour</th><th>Vues</th><th>Scans</th></tr></thead>
                        <tbody>
                            @foreach ($serie as $jour)
                                <tr>
                                    <td>{{ $jour['jour'] }}</td>
                                    <td>{{ $jour['vues'] }}</td>
                                    <td>{{ $jour['scans'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="db-vide">
                        <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M2 2h2v2H2z"/>
                            <path d="M6 0v6H0V0zM5 1H1v4h4zM4 12H2v2h2z"/>
                            <path d="M6 10v6H0v-6zm-5 1v4h4v-4zm11-9h2v2h-2z"/>
                            <path d="M10 0v6h6V0zm5 1v4h-4V1zM8 8v2H6V8zm2 2V8h2v2zm-2 2v-2H6v2zm2 0h2v-2h-2zm4 0v2h-2v-2z"/>
                        </svg>
                        <p class="db-vide__titre">Aucun événement sur cette période</p>
                        <p class="db-vide__texte">
                            Partagez votre QR Code ou votre lien&nbsp;: chaque
                            consultation apparaîtra ici.
                        </p>
                        <p class="mt-3">
                            <x-button :href="route('carte.qr')" size="sm">Voir mon QR Code</x-button>
                        </p>
                    </div>
                @endif
            </section>

            {{-- ===================== DERNIERS ÉVÉNEMENTS ===================== --}}
            <section class="db-card">
                <h2 class="db-card__titre">Derniers événements</h2>

                @forelse ($derniers as $evenement)
                    <div class="visite">
                        <span @class([
                                'visite__pastille',
                                'visite__pastille--scan' => $evenement->type === 'scan',
                              ]) aria-hidden="true">
                            @if ($evenement->type === 'scan')
                                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h2v2H2z"/><path d="M6 0v6H0V0zM5 1H1v4h4zM4 12H2v2h2z"/><path d="M6 10v6H0v-6zm-5 1v4h4v-4zm11-9h2v2h-2z"/><path d="M10 0v6h6V0zm5 1v4h-4V1z"/></svg>
                            @elseif ($evenement->type === 'save')
                                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4"/></svg>
                            @else
                                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg>
                            @endif
                        </span>

                        <span class="visite__texte">
                            <span class="visite__type">
                                @switch($evenement->type)
                                    @case('scan') Scan du QR Code @break
                                    @case('save') Contact enregistré @break
                                    @default Consultation directe
                                @endswitch
                            </span>
                            <span class="visite__date">{{ $evenement->created_at?->diffForHumans() }}</span>
                        </span>
                    </div>
                @empty
                    <p class="db-vide__texte db-vide__texte--serre">
                        Aucun événement enregistré pour l'instant.
                    </p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
