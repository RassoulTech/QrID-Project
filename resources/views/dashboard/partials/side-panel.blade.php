{{--
  Colonne de droite — derniers visiteurs et journal du compte.

  Elle se replie sous 1200px et passe SOUS le contenu principal : c'est du
  contexte utile, jamais de l'information dont dépend une action.

  PAS DE VILLE dans les visiteurs, et ce n'est pas un oubli : profile_events
  ne stocke que sha256(ip + clé), jamais l'adresse en clair. Aucune
  géolocalisation n'en est dérivable — il faudrait conserver les IP, ce que ce
  produit a choisi de ne pas faire.
--}}
<aside class="db-rail">

    {{-- ===================== DERNIERS VISITEURS ===================== --}}
    <section class="db-card">
        <h2 class="db-card__titre">Derniers visiteurs</h2>

        @forelse ($visiteurs as $visite)
            <div class="visite">
                <span @class(['visite__pastille', 'visite__pastille--scan' => $visite->type === 'scan'])
                      aria-hidden="true">
                    @if ($visite->type === 'scan')
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h2v2H2z"/><path d="M6 0v6H0V0zM5 1H1v4h4zM4 12H2v2h2z"/><path d="M6 10v6H0v-6zm-5 1v4h4v-4zm11-9h2v2h-2z"/><path d="M10 0v6h6V0zm5 1v4h-4V1z"/></svg>
                    @else
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg>
                    @endif
                </span>

                <span class="visite__texte">
                    <span class="visite__type">
                        {{ $visite->type === 'scan' ? 'Scan du QR Code' : 'Consultation directe' }}
                    </span>
                    <span class="visite__date">{{ $visite->created_at?->diffForHumans() }}</span>
                </span>
            </div>
        @empty
            <p class="db-vide__texte db-vide__texte--serre">
                Personne n'a encore ouvert votre carte.
            </p>
        @endforelse
    </section>

    {{-- ===================== JOURNAL DU COMPTE ===================== --}}
    <section class="db-card">
        <h2 class="db-card__titre">Activité du compte</h2>

        @forelse ($journal as $entree)
            <div class="journal">
                <span class="journal__point" aria-hidden="true"></span>
                <span class="journal__texte">
                    <span class="journal__titre">{{ $entree['titre'] }}</span>
                    @if ($entree['detail'])
                        <span class="journal__detail">{{ $entree['detail'] }}</span>
                    @endif
                    <span class="journal__date">{{ $entree['date']?->diffForHumans() }}</span>
                </span>
            </div>
        @empty
            <p class="db-vide__texte db-vide__texte--serre">Aucune activité enregistrée.</p>
        @endforelse
    </section>

    {{-- ===================== GROUPE D'ENTRAIDE =====================

         EN DERNIER, et volontairement discret. Ce n'est pas une action du
         produit : c'est une porte ouverte pour qui bloque.

         L'INVITATION N'EXISTE QUE POUR UN COMPTE CONNECTÉ. Ce lien donne
         accès à un espace réservé aux clients — quiconque l'obtient peut y
         entrer. Il ne doit jamais apparaître sur une page publique, et un
         test le vérifie sur l'accueil, la connexion et l'inscription.

         Il double l'invitation de l'e-mail de bienvenue, à dessein : cet
         e-mail arrive une fois, se lit en diagonale, et se perd. Le besoin
         d'aide, lui, arrive plus tard. --}}
    @if ($groupeUrl = config('automation.whatsapp_groupe'))
        <section class="db-card db-groupe">
            <h2 class="db-card__titre">Besoin d'un coup de main&nbsp;?</h2>

            <p class="db-groupe__texte">
                Un groupe WhatsApp réunit les clients {{ config('app.name') }}.
                Questions, entraide, et réponses de notre équipe.
            </p>

            <a href="{{ $groupeUrl }}" class="db-groupe__lien"
               target="_blank" rel="noopener noreferrer">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12.04 2a9.9 9.9 0 0 0-8.5 15.02L2 22.5l5.62-1.47A9.9 9.9 0 1 0 12.04 2m0 1.67a8.23 8.23 0 1 1-4.19 15.31l-.3-.18-3.34.87.89-3.25-.2-.31A8.23 8.23 0 0 1 12.04 3.67"/>
                    <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97s-.47-.15-.67.15-.77.96-.94 1.16-.35.22-.65.07a8.1 8.1 0 0 1-2.39-1.47 9 9 0 0 1-1.65-2.06c-.17-.3-.02-.46.13-.61s.3-.35.45-.52.2-.3.3-.5.05-.37-.02-.52-.67-1.61-.92-2.21c-.24-.58-.49-.5-.67-.51h-.57a1.1 1.1 0 0 0-.8.37 3.35 3.35 0 0 0-1.04 2.48 5.8 5.8 0 0 0 1.22 3.09 13.3 13.3 0 0 0 5.09 4.5c.71.3 1.27.49 1.7.63a4.1 4.1 0 0 0 1.88.12 3.07 3.07 0 0 0 2.01-1.42 2.5 2.5 0 0 0 .17-1.42c-.07-.12-.27-.2-.57-.35z"/>
                </svg>
                Rejoindre le groupe
            </a>
        </section>
    @endif
</aside>
