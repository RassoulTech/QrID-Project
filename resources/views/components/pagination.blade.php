{{--
  x-pagination — pagination stylée du produit.

  <x-pagination :paginator="$profils" />

  Props : paginator (LengthAwarePaginator)
  Rappel : toute liste doit être paginée (règle du projet).
  Le rendu utilise la vue Bootstrap 5 fournie par Laravel, plus un compteur
  de résultats lisible.
--}}
@props(['paginator'])

@if ($paginator && $paginator->hasPages())
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-4">
        <p class="text-secondary small mb-0">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            {{ __('common.pagination.sur') }}
            {{ trans_choice('common.pagination.resultat', $paginator->total(), [
                'compte' => \App\Support\Formats::nombre($paginator->total()),
            ]) }}
        </p>

        {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@elseif ($paginator && $paginator->total() > 0)
    <p class="text-secondary small mb-0 mt-3">
        {{ trans_choice('common.pagination.resultat', $paginator->total(), [
            'compte' => \App\Support\Formats::nombre($paginator->total()),
        ]) }}
    </p>
@endif
