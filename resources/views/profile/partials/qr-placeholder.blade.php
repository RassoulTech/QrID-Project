{{--
  CARTE QR CODE — élément mis en avant du dashboard actif.

  Props :
    $qrPath   chemin du QR Code généré, ou null s'il ne l'est pas encore

  Tant que la génération n'est pas branchée, on affiche un état d'attente
  explicite : un cadre inerte laisserait croire à un bug.

  À BRANCHER : quand la génération existera, remplacer les <span> inactifs par
  des liens vers la route de téléchargement (format PNG et SVG).
--}}
@props(['qrPath' => null])

<div class="qr-card">
    <p class="qr-card__label">Mon QR Code</p>

    <div class="qr-card__frame">
        @if ($qrPath)
            <img src="{{ Storage::url($qrPath) }}" alt="QR Code de votre profil" class="qr-card__img">
        @else
            <div class="qr-card__pending" aria-hidden="true">
                <svg viewBox="0 0 48 48" width="76" height="76" fill="none">
                    <rect x="3" y="3" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2.4"/>
                    <rect x="32" y="3" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2.4"/>
                    <rect x="3" y="32" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2.4"/>
                    <rect x="7.5" y="7.5" width="4" height="4" fill="currentColor"/>
                    <rect x="36.5" y="7.5" width="4" height="4" fill="currentColor"/>
                    <rect x="7.5" y="36.5" width="4" height="4" fill="currentColor"/>
                    <path d="M22 3v6M22 14v5M28 22h-6M40 22h-5M22 28v6M22 40v5M34 34h4v4h-4zM42 34h3M34 42v3"
                          stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                </svg>
            </div>
        @endif
    </div>

    <p class="qr-card__hint">
        @if ($qrPath)
            Vos contacts scannent, votre profil s'ouvre.
        @else
            Génération en cours de mise en place. Votre lien public fonctionne déjà.
        @endif
    </p>

    {{-- Boutons visibles mais explicitement inactifs : des <span>, jamais des
         liens morts. L'utilisateur voit ce qui l'attend sans pouvoir cliquer. --}}
    <div class="qr-card__actions">
        <span class="btn-pill btn-light is-disabled" aria-disabled="true">Télécharger en PNG</span>
        <span class="btn-pill btn-ghost-light is-disabled" aria-disabled="true">Version SVG</span>
    </div>
</div>
