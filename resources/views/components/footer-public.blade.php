{{-- PIED DE PAGE — fond très sombre. Textes de la maquette, mot pour mot. --}}
<footer class="site-footer">
  <div class="wrap site-footer__grid">

    <div>
      <x-brand tone="light" :link="false" class="site-footer__brand" />
      <p class="site-footer__about">
        &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Excellence Professionnelle Sénégalaise.') }}
        {{ __('La référence pour votre présence digitale sécurisée.') }}
      </p>
    </div>

    <div>
      <p class="site-footer__title">{{ __('Plateforme') }}</p>
      <a href="{{ route('home') }}#produits">{{ __('Produits') }}</a>
      <a href="{{ route('home') }}#ressources">{{ __('Ressources') }}</a>
      <a href="{{ route('home') }}#tarifs">{{ __('Tarifs') }}</a>
    </div>

    <div>
      <p class="site-footer__title">{{ __('Légal') }}</p>
      <a href="{{ route('legal.conditions') }}">{{ __('CGU') }}</a>
      <a href="{{ route('legal.confidentialite') }}">{{ __('Confidentialité') }}</a>
    </div>

    <div>
      <p class="site-footer__title">{{ __('Support') }}</p>
      <a href="{{ route('legal.mentions') }}">{{ __('Aide') }}</a>
      <a href="{{ config('registration.support_whatsapp') }}" target="_blank" rel="noopener">{{ __('Contact') }}</a>
    </div>

  </div>
</footer>
