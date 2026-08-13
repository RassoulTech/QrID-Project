{{--
  Bouton d'action. Écrit une fois, repris par tous les e-mails.

  Pourquoi un <a> stylé et non un <button> : aucun client de messagerie ne
  soumet de formulaire. Le padding est sur le lien lui-même, jamais sur un
  conteneur — Outlook ignore le padding d'un bloc et le bouton s'effondre.

  $ton : 'vert' par défaut. 'sombre' pour les messages de sécurité, où le
  vert de la marque suggérerait à tort une bonne nouvelle.
--}}
@php
    $fond = ($ton ?? 'vert') === 'sombre' ? '#1E293B' : '#0B5D3B';
@endphp

<p style="margin:0 0 24px;" align="center">
    <a href="{{ $url }}"
       style="display:inline-block;background:{{ $fond }};color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:8px;font-weight:bold;font-size:16px;">
        {{ $libelle }}
    </a>
</p>
