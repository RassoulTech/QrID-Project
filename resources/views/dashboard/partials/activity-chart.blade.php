{{--
  Activité récente — histogramme des vues, RENDU CÔTÉ SERVEUR.

  Aucune bibliothèque de graphiques, aucun canvas : des <div> dont la hauteur
  est un pourcentage. Cela tient en quelques lignes de CSS, s'imprime, se lit
  au lecteur d'écran grâce au tableau de secours, et ne dépend d'aucun script.

  Le sélecteur de période est un jeu de LIENS vers la même page avec
  ?periode=N : l'état vit dans l'URL, il est partageable et rechargeable.

  Quand il n'y a aucune vue, le contrôleur renvoie null : on n'affiche jamais
  un graphique plat, qui laisserait croire à une panne.
--}}
<section class="db-card">
    <div class="db-card__tete">
        <h2 class="db-card__titre">{{ __('dashboard.activite.titre') }}</h2>

        <div class="periode" role="group" aria-label="{{ __('dashboard.activite.periode_aria') }}">
            @foreach ($periodes as $p)
                <a href="{{ route('dashboard', ['periode' => $p]) }}"
                   @class(['periode__item', 'is-active' => $p === $periode])
                   @if ($p === $periode) aria-current="true" @endif>
                    {{ __('dashboard.activite.periode_jours', ['compte' => $p]) }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($serie)
        @php $max = max(array_column($serie, 'total')) ?: 1; @endphp

        <div class="chart" role="img"
             aria-label="{{ __('dashboard.activite.graphique_aria', ['compte' => $periode]) }}">
            @foreach ($serie as $jour)
                {{-- trans_choice : le « s » ajouté à la main n'existe pas dans
                     toutes les langues, et n'y suit pas la même règle. --}}
                <span class="chart__col" title="{{ trans_choice('dashboard.activite.infobulle', $jour['total'], ['jour' => $jour['jour'], 'compte' => $jour['total']]) }}">
                    <span class="chart__barre" style="height:{{ max(3, round($jour['total'] / $max * 100)) }}%"></span>
                    <span class="chart__jour">{{ $jour['libelle'] }}</span>
                </span>
            @endforeach
        </div>

        {{-- Équivalent textuel : un histogramme en CSS reste illisible pour un
             lecteur d'écran. Le tableau porte les mêmes chiffres. --}}
        {{-- LA CLASSE EST SUR L'ENVELOPPE, PAS SUR LE TABLEAU.
             « height:1px » ne borne pas un <table> : la spécification traite
             la hauteur d'un tableau comme un MINIMUM. Posée directement sur
             lui, .visually-hidden le laissait à sa hauteur naturelle — 262px
             mesurés — en position absolue, ce qui rallongeait le document de
             198px et faisait apparaître une bande blanche sous la page.
             Un <div> obéit, lui. --}}
        <div class="visually-hidden">
        <table>
            <caption>{{ __('dashboard.activite.tableau_titre') }}</caption>
            <thead><tr><th>{{ __('dashboard.activite.colonne_jour') }}</th><th>{{ __('dashboard.activite.colonne_vues') }}</th></tr></thead>
            <tbody>
                @foreach ($serie as $jour)
                    <tr><td>{{ $jour['jour'] }}</td><td>{{ $jour['total'] }}</td></tr>
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
            <p class="db-vide__titre">{{ __('dashboard.activite.vide_titre') }}</p>
            <p class="db-vide__texte">{!! __('dashboard.activite.vide_texte') !!}</p>
        </div>
    @endif
</section>
