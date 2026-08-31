{{--
  x-card — LA carte QrID. Une face, une variante, un porteur.

      <x-card face="recto" :profile="$profile" />
      <x-card face="verso" variant="dark" :profile="$profile" />

  ═══════════════════════════════════════════════════════════════════════
  UN SEUL COMPOSANT, ET PLUS AUCUN AUTRE
  ═══════════════════════════════════════════════════════════════════════
  Il remplace pvc-card, pvc-card-face-recto et pvc-card-face-verso, qui ont
  été supprimés. Trois représentations d'un même objet finissent toujours
  par diverger — et la divergence se découvre sur des cartes déjà tirées.

  Il sert partout : tableau de bord, aperçu avant paiement, page « Mon QR
  Code », étape de personnalisation, et le gabarit d'impression.

  ═══════════════════════════════════════════════════════════════════════
  LE BALISAGE EST CELUI DE LA RÉFÉRENCE VALIDÉE
  ═══════════════════════════════════════════════════════════════════════
  Structure, classes et ordre des éléments sont repris tels quels. Seules
  les valeurs variables — nom, fonction, entreprise, QR — sont injectées.
  Voir resources/sass/_card.scss pour la note complète.

  Props :
    face     'recto' (le porteur) ou 'verso' (la plateforme)
    variant  'light' (blanche, par défaut) ou 'dark' (verte)
    profile  le modèle du porteur ; facultatif sur le verso
--}}
@props([
    'face' => 'recto',
    'variant' => null,
    'profile' => null,
])

@php
    /*
     | LA VARIANTE VIENT DU PROFIL, sauf si l'appelant en impose une.
     |
     | L'étape de personnalisation et la planche du design-system montrent
     | les DEUX variantes côte à côte : elles passent donc la leur. Partout
     | ailleurs, c'est le choix du client qui décide, et aucune vue ne lit
     | primary_color directement — sans quoi une teinte héritée de l'ancien
     | nuancier finirait par ressortir quelque part.
     */
    $variante = $variant
        ?? ($profile?->variante() === \App\Enums\VarianteCarte::Verte ? 'dark' : 'light');

    $qr = app(App\Services\QrCodeService::class);
@endphp

<div {{ $attributes->merge(['class' => 'card '.$variante]) }}>
    @if ($face === 'verso')
        {{-- ═══════════ VERSO — entièrement statique ═══════════
             Rigoureusement identique sur les cartes de tous les clients : il
             ne lit aucune donnée de profil. Son QR mène à la PLATEFORME, pas
             au porteur — chaque carte distribuée devient ainsi un canal
             d'acquisition, sans rien coûter à celui qui la tend. --}}
        <div class="back">
            <div class="brand">
                <span class="mark">{{ \App\Support\Marque::monogramme() }}</span>
                <span class="wordmark">{{ config('app.name') }}</span>
            </div>

            <div class="tagline">{{ config('landing.brand.tagline') }}</div>

            <div class="qrbox">
                <span class="qrpanneau">
                    <span class="qr">{!! $qr->plateformeSvg() !!}</span>
                </span>
                <div class="qrcap">{{ config('landing.brand.card_cta') }}</div>
            </div>

            <div class="foot">
                <span class="l">{{ __('card.protocole') }}</span>
                {{-- L'adresse vient de la configuration : en développement,
                     la dériver d'APP_URL ferait imprimer « 127.0.0.1 », et
                     cela ne se voit qu'une fois les cartes sorties. --}}
                <span class="r">{{ config('landing.brand.website') }}</span>
            </div>
        </div>
    @else
        {{-- ═══════════ RECTO — le porteur ═══════════ --}}
        @php
            $nom = mb_strtoupper((string) $profile?->full_name);

            /*
             | LA TAILLE DU NOM SUIT SON NOMBRE DE MOTS.
             |
             | Le nom reste sur UNE ligne, quelle que soit sa longueur — la
             | feuille pose white-space:nowrap. Trois paliers sont prévus :
             | un seul mot peut être écrit en grand, trois mots ou plus
             | doivent être réduits pour tenir dans les 55 % de largeur de
             | leur colonne.
             |
             | Le comptage se fait sur les mots et non sur les caractères :
             | c'est le nombre d'espaces qui décide de la place réellement
             | occupée par un nom en capitales.
             */
            $mots = count(preg_split('/\s+/', trim($nom), -1, PREG_SPLIT_NO_EMPTY) ?: []);

            $classeNom = match (true) {
                $mots <= 1 => 'name name--short',
                $mots === 2 => 'name',
                default => 'name name--long',
            };
        @endphp

        <div class="front">
            <div class="who">
                <div class="{{ $classeNom }}">{{ $nom }}</div>

                @if ($profile?->job_title)
                    <div class="role">{{ mb_strtoupper($profile->job_title) }}</div>
                @endif

                @if ($profile?->company)
                    <div class="org">{{ $profile->company }}</div>
                @endif
            </div>

            <div class="access">
                {{-- L'onde est STATIQUE, identique pour tous. Elle dit, avant
                     qu'on ait rien lu, que ce morceau de plastique mène
                     quelque part — la convention des cartes de paiement. --}}
                <svg class="nfc" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                    {{-- LE GLYPHE EST CELUI DU WIFI : trois arcs concentriques
                         ET LE POINT D'ORIGINE. Le point n'est pas un détail —
                         c'est lui qui distingue le WiFi d'un simple indicateur
                         de réseau, et sans lui les trois arcs se lisaient comme
                         une barre de signal couchée. --}}
                    <path d="M2 9.2a15.5 15.5 0 0 1 20 0"/>
                    <path d="M5.4 13a10.5 10.5 0 0 1 13.2 0"/>
                    <path d="M8.8 16.8a5.5 5.5 0 0 1 6.4 0"/>
                    <circle cx="12" cy="20.2" r="1.35" fill="currentColor" stroke="none"/>
                </svg>

                {{-- Le QR du recto mène au PROFIL du porteur. Deux codes, deux
                     destinations : ce n'est pas une erreur.

                     svg() ET NON carteSvg(). carteSvg() teinte les modules
                     selon la variante : sur la carte VERTE il les rendait
                     BLANCS, et la référence pose un fond blanc sous le code —
                     blanc sur blanc, donc invisible et inscannable.

                     La référence veut des modules sombres sur fond blanc dans
                     les DEUX variantes. C'est aussi l'orientation standard
                     d'ISO/IEC 18004 : un code inversé est géré par les
                     lecteurs récents, ignoré par les autres, et leur échec est
                     silencieux. Sur un objet imprimé qu'on ne peut plus
                     corriger, ce risque ne se prend pas. --}}
                {{-- LE PANNEAU ENTOURE LE QR SUR LES DEUX VARIANTES.
                     Il donne au code un fond clair même sur la carte verte,
                     donc « sombre sur clair » partout, conformément à
                     ISO/IEC 18004. Voir .qrpanneau dans _card.scss. --}}
                <span class="qrpanneau">
                    <span class="qr">
                        @if ($profile?->slug)
                            {!! $qr->svg($profile) !!}
                        @endif
                    </span>
                </span>
            </div>
        </div>
    @endif
</div>
