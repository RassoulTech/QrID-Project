{{-- MON QR CODE — le code en grand, le lien, les téléchargements. --}}
<x-app-layout :title="__('card.qr.titre')">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">{{ __('card.qr.titre') }}</h1>
            <p class="db-tete__sous">{!! __('card.qr.sous') !!}</p>
        </div>
    </div>

    <div class="db-grille">
        <div class="db-principal">

            <section class="db-card">
                <h2 class="db-card__titre">{{ __('card.qr.code') }}</h2>

                <div class="qr-page">
                    {{-- Fond blanc franc quel que soit le thème : un lecteur a
                         besoin de contraste avec le fond IMMÉDIAT du code. --}}
                    <div class="qr-page__cadre">{!! $qrSvg !!}</div>

                    <div class="qr-page__cote">
                        <label class="board-link">
                            <span class="board-link__label">{{ __('dashboard.carte.lien_public') }}</span>
                            <span class="board-link__row">
                                <input type="text" class="board-link__input" readonly
                                       id="lienQr" value="{{ $publicUrl }}"
                                       aria-label="{{ __('dashboard.carte.lien_aria') }}">
                                <button type="button" class="board-link__copy"
                                        data-copy="lienQr"
                                        data-copy-done="{{ __('dashboard.carte.copie') }}">{{ __('dashboard.carte.copier') }}</button>
                            </span>
                        </label>

                        <div class="board-downloads">
                            <x-button :href="route('carte.qr.png')" variant="outline" size="sm">
                                {{ __('card.qr.png') }}
                            </x-button>
                            <x-button :href="route('carte.qr.svg')" variant="outline" size="sm">
                                {{ __('card.qr.svg') }}
                            </x-button>

                            @if ($profile->isPubliclyVisible())
                                <x-button :href="route('carte.imprimable')" variant="outline" size="sm">
                                    {{ __('card.qr.pdf') }}
                                </x-button>
                            @endif
                        </div>

                        <p class="db-vide__texte db-vide__texte--serre">
                            {!! __('card.qr.formats') !!}
                        </p>
                    </div>
                </div>
            </section>

            <section class="db-card">
                <h2 class="db-card__titre">{{ __('card.qr.apercu') }}</h2>

                <div class="db-carte__visuel">
                    <x-card-duo :profile="$profile" />
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
