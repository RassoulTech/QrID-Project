{{-- BANDEAU DES MÉTIERS — défilement horizontal lent en boucle, CSS pur,
     indépendant du défilement de la page.

     La liste est dupliquée : la seconde copie prend la place de la première
     à -50 %, ce qui rend la boucle continue et invisible.
     Props : $trades --}}
<section class="trades" id="produits" aria-label="{{ __('Métiers concernés') }}">
  <div class="trades__row">
    @foreach ($trades as $trade)
      <span class="trades__item{{ in_array($loop->index, [1, 3]) ? ' trades__item--strong' : '' }}">
        {{ __($trade) }}
      </span>
    @endforeach

    {{-- Copie pour la continuité de la boucle --}}
    @foreach ($trades as $trade)
      <span class="trades__item{{ in_array($loop->index, [1, 3]) ? ' trades__item--strong' : '' }}"
            aria-hidden="true">
        {{ __($trade) }}
      </span>
    @endforeach
  </div>
</section>
