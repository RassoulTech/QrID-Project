{{--
  CONFIRMATION — la carte est en ligne.

  On remet ici au client ce qu'il vient d'acheter : sa carte, son lien, ses
  fichiers. Renvoyer sec au tableau de bord après un paiement laissait le
  moment le plus important du parcours sans aucun accusé de réception.

  ACTION PRINCIPALE : récupérer son lien et ses fichiers.
--}}
<x-app-layout :title="__('payment.confirmation.titre')">
    <div class="preview">

        <span class="confirm__coche" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 16 16" fill="currentColor">
                <path d="M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
            </svg>
        </span>

        <h1 class="preview__title">{{ __('payment.confirmation.titre') }}</h1>
        <p class="preview__sub">
            {{-- La phrase entière est traduite d'un bloc, pas assemblée : en
                 anglais l'incise de date ne se place pas au même endroit. --}}
            @if ($subscription?->ends_at)
                {{ __('payment.confirmation.sous_avec_date', [
                    'date' => $subscription->ends_at->translatedFormat('j F Y'),
                ]) }}
            @else
                {{ __('payment.confirmation.sous_sans_date') }}
            @endif
        </p>

        <div class="preview__stage">
            <x-card-duo :profile="$profile" class="mx-auto" />
        </div>

        {{-- Le lien, copiable. Champ readonly : sans JavaScript il reste
             sélectionnable à la main, le bouton n'est qu'un raccourci. --}}
        <label class="board-link">
            <span class="board-link__label">{{ __('payment.confirmation.lien') }}</span>
            <span class="board-link__row">
                <input type="text" class="board-link__input" readonly
                       id="lienConfirme" value="{{ $publicUrl }}"
                       aria-label="{{ __('dashboard.carte.lien_aria') }}">
                <button type="button" class="board-link__copy"
                        data-copy="lienConfirme" data-copy-done="{{ __('dashboard.carte.copie') }}">
                    {{ __('dashboard.carte.copier') }}
                </button>
            </span>
        </label>

        <div class="board-downloads">
            <x-button :href="route('carte.qr.png')" variant="outline" size="sm">{{ __('dashboard.carte.qr_png') }}</x-button>
            <x-button :href="route('carte.qr.svg')" variant="outline" size="sm">{{ __('dashboard.carte.qr_svg') }}</x-button>
            <x-button :href="route('carte.imprimable')" variant="outline" size="sm">
                {{ __('payment.confirmation.imprimable') }}
            </x-button>
        </div>

        <div class="preview__action">
            <x-button :href="route('dashboard')">{{ __('payment.confirmation.tableau') }}</x-button>
        </div>
    </div>
</x-app-layout>
