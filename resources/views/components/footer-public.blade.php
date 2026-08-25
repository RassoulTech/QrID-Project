{{-- PIED DE PAGE — fond très sombre. Textes de la maquette, mot pour mot. --}}
<footer class="site-footer">
  <div class="wrap site-footer__grid">

    <div>
      <x-brand tone="light" :link="false" class="site-footer__brand" />
      <p class="site-footer__about">
        &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('navigation.pied.about') }}
        {{ __('navigation.pied.about_suite') }}
      </p>
    </div>

    <div>
      <p class="site-footer__title">{{ __('navigation.pied.plateforme') }}</p>
      <a href="{{ route('home') }}#produits">{{ __('navigation.public.produits') }}</a>
      <a href="{{ route('home') }}#ressources">{{ __('navigation.public.ressources') }}</a>
      <a href="{{ route('home') }}#tarifs">{{ __('navigation.public.tarifs') }}</a>
    </div>

    <div>
      <p class="site-footer__title">{{ __('navigation.pied.legal') }}</p>
      <a href="{{ route('legal.conditions') }}">{{ __('navigation.pied.cgu') }}</a>
      <a href="{{ route('legal.confidentialite') }}">{{ __('navigation.pied.confidentialite') }}</a>
    </div>

    <div>
      <p class="site-footer__title">{{ __('navigation.pied.support') }}</p>
      <a href="{{ route('legal.mentions') }}">{{ __('navigation.coque.aide') }}</a>
      <a href="{{ config('registration.support_whatsapp') }}" target="_blank" rel="noopener">{{ __('navigation.public.contact') }}</a>
    </div>

  </div>
</footer>
