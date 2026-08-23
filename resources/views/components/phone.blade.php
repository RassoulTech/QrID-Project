{{--
  x-phone — un téléphone qui montre LA PAGE PUBLIQUE, à l'échelle.

  <x-phone :profile="$p" />                grande taille (hero, 280px)
  <x-phone :profile="$p" size="sm" />      section sombre (240px)
  <x-phone :profile="$p" :animate="false" />

  ═══════════════════════════════════════════════════════════════════════
  CE QU'IL MONTRE DOIT ÊTRE CE QU'ON REÇOIT
  ═══════════════════════════════════════════════════════════════════════
  Cette maquette reproduisait la PREMIÈRE version de la page publique :
  en-tête vert plein, avatar rond centré, quatre actions en ligne, pastilles
  grises pour les réseaux. La page publique a été refaite depuis — bannière
  en dégradé, médaillon carré qui déborde, bloc de coordonnées teinté,
  grille de trois par rangée, barre Partager / Enregistrer.

  Une landing qui montre un écran que le produit ne rend plus est une
  promesse fausse. Le visiteur qui s'inscrit obtient autre chose que ce
  qu'on lui a montré, et c'est exactement à ce moment-là qu'il se demande ce
  qu'on lui a caché d'autre.

  La structure ci-dessous suit donc, bloc pour bloc, celle de
  resources/views/public/profile.blade.php. Les classes sont distinctes
  (.phc au lieu de .pubc) parce que les tailles sont réduites de moitié pour
  tenir dans 280px — mais l'ordre, les proportions et les couleurs sont les
  mêmes, et ils doivent le rester.

  ═══════════════════════════════════════════════════════════════════════
  LE PORTRAIT EST UN DESSIN
  ═══════════════════════════════════════════════════════════════════════
  La photo du profil de démonstration est celle d'une personne réelle — le
  propriétaire du compte. Une vitrine ne présente pas quelqu'un de réel
  comme s'il était un client : voir x-avatar-demo.

  Tout en HTML/CSS, icônes en SVG inline. Aucune image, aucune police
  d'icônes, aucune requête réseau.
--}}
@props([
    'profile',
    'size' => 'lg',
    'animate' => true,
])

@php
    /*
     | LES ACTIONS, DANS L'ORDRE DE LA PAGE PUBLIQUE.
     |
     | WhatsApp, téléphone, localisation, puis les réseaux. Six au plus : la
     | grille en montre deux rangées de trois, et une troisième rangée
     | dépasserait de l'écran du téléphone.
     |
     | Les réseaux ne sont lus que si la relation a été chargée en amont :
     | aucune requête déclenchée depuis une vue, donc aucun N+1 possible.
     */
    $liens = $profile->relationLoaded('socialLinks') ? $profile->socialLinks : collect();

    $actions = [];

    if ($profile->whatsapp_href) {
        $actions[] = ['whatsapp', __('WhatsApp')];
    }

    if ($profile->phone) {
        $actions[] = ['telephone', __('Appeler')];
    }

    if ($profile->address) {
        $actions[] = ['localisation', __('Localisation')];
    }

    foreach ($liens as $lien) {
        $actions[] = [$lien->platform, $lien->platform_label];
    }

    /*
     | LA GRILLE EST TOUJOURS COMPLÈTE — six entrées, deux rangées pleines.
     |
     | Avec cinq, la dernière rangée laissait un trou à droite, et un trou
     | dans une grille se lit comme un défaut d'affichage, jamais comme un
     | choix. Les plateformes de complément sont les plus courantes chez les
     | professionnels sénégalais.
     */
    foreach (['linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook'] as $cle => $nom) {
        if (count($actions) >= 6) {
            break;
        }

        $actions[] = [$cle, $nom];
    }

    $actions = array_slice($actions, 0, 6);
@endphp

