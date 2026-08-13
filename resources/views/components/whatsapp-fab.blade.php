{{--
  x-whatsapp-fab — le bouton d'aide flottant.

      <x-whatsapp-fab />
      <x-whatsapp-fab message="Bonjour, j'ai une question sur mon abonnement." />

  UN SEUL COMPOSANT POUR LES DEUX ESPACES. Le message pré-rempli change selon
  l'endroit — un visiteur de la page d'accueil et un client déjà abonné ne
  posent pas la même question — mais le bouton, lui, est le même partout.

  IL N'APPARAÎT PAS SI LE NUMÉRO N'EST PAS CONFIGURÉ. Un bouton d'aide qui
  mène à une conversation vide avec un numéro inexistant est pire que pas de
  bouton : quelqu'un qui a un problème en rencontre un second.

  target="_blank" avec rel="noopener" : WhatsApp s'ouvre à côté, sans faire
  perdre la page en cours — un client au milieu de la création de sa carte ne
  doit pas la quitter pour poser une question. Le `noopener` empêche la page
  ouverte d'accéder à la nôtre.

  LE LIBELLÉ EST MASQUÉ SUR PETIT ÉCRAN, jamais supprimé : il reste lisible
  par un lecteur d'écran, et le bouton garde un nom accessible.
--}}
@props([
    'message' => null,
])

@php
    $numero = trim((string) config('landing.support.whatsapp'));
@endphp

@if ($numero !== '')
    @php
        // wa.me n'accepte QUE des chiffres : un « + » ou une espace dans le
        // numéro produit un lien qui s'ouvre sur une erreur WhatsApp.
        $chiffres = preg_replace('/\D+/', '', $numero);

        $lien = 'https://wa.me/'.$chiffres
            .($message ? '?text='.rawurlencode($message) : '');
    @endphp

    <a href="{{ $lien }}"
       class="wa-fab"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Nous écrire sur WhatsApp">

        <svg class="wa-fab__icone" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97s-.47-.15-.67.15-.77.96-.94 1.16-.35.22-.65.07a8.1 8.1 0 0 1-2.39-1.47 9 9 0 0 1-1.65-2.06c-.17-.3-.02-.46.13-.61s.3-.35.45-.52.2-.3.3-.5.05-.37-.02-.52-.67-1.61-.92-2.21c-.24-.58-.49-.5-.67-.51h-.57a1.1 1.1 0 0 0-.8.37 3.35 3.35 0 0 0-1.04 2.48 5.8 5.8 0 0 0 1.22 3.09 13.3 13.3 0 0 0 5.09 4.5c.71.3 1.27.49 1.7.63a4.1 4.1 0 0 0 1.88.12 3.07 3.07 0 0 0 2.01-1.42 2.5 2.5 0 0 0 .17-1.42c-.07-.12-.27-.2-.57-.35z"/>
            <path d="M12.04 2a9.9 9.9 0 0 0-8.5 15.02L2 22.5l5.62-1.47A9.9 9.9 0 1 0 12.04 2m0 1.67a8.23 8.23 0 1 1-4.19 15.31l-.3-.18-3.34.87.89-3.25-.2-.31A8.23 8.23 0 0 1 12.04 3.67"/>
        </svg>

        <span class="wa-fab__texte">Une question&nbsp;?</span>
    </a>
@endif
