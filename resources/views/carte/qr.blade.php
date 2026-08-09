{{-- MON QR CODE — le code en grand, le lien, les téléchargements. --}}
<x-app-layout title="Mon QR Code">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">Mon QR Code</h1>
            <p class="db-tete__sous">
                Imprimez-le, partagez-le&nbsp;: il ouvre votre carte sur n'importe quel téléphone.
            </p>
        </div>
    </div>

    <div class="db-grille">
        <div class="db-principal">

            <section class="db-card">
                <h2 class="db-card__titre">Votre code</h2>

                <div class="qr-page">
                    {{-- Fond blanc franc quel que soit le thème : un lecteur a
                         besoin de contraste avec le fond IMMÉDIAT du code. --}}
                    <div class="qr-page__cadre">{!! $qrSvg !!}</div>

                    <div class="qr-page__cote">
                        <label class="board-link">
                            <span class="board-link__label">Lien public</span>
                            <span class="board-link__row">
                                <input type="text" class="board-link__input" readonly
                                       id="lienQr" value="{{ $publicUrl }}"
                                       aria-label="Lien public de votre carte">
                                <button type="button" class="board-link__copy"
                                        data-copy="lienQr" data-copy-done="Copié">Copier</button>
                            </span>
                        </label>

                        <div class="board-downloads">
                            <x-button :href="route('carte.qr.png')" variant="outline" size="sm">
                                Télécharger en PNG
                            </x-button>
                            <x-button :href="route('carte.qr.svg')" variant="outline" size="sm">
                                Télécharger en SVG
                            </x-button>

                            @if ($profile->isPubliclyVisible())
                                <x-button :href="route('carte.imprimable')" variant="outline" size="sm">
                                    Carte imprimable (PDF)
                                </x-button>
                            @endif
                        </div>

                        <p class="db-vide__texte db-vide__texte--serre">
                            Le SVG est vectoriel&nbsp;: c'est le format à confier
                            à un imprimeur. Le PNG convient partout ailleurs.
                        </p>
                    </div>
                </div>
            </section>

            <section class="db-card">
                <h2 class="db-card__titre">Aperçu de la carte</h2>

                <div class="db-carte__visuel">
                    <x-pvc-card :profile="$profile" size="lg" />
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
