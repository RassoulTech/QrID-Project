{{-- PIED DE PAGE — fond très sombre. Textes de la maquette, mot pour mot. --}}
<footer class="site-footer">
  <div class="wrap site-footer__grid">

    <div>
      <x-brand tone="light" :link="false" class="site-footer__brand" />
      <p class="site-footer__about">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Excellence Professionnelle Sénégalaise.
        La référence pour votre présence digitale sécurisée.
      </p>
    </div>

    <div>
      <p class="site-footer__title">Plateforme</p>
      <a href="{{ route('home') }}#produits">Produits</a>
      <a href="{{ route('home') }}#ressources">Ressources</a>
      <a href="{{ route('home') }}#tarifs">Tarifs</a>
    </div>

    <div>
      <p class="site-footer__title">Légal</p>
      <a href="{{ route('legal.conditions') }}">CGU</a>
      <a href="{{ route('legal.confidentialite') }}">Confidentialité</a>
    </div>

    <div>
      <p class="site-footer__title">Support</p>
      <a href="{{ route('legal.mentions') }}">Aide</a>
      <a href="{{ config('registration.support_whatsapp') }}" target="_blank" rel="noopener">Contact</a>
    </div>

  </div>
</footer>