<div {{ $attributes->merge([
        'class' => 'phone phone--'.$size.($animate ? ' phone--enter' : ''),
    ]) }}>

    <span class="phone__vol phone__vol--up" aria-hidden="true"></span>
    <span class="phone__vol phone__vol--down" aria-hidden="true"></span>
    <span class="phone__side" aria-hidden="true"></span>
    <span class="phone__gloss" aria-hidden="true"></span>

    <div class="phone__screen">
        <span class="phone__notch" aria-hidden="true"></span>

        {{-- Barre d'état --}}
        <div class="phone__status" aria-hidden="true">
            <span class="phone__time">9:41</span>
            <span class="phone__icons">
                <svg width="12" height="9" viewBox="0 0 16 12" fill="currentColor">
                    <rect x="0" y="8" width="3" height="4" rx="1"/>
                    <rect x="4" y="5" width="3" height="7" rx="1"/>
                    <rect x="8" y="2" width="3" height="10" rx="1"/>
                    <rect x="12" y="0" width="3" height="12" rx="1" opacity=".4"/>
                </svg>
                <svg width="11" height="9" viewBox="0 0 16 12" fill="currentColor">
                    <path d="M8 10.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2M5.5 7.2a3.5 3.5 0 0 1 5 0l-.9.9a2.2 2.2 0 0 0-3.2 0zM3 4.6a7 7 0 0 1 10 0l-.9.9a5.7 5.7 0 0 0-8.2 0z"/>
                </svg>
                <svg width="16" height="9" viewBox="0 0 22 12" fill="none">
                    <rect x=".5" y=".5" width="17" height="11" rx="3" stroke="currentColor" opacity=".5"/>
                    <rect x="2" y="2" width="12" height="8" rx="1.5" fill="currentColor"/>
                    <path d="M19 4v4a2 2 0 0 0 0-4" fill="currentColor" opacity=".5"/>
                </svg>
            </span>
        </div>

        {{-- ═══════════ LA CARTE, comme sur /p/{slug} ═══════════
             Deux niveaux, exactement comme la page publique : une PAGE grise
             qui porte une CARTE blanche détachée. La carte à fond perdu, sans
             marge ni coin arrondi, ressemblait à une application plein écran
             et non à une carte de visite. --}}
        <div class="phc">
          <div class="phc__carte">

            {{-- COUVERTURE ET MÉDAILLON — le médaillon déborde, c'est ce
                 chevauchement qui fait lire la photo et le nom comme un seul
                 bloc. Même composition que .pubc__couverture. --}}
            <div class="phc__couverture">
                <span class="phc__couverture-fond" aria-hidden="true"></span>

                <div class="phc__medaillon">
                    <x-avatar-demo :taille="52" class="phc__photo" />
                </div>
            </div>

            {{-- IDENTITÉ --}}
            <div class="phc__identite">
                <span class="phc__nom">{{ $profile->full_name }}</span>
                @if ($profile->job_title)
                    <span class="phc__role">{{ $profile->job_title }}</span>
                @endif
                @if ($profile->company)
                    <span class="phc__entreprise">{{ $profile->company }}</span>
                @endif
            </div>

            {{-- COORDONNÉES — bloc teinté, lu d'un seul regard. --}}
            <div class="phc__infos">
                @if ($profile->public_email)
                    <span class="phc__info">
                        <span class="phc__info-icone" aria-hidden="true">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>
                            </svg>
                        </span>
                        <span class="phc__info-texte">{{ $profile->public_email }}</span>
                    </span>
                @endif

                @if ($profile->phone)
                    <span class="phc__info">
                        <span class="phc__info-icone" aria-hidden="true">
                            <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58z"/>
                            </svg>
                        </span>
                        <span class="phc__info-texte">{{ $profile->formatted_phone }}</span>
                    </span>
                @endif

                @if ($profile->address)
                    <span class="phc__info">
                        <span class="phc__info-icone" aria-hidden="true">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                        <span class="phc__info-texte">{{ $profile->address }}</span>
                    </span>
                @endif
            </div>

            {{-- LA GRILLE — trois par rangée, logos officiels. --}}
            <div class="phc__grille">
                @foreach ($actions as [$plateforme, $libelle])
                    <span class="phc__tuile">
                        <x-social-icon :plateforme="$plateforme" :taille="14" />
                        <span class="phc__tuile-nom">{{ $libelle }}</span>
                    </span>
                @endforeach
            </div>

            {{-- BARRE D'ACTIONS — une seule est pleine : c'est
                 « Enregistrer » qui termine le parcours. --}}
            <div class="phc__barre">
                <span class="phc__action phc__action--clair">{{ __('Partager') }}</span>
                <span class="phc__action phc__action--plein">{{ __('Enregistrer') }}</span>
            </div>

            {{-- La signature de bas de carte, comme sur la page publique.
                 Elle n'est pas décorative : c'est elle qui fait de chaque
                 carte partagée un point d'entrée vers le produit. --}}
            <span class="phc__pied">
                {!! __('Carte créée avec <b>:marque</b>', ['marque' => e(config('app.name'))]) !!}
            </span>
          </div>
        </div>
    </div>
</div>
