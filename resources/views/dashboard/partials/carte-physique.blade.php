{{--
  MA CARTE PHYSIQUE — le bloc du tableau de bord.

  IL N'APPARAÎT QUE SI UNE COMMANDE EXISTE, c'est-à-dire après un premier
  paiement encaissé. Un bloc « vous n'avez pas de carte » sur le tableau de
  bord d'un client en essai lui apprendrait surtout qu'il lui manque quelque
  chose — au moment précis où on lui demande de découvrir le produit.

  LE PREMIER MOT EST L'ÉTAT, pas l'action : le client vient ici pour savoir
  où en est sa carte, pas pour remplir un formulaire.

  Les classes sont celles DÉJÀ en service sur le tableau de bord. En inventer
  un second jeu garantirait qu'ils divergent au premier ajustement.
--}}
@php
    $commandeCarte = auth()->user()
        ?->cardOrders()
        ->whereNot('status', \App\Models\CardOrder::STATUS_CANCELLED)
        ->latest('id')
        ->first();
@endphp

@if ($commandeCarte)
    <section class="db-card">
        <div class="db-card__tete">
            <h2 class="db-card__titre">Ma carte physique</h2>
            <span class="carte-etat__pastille">{{ $commandeCarte->statutLibelle() }}</span>
        </div>

        @if (! $commandeCarte->adresseComplete())
            {{-- LA SEULE CHOSE QUI BLOQUE VRAIMENT. Sans adresse, la carte ne
                 peut pas partir — et le client ne le sait pas : pour lui, il a
                 payé et il attend. --}}
            <p class="db-card__texte">
                Votre carte PVC est <strong>offerte</strong> et vous attend.
                Il nous manque seulement l'adresse où la livrer.
            </p>

            <a href="{{ route('carte.physique') }}" class="btn-pill btn-dark btn-sm-pill">
                Indiquer mon adresse
            </a>
        @else
            <p class="db-card__texte">
                Livraison à <strong>{{ $commandeCarte->city }}</strong>, pour
                {{ $commandeCarte->recipient_name }}.

                @if ($commandeCarte->delivered_at)
                    Livrée le {{ $commandeCarte->delivered_at->format('d/m/Y') }}.
                @elseif ($commandeCarte->shipped_at)
                    Expédiée le {{ $commandeCarte->shipped_at->format('d/m/Y') }}.
                @elseif ($commandeCarte->adresseModifiable())
                    Départ à la prochaine production, sous environ
                    {{ config('cartes.delai_jours') }} jours.
                @endif
            </p>

            @if ($commandeCarte->adresseModifiable())
                <a href="{{ route('carte.physique') }}" class="board-link__label">
                    Corriger l'adresse
                </a>
            @endif
        @endif
    </section>
@endif
