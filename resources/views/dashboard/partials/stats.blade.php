{{--
  Vue d'ensemble — quatre cartes en grille 2×2, un fond pastel chacune.

  CHAQUE CARTE AFFICHE TOUJOURS SON CHIFFRE, zéro compris.

  La version précédente masquait le nombre quand il valait zéro, pour ne pas
  décourager. C'était une erreur : une carte sans chiffre paraît cassée, et le
  lecteur ne sait plus s'il n'a aucune vue ou si la mesure ne fonctionne pas.
  Un zéro EST une information — il dit que le compteur marche et qu'il attend.

  La phrase d'aide reste, en petit et en gris, sous le libellé, uniquement
  quand la valeur est nulle : elle indique alors quoi faire.
--}}
@php
    /* Le libellé et la phrase d'attente sont DÉRIVÉS de la clé : « views »
       donne dashboard.apercu.views_libelle et views_attente. Une seule
       source, et rien à tenir en double. */
    $cartes = [
        ['cle' => 'views', 'pastel' => 1, 'icone' => 'oeil'],
        ['cle' => 'scans', 'pastel' => 2, 'icone' => 'qr'],
        ['cle' => 'saves', 'pastel' => 3, 'icone' => 'contact'],
        ['cle' => 'days', 'pastel' => 4, 'icone' => 'horloge'],
    ];
@endphp

<section class="db-card">
    <h2 class="db-card__titre">{{ __('dashboard.apercu.titre') }}</h2>

    {{-- `data-compteurs-url` porte l'adresse de relecture. Elle est ici et
         non dans le module : une adresse écrite en dur dans un fichier .js
         cesse d'être vraie au premier changement de route, sans rien signaler. --}}
    <div class="stat-grid" data-compteurs-url="{{ route('dashboard.compteurs') }}">
        @foreach ($cartes as $carte)
            @php $valeur = $stats[$carte['cle']] ?? null; @endphp

            {{-- `days` n'est pas un compteur d'audience mais un décompte
                 d'abonnement : il ne bouge pas quand on scanne une carte, et
                 n'a donc rien à relire. --}}
            <div class="stat-tuile stat-tuile--{{ $carte['pastel'] }}"
                 @if ($carte['cle'] !== 'days') data-compteur="{{ $carte['cle'] }}" @endif>
                <span class="stat-tuile__icone" aria-hidden="true">
                    @switch($carte['icone'])
                        @case('oeil')
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                            @break
                        @case('qr')
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h2v2H2z"/><path d="M6 0v6H0V0zM5 1H1v4h4zM4 12H2v2h2z"/><path d="M6 10v6H0v-6zm-5 1v4h4v-4zm11-9h2v2h-2z"/><path d="M10 0v6h6V0zm5 1v4h-4V1zM8 8v2H6V8zm2 2V8h2v2zm-2 2v-2H6v2zm2 0h2v-2h-2zm4 0v2h-2v-2z"/></svg>
                            @break
                        @case('contact')
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4"/></svg>
                            @break
                        @default
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/></svg>
                    @endswitch
                </span>

                <span class="stat-tuile__n" data-compteur-valeur>{{ number_format((int) $valeur, 0, ',', ' ') }}</span>
                <span class="stat-tuile__l">{{ __('dashboard.apercu.'.$carte['cle'].'_libelle') }}</span>

                @if ($valeur === null || $valeur === 0)
                    <span class="stat-tuile__attente" data-compteur-attente>{{ __('dashboard.apercu.'.$carte['cle'].'_attente') }}</span>
                @endif
            </div>
        @endforeach
    </div>
</section>
