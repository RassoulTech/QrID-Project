{{--
  VUE D'ENSEMBLE — écran 1 de l'espace administrateur.

  Tout ce qui s'affiche ici vient de la base, par AdminStatsService. Aucun
  chiffre n'est écrit dans ce gabarit.

  LES ZÉROS SONT AFFICHÉS. Une carte sans chiffre est une carte cassée : un
  zéro est une information, et le masquer laisse croire à une panne.
--}}
<x-admin-layout
    :title="__('admin.vue_ensemble.titre')"
    :subtitle="__('admin.vue_ensemble.sous_titre')"
>
    <x-slot:actions>
        {{-- Sélecteur de période : formulaire GET. La période reste dans
             l'URL, donc le lien envoyé à un collègue montre la même chose. --}}
        <form method="GET" action="{{ route('admin.overview') }}" class="adm-filtres adm-filtres--nu">
            <div class="adm-filtre">
                <label for="periode">{{ __('admin.commun.periode') }}</label>
                <select id="periode" name="periode" class="adm-select" onchange="this.form.submit()">
                    @foreach ($periodes as $cle)
                        <option value="{{ $cle }}" @selected($cle === $periode)>
                            {{ $libellesPeriode[$cle] }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Sans JavaScript, le onchange ne part pas : ce bouton reste la
                 commande réelle. Il n'est masqué que lorsque le script a
                 confirmé qu'il prendra le relais. --}}
            <noscript><button type="submit" class="adm-btn adm-btn--clair">Appliquer</button></noscript>
        </form>

        <a href="{{ route('admin.overview.export', ['periode' => $periode]) }}" class="adm-btn adm-btn--vert">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1v8.6l2.3-2.3 1 1L8 11.7 4.7 8.3l1-1L8 9.6zM2 12h12v2H2z"/>
            </svg>
            Export
        </a>
    </x-slot:actions>

    {{-- ================= LES SIX CARTES ================= --}}
    <div class="adm-stats">
        @foreach ($cartes as $cle => $carte)
            <div @class(['adm-stat', 'adm-stat--vedette' => $loop->first])>
                <span class="adm-stat__libelle">{{ $libellesCartes[$cle] }}</span>

                <span class="adm-stat__valeur">
                    {{ $cle === 'chiffre_affaires'
                        ? number_format($carte['valeur'], 0, ',', ' ')
                        : number_format($carte['valeur'], 0, ',', ' ') }}
                    @if ($cle === 'chiffre_affaires')
                        <small>FCFA</small>
                    @endif
                </span>

                @if ($carte['variation'] === null)
                    {{-- Aucune base de comparaison : un tiret, pas un « +100 % »
                         qui impressionnerait sans rien vouloir dire. --}}
                    <span class="adm-stat__var is-neutre">{{ __('admin.vue_ensemble.sur_periode_precedente') }}</span>
                @else
                    <span @class(['adm-stat__var', 'is-hausse' => $carte['variation'] >= 0, 'is-baisse' => $carte['variation'] < 0])>
                        {{ $carte['variation'] >= 0 ? '+' : '' }}{{ number_format($carte['variation'], 1, ',', ' ') }} %
                        <span class="adm-stat__ref">{{ __('admin.vue_ensemble.sur_periode_precedente') }}</span>
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ================= HISTOGRAMME + ALERTE ================= --}}
    <div class="adm-grille adm-grille--2-1">

        <section class="adm-bloc">
            <div class="adm-bloc__tete">
                <h2 class="adm-bloc__titre">{{ __('admin.vue_ensemble.tendance_inscriptions') }}</h2>

                <p class="adm-legende">
                    <span class="adm-legende__puce"></span>
                    {{ __('admin.vue_ensemble.nouveaux_comptes') }}
                </p>
            </div>

            @if ($tendance->sum('valeur') === 0)
                <x-empty-state
                    :title="__('admin.vue_ensemble.aucune_inscription_periode_titre')"
                    :message="__('admin.vue_ensemble.aucune_inscription_periode_message')" />
            @else
                @php $max = max(1, $tendance->max('valeur')); @endphp

                {{-- Histogramme en HTML et CSS. Pas de bibliothèque de
                     graphiques : douze barres ne justifient pas 200 ko de
                     JavaScript, et un graphique qui ne se dessine pas est un
                     écran vide au moment où l'on en a besoin.

                     Les valeurs sont dans le balisage : un lecteur d'écran
                     lit le tableau des chiffres, pas une image muette. --}}
                <div class="adm-chart" role="img"
                     aria-label="Inscriptions par période : {{ $tendance->map(fn ($p) => $p['libelle'].' : '.$p['valeur'])->implode(', ') }}">
                    @foreach ($tendance as $point)
                        <div class="adm-chart__col">
                            <div class="adm-chart__barre" style="height: {{ max(2, round($point['valeur'] / $max * 100)) }}%">
                                <span class="adm-chart__valeur">{{ $point['valeur'] }}</span>
                            </div>
                            <span class="adm-chart__libelle">{{ $point['libelle'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="adm-colonne">
            {{-- ENCADRÉ D'ALERTE — n'existe que s'il a quelque chose à dire.
                 Un bandeau permanent « tout va bien » devient un élément de
                 décor en trois jours, et le jour où il change, personne ne le
                 voit. --}}
            @if ($alerte)
                <section @class(['adm-alerte', 'adm-alerte--'.$alerte['ton']])>
                    <h2 class="adm-alerte__titre">{{ $alerte['titre'] }}</h2>
                    <p class="adm-alerte__texte">{{ $alerte['texte'] }}</p>
                    <a href="{{ $alerte['action'] }}" class="adm-alerte__lien">{{ $alerte['libelle'] }}</a>
                </section>
            @endif

            {{-- ================= MOYENS DE PAIEMENT ================= --}}
            <section class="adm-bloc">
                <h2 class="adm-bloc__titre">{{ __('admin.vue_ensemble.moyens_paiement') }}</h2>

                @if ($moyens->sum('total') === 0)
                    <x-empty-state icon="payment"
                        :title="__('admin.vue_ensemble.aucun_encaissement_titre')"
                        :message="__('admin.vue_ensemble.aucun_encaissement_message')" />
                @else
                    <ul class="adm-parts">
                        @foreach ($moyens as $moyen)
                            <li class="adm-part">
                                <span class="adm-part__nom">
                                    <span @class(['adm-part__puce', 'adm-part__puce--'.$moyen['cle']])></span>
                                    {{ $moyen['libelle'] }}
                                </span>

                                <span class="adm-part__chiffre">{{ $moyen['part'] }} %</span>

                                <span class="adm-part__piste">
                                    <span @class(['adm-part__barre', 'adm-part__barre--'.$moyen['cle']])
                                          style="width: {{ $moyen['part'] }}%"></span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>

    {{-- ================= LES DEUX LISTES DU BAS ================= --}}
    <div class="adm-grille adm-grille--1-1">

        <section class="adm-bloc">
            <div class="adm-bloc__tete">
                <h2 class="adm-bloc__titre">{{ __('admin.vue_ensemble.dernieres_inscriptions') }}</h2>
                <a href="{{ route('admin.clients.index') }}" class="adm-lien">{{ __('admin.commun.voir_tout') }}</a>
            </div>

            @forelse ($inscriptions as $client)
                <div class="adm-ligne">
                    <span class="adm-avatar adm-avatar--sm" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}
                    </span>

                    <span class="adm-ligne__texte">
                        <span class="adm-ligne__nom">{{ $client->name }}</span>
                        <span class="adm-ligne__meta">{{ $client->email }}</span>
                    </span>

                    <span class="adm-ligne__droite">
                        <span class="adm-ligne__meta">{{ $client->created_at?->diffForHumans() }}</span>
                    </span>
                </div>
            @empty
                <x-empty-state icon="profile"
                    :title="__('admin.vue_ensemble.aucune_inscription_titre')"
                    :message="__('admin.vue_ensemble.aucune_inscription_message')" />
            @endforelse
        </section>

        <section class="adm-bloc">
            <div class="adm-bloc__tete">
                <h2 class="adm-bloc__titre">{{ __('admin.vue_ensemble.derniers_paiements') }}</h2>
                <a href="{{ route('admin.payments.index') }}" class="adm-lien">{{ __('admin.commun.voir_tout') }}</a>
            </div>

            @forelse ($paiements as $paiement)
                <div class="adm-ligne">
                    <span class="adm-avatar adm-avatar--sm" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($paiement->user?->name ?? '??', 0, 2)) }}
                    </span>

                    <span class="adm-ligne__texte">
                        <span class="adm-ligne__nom">{{ $paiement->user?->name ?? 'Compte supprimé' }}</span>
                        <span class="adm-ligne__meta">{{ $paiement->method_label }}</span>
                    </span>

                    <span class="adm-ligne__droite">
                        <span class="adm-ligne__montant">{{ $paiement->formattedAmount() }}</span>
                        <x-badge :variant="$tonsPaiement[$paiement->status]">
                            {{ $libellesPaiement[$paiement->status] }}
                        </x-badge>
                    </span>
                </div>
            @empty
                <x-empty-state icon="payment"
                    :title="__('admin.vue_ensemble.aucun_paiement_titre')"
                    :message="__('admin.vue_ensemble.aucun_paiement_message')" />
            @endforelse
        </section>
    </div>
</x-admin-layout>
