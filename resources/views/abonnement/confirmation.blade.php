{{--
  CONFIRMATION — la carte est en ligne.

  On remet ici au client ce qu'il vient d'acheter : sa carte, son lien, ses
  fichiers. Renvoyer sec au tableau de bord après un paiement laissait le
  moment le plus important du parcours sans aucun accusé de réception.

  ACTION PRINCIPALE : récupérer son lien et ses fichiers.
--}}
<x-app-layout title="Votre carte est en ligne">
    <div class="preview">

        <span class="confirm__coche" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 16 16" fill="currentColor">
                <path d="M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
            </svg>
        </span>

        <h1 class="preview__title">Votre carte est en ligne</h1>
        <p class="preview__sub">
            Paiement confirmé. Vos contacts peuvent désormais ouvrir votre carte
            @if ($subscription?->ends_at)
                — jusqu'au {{ $subscription->ends_at->translatedFormat('j F Y') }}.
            @else
                dès maintenant.
            @endif
        </p>

        <div class="preview__stage">
            <x-card-duo :profile="$profile" class="mx-auto" />
        </div>

        {{-- Le lien, copiable. Champ readonly : sans JavaScript il reste
             sélectionnable à la main, le bouton n'est qu'un raccourci. --}}
        <label class="board-link">
            <span class="board-link__label">Votre lien public</span>
            <span class="board-link__row">
                <input type="text" class="board-link__input" readonly
                       id="lienConfirme" value="{{ $publicUrl }}"
                       aria-label="Lien public de votre carte">
                <button type="button" class="board-link__copy"
                        data-copy="lienConfirme" data-copy-done="Copié">
                    Copier
                </button>
            </span>
        </label>

        <div class="board-downloads">
            <x-button :href="route('carte.qr.png')" variant="outline" size="sm">QR en PNG</x-button>
            <x-button :href="route('carte.qr.svg')" variant="outline" size="sm">QR en SVG</x-button>
            <x-button :href="route('carte.imprimable')" variant="outline" size="sm">
                Carte imprimable (PDF)
            </x-button>
        </div>

        <div class="preview__action">
            <x-button :href="route('dashboard')">Aller à mon tableau de bord</x-button>
        </div>
    </div>
</x-app-layout>
