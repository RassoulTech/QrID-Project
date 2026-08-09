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
</aside>
