{{-- ============================================================================
     LE PIED DE PAGE PUBLIC — direction A (maquette desk-1).

     Couverture profonde, lisière tissée en tête (elle « ferme » la page).
     Marque et promesse, puis trois colonnes : Produit, Aide, Légal. En bas,
     la ligne des moyens de paiement — ceux que le produit encaisse
     réellement (Wave, Orange Money, Free Money), en FCFA.

     L'aide WhatsApp vit ICI sur la vitrine, et non plus en bulle flottante :
     elle ne recouvre plus rien.
============================================================================ --}}
<footer class="site-footer">
  <div class="wrap">
    <div class="site-footer__grid">

      <div>
        <x-brand tone="light" :link="false" class="site-footer__brand" />
        <p class="site-footer__about">{{ __('navigation.pied.description') }}</p>
      </div>

      <div>
        <p class="site-footer__title">{{ __('navigation.pied.produit') }}</p>
        <ul class="site-footer__liens">
          <li><a href="{{ route('home') }}#fonctionnement">{{ __('navigation.public.fonctionnement') }}</a></li>
          <li><a href="{{ route('home') }}#tarifs">{{ __('navigation.public.tarifs') }}</a></li>
          <li><a href="{{ route('home') }}#carte-pvc">{{ __('navigation.public.carte_pvc') }}</a></li>
          <li><a href="{{ route('profile.demo') }}">{{ __('navigation.pied.exemple_carte') }}</a></li>
        </ul>
      </div>

      <div>
        <p class="site-footer__title">{{ __('navigation.pied.aide') }}</p>
        <ul class="site-footer__liens">
          <li><a href="{{ route('home') }}#contact">{{ __('navigation.pied.nous_ecrire') }}</a></li>
        </ul>
        <x-whatsapp-fab variante="lien" />
      </div>

      <div>
        <p class="site-footer__title">{{ __('navigation.pied.legal') }}</p>
        <ul class="site-footer__liens">
          <li><a href="{{ route('legal.conditions') }}">{{ __('navigation.pied.conditions') }}</a></li>
          <li><a href="{{ route('legal.confidentialite') }}">{{ __('navigation.pied.confidentialite') }}</a></li>
          <li><a href="{{ route('legal.mentions') }}">{{ __('navigation.pied.mentions') }}</a></li>
        </ul>
      </div>

    </div>

    <div class="site-footer__base">
      <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
      <span>{{ __('navigation.pied.paiement') }}</span>
    </div>
  </div>
</footer>
