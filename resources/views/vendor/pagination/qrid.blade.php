{{--
  PAGINATION — QrID.

  PAS DE LISTE DE NUMÉROS. Sur 61 résultats la liste « 1 2 3 4 5 » paraît
  utile ; sur 3 000 elle devient « 1 2 … 97 98 99 … 200 » et personne ne
  clique jamais sur « 98 ». Ce que l'on fait réellement, c'est avancer d'une
  page, reculer d'une page, et savoir où l'on en est.

  Trois éléments, et trois seulement : Précédent · Page N sur M · Suivant.

  PAS DE CHAMP DE SAUT NON PLUS. Un champ numérique suivi d'un bouton « OK »
  ajoute deux commandes pour un geste que personne ne fait : on ne connaît
  pas d'avance le numéro de la page que l'on cherche. Les filtres, eux,
  servent à cela — et ils sont juste au-dessus.

  Le texte anglais du gabarit Bootstrap (« Showing 16 to 30 of 61 results »)
  a disparu : les écrans affichent déjà leur compteur, en français, avec le
  mot qui convient — clients, paiements, entrées.

  aria-label sur <nav>, aria-disabled sur les bornes : un lecteur d'écran
  annonce « Précédent, indisponible » plutôt qu'un lien muet.
--}}
@if ($paginator->hasPages())
    <nav class="pagin" role="navigation" aria-label="{{ __('pagination.libelle') }}">

        {{-- PRÉCÉDENT --}}
        @if ($paginator->onFirstPage())
            <span class="pagin__lien is-muted" aria-disabled="true">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                </svg>
                <span class="pagin__mot">{{ __('pagination.precedent') }}</span>
            </span>
        @else
            <a class="pagin__lien" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                </svg>
                <span class="pagin__mot">{{ __('pagination.precedent') }}</span>
            </a>
        @endif

        {{-- POSITION --}}
        <span class="pagin__position">
            Page <strong>{{ $paginator->currentPage() }}</strong>
            sur {{ number_format($paginator->lastPage(), 0, ',', ' ') }}
        </span>

        {{-- SUIVANT --}}
        @if ($paginator->hasMorePages())
            <a class="pagin__lien" href="{{ $paginator->nextPageUrl() }}" rel="next">
                <span class="pagin__mot">{{ __('pagination.suivant') }}</span>
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                </svg>
            </a>
        @else
            <span class="pagin__lien is-muted" aria-disabled="true">
                <span class="pagin__mot">{{ __('pagination.suivant') }}</span>
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                </svg>
            </span>
        @endif

    </nav>
@endif
