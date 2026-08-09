{{--
  x-pvc-card — la carte physique, reproduite sur la référence fournie.

      <x-pvc-card :profile="$profile" />
      <x-pvc-card :profile="$profile" size="lg" :flip="false" />
      <x-pvc-card :profile="$profile" layout="duo" :flip="false" />

  ATTENTION : aucun commentaire Blade ne doit être ouvert à l'intérieur de ce
  bloc. Blade ne les imbrique pas — le premier marqueur de fermeture rencontré
  ferme le bloc entier et tout ce qui suit part en texte brut dans la page.

  LES DEUX FACES sont des partials, réutilisés tels quels par le design-system
  pour les montrer isolément : aucune duplication de balisage.

  · RECTO = le PORTEUR : nom, QR Code, fonction.
  · VERSO = la PLATEFORME, à l'identique sur toutes les cartes. Il ne prend
    aucun paramètre et ne lit aucune donnée de profil.

  LE QR EST INVERSÉ : modules blancs posés directement sur le vert, sans cadre,
  comme la référence. Correction d'erreur H et zone de silence de 4 modules
  conservées — voir QrCodeService::invertedSvg().

  FORMAT : ratio 1,586 (85,6 × 54 mm, ISO/IEC 7810 ID-1) porté par
  aspect-ratio, jamais par une hauteur. La typographie est en unités de
  CONTENEUR : le texte suit la largeur réelle de la carte, donc le contenu
  tient à toute largeur, par construction.

  SANS JAVASCRIPT : les deux faces s'affichent l'une sous l'autre et le bouton
  de permutation n'apparaît pas.
--}}
@props([
    'profile',
    'flip' => true,
    'size' => 'md',
    'layout' => 'stack',
])

<div {{ $attributes->merge(['class' => 'pvc pvc--'.$size.' pvc--'.$layout]) }}
     @if ($flip) data-pvc @endif
     style="--pvc-teinte:{{ $profile->primary_color ?: '#0B3B2E' }}">

    <div class="pvc__scene">
        {{-- Le verso ne reçoit le profil QUE pour son code-barres, seul
             élément de cette face qui dépende du porteur. Tout le reste y est
             identique sur les cartes de tous les clients. --}}
        @include('components.pvc-card-face-recto', ['profile' => $profile])
        <x-pvc-card-face-verso :profile="$profile" />
    </div>

    @if ($flip)
        <div class="pvc__commande" data-pvc-commande hidden>
            <button type="button" class="btn-pill btn-outline btn-pill--sm" data-pvc-toggle
                    aria-pressed="false">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9"/>
                    <path d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5 5 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z"/>
                </svg>
                <span data-pvc-label>Voir le verso</span>
            </button>
        </div>
    @endif
</div>
