{{--
  x-pagination — le pied de liste : le compte, puis les pages.

      <x-pagination :paginator="$profils" />

  Cases de 44px, page courante en baobab, chiffres tabulaires. Les liens
  sont rendus par la vue Bootstrap de Laravel, habillée par le socle
  (`.page-link`, `.page-item.active`) : une seule apparence de pagination.
--}}
@props(['paginator'])

@if ($paginator && $paginator->hasPages())
    <div class="pagination-pied">
        <p class="pagination-resume">
            <b>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</b>
            {{ __('common.pagination.sur') }}
            {{ trans_choice('common.pagination.resultat', $paginator->total(), [
                'compte' => \App\Support\Formats::nombre($paginator->total()),
            ]) }}
        </p>
        {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@elseif ($paginator && $paginator->total() > 0)
    <p class="pagination-resume pagination-pied">
        {{ trans_choice('common.pagination.resultat', $paginator->total(), [
            'compte' => \App\Support\Formats::nombre($paginator->total()),
        ]) }}
    </p>
@endif
