{{-- LA CARTE EXISTE, MAIS ELLE N'EST PAS EN LIGNE.

     Le premier geste d'un client qui vient de créer sa carte est de scanner son
     propre QR pour voir si « ça marche ». Il tombait sur une page d'erreur nue,
     sans aucun moyen de savoir que rien n'était cassé et qu'il lui restait une
     seule chose à faire.

     LA RÉPONSE PORTE UN STATUT 404 — voir PublicProfileController. Un 200
     distinguerait une carte inactive d'un slug inexistant, et permettrait
     d'énumérer les comptes en essayant des adresses.

     LE NOM N'EST AFFICHÉ QU'AU PROPRIÉTAIRE. Pour tout autre visiteur, cette
     page ne révèle rien : ni de qui il s'agit, ni même que quelqu'un a réservé
     cette adresse.

     Props : $profile, $raison (brouillon | abonnement | suspendue), $proprietaire --}}
<x-public-profile-layout
    :title="$proprietaire ? __('card.inactive.titre_proprietaire') : __('card.inactive.titre_visiteur')"
    :description="__('card.inactive.description')">

    <div class="pub">
        <div class="carte-off">
            <div class="carte-off__marque" aria-hidden="true">
                {{-- Un QR Code barré. Des formes explicites plutôt qu'un tracé
                     unique : le rendu est vérifiable à la lecture. --}}
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="6" height="6" rx="1"/>
                    <rect x="15" y="3" width="6" height="6" rx="1"/>
                    <rect x="3" y="15" width="6" height="6" rx="1"/>
                    <line x1="14" y1="21" x2="21" y2="14" stroke-linecap="round"/>
                </svg>
            </div>

            @if ($proprietaire)
                {{-- ══════════════ LE PROPRIÉTAIRE, CONNECTÉ ══════════════ --}}
                @if ($raison === 'suspendue')
                    <h1 class="carte-off__titre">{{ __('card.inactive.suspendue_titre') }}</h1>
                    <p class="carte-off__texte">{{ __('card.inactive.suspendue_texte') }}</p>
                    <a href="{{ config('registration.support_whatsapp') }}"
                       class="btn-pill btn-dark carte-off__action"
                       target="_blank" rel="noopener">{{ __('card.inactive.suspendue_action') }}</a>

                @elseif ($raison === 'abonnement')
                    <h1 class="carte-off__titre">{{ __('card.inactive.abonnement_titre') }}</h1>
                    <p class="carte-off__texte">{!! __('card.inactive.abonnement_texte') !!}</p>
                    {{-- Pour un administrateur, le bouton saute l'écran de
                         paiement — qui ne mène nulle part tant qu'aucune
                         passerelle n'encaisse — et va droit à la prolongation,
                         la seule voie réellement ouverte. --}}
                    <a href="{{ $ficheAdmin ?? route('abonnement.paiement') }}"
                       class="btn-pill btn-dark carte-off__action">
                        {{ $ficheAdmin ? __('card.inactive.abonnement_prolonger') : __('card.inactive.abonnement_activer') }}
                    </a>

                @else
                    <h1 class="carte-off__titre">{{ __('card.inactive.brouillon_titre') }}</h1>
                    <p class="carte-off__texte">{!! __('card.inactive.brouillon_texte') !!}</p>
                    <a href="{{ route('profile.preview') }}"
                       class="btn-pill btn-dark carte-off__action">{{ __('card.inactive.brouillon_action') }}</a>
                @endif

                <p class="carte-off__pied">
                    <a href="{{ route('dashboard') }}">{{ __('card.inactive.retour') }}</a>
                </p>

            @else
                {{-- ══════════════ TOUT AUTRE VISITEUR ══════════════
                     Aucun nom, aucune information : cette page ne confirme même
                     pas que l'adresse a été réservée. --}}
                <h1 class="carte-off__titre">{{ __('card.inactive.visiteur_titre') }}</h1>
                <p class="carte-off__texte">{{ __('card.inactive.visiteur_texte') }}</p>
                <a href="{{ route('login') }}"
                   class="btn-pill btn-dark carte-off__action">
                    {{ __('card.inactive.visiteur_connexion') }}
                </a>

                <p class="carte-off__pied">
                    <a href="{{ route('home') }}">{{ __('card.inactive.visiteur_creer') }}</a>
                </p>
            @endif
        </div>
    </div>
</x-public-profile-layout>
