{{--
  x-form-legende — la légende qui explique l'astérisque.

      <x-form-legende />

  ═══════════════════════════════════════════════════════════════════════
  POURQUOI UNE LÉGENDE, ET POURQUOI EN TÊTE
  ═══════════════════════════════════════════════════════════════════════
  Un astérisque rouge est une convention, pas une évidence : elle se lit chez
  qui a déjà rempli des formulaires en ligne, et pas ailleurs. Sur un produit
  dont une partie des clients découvre l'administratif numérique, l'expliquer
  une fois coûte une ligne et lève un doute.

  EN TÊTE et non en pied : une légende placée après les champs arrive quand on
  s'est déjà demandé ce que signifiait la marque.

  Props : texte
--}}
@props(['texte' => null])

<p {{ $attributes->merge(['class' => 'f__legende']) }}>
    <span class="f__requis" aria-hidden="true">*</span>
    {{ $texte ?? __('common.champs.astensque') }}
</p>
