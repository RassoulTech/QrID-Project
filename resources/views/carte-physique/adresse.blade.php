{{--
  MA CARTE PHYSIQUE — l'adresse de livraison.

  Cet écran n'existe qu'après un premier paiement encaissé : demander une
  adresse avant d'avoir encaissé ferait remplir un formulaire pour un objet
  qui n'existera peut-être jamais.
--}}
<x-app-layout title="Ma carte physique">
    <div class="step-card">
        <p class="step-card__kicker">Carte physique</p>
        <h1 class="step-card__title">Où livrer votre carte&nbsp;?</h1>

        <p class="step-card__sub">
            Votre carte PVC est <strong>offerte</strong> avec votre abonnement.
            Elle part à la prochaine production, sous environ
            <strong>{{ $delai }} jours</strong> après validation de l'adresse.
        </p>

        {{-- L'ÉTAT AVANT LE FORMULAIRE. Le client vient souvent pour savoir où
             en est sa carte, pas pour saisir quelque chose. --}}
        <div class="carte-etat">
            <span class="carte-etat__pastille">{{ $commande->statutLibelle() }}</span>

            @if ($commande->shipped_at)
                <span class="carte-etat__date">Expédiée le {{ $commande->shipped_at->format('d/m/Y') }}</span>
            @elseif ($commande->produced_at)
                <span class="carte-etat__date">Imprimée le {{ $commande->produced_at->format('d/m/Y') }}</span>
            @endif
        </div>

        @if (! $commande->adresseModifiable())
            {{-- Le bordereau est figé : le dire, plutôt que d'afficher un
                 formulaire dont la soumission serait refusée. --}}
            <x-alert type="info" :dismissible="false">
                Votre carte est déjà en production : l'adresse ne peut plus être
                modifiée. Si elle est erronée, écrivez-nous — nous
                interviendrons avant l'expédition si c'est encore possible.
            </x-alert>

            <dl class="carte-recap">
                <dt>Destinataire</dt><dd>{{ $commande->recipient_name ?? '—' }}</dd>
                <dt>Téléphone</dt><dd>{{ $commande->phone ?? '—' }}</dd>
                <dt>Adresse</dt><dd>{{ $commande->address_line ?? '—' }}</dd>
                <dt>Ville</dt><dd>{{ $commande->city ?? '—' }}</dd>
            </dl>
        @else
            <form method="POST" action="{{ route('carte.physique.update') }}" novalidate>
                @csrf
                @method('PATCH')

                <x-form-legende />

                <x-field name="recipient_name" label="Nom du destinataire"
                         :value="$commande->recipient_name" autocomplete="name" />

                <x-phone-field name="phone" label="Téléphone du destinataire"
                               :value="$commande->phone"
                               help="C'est ce numéro que le livreur appellera." />

                <x-field name="address_line" label="Adresse"
                         placeholder="Cité Keur Gorgui, villa 42"
                         :value="$commande->address_line" autocomplete="street-address" />

                <div class="f-row">
                    <x-field name="city" label="Ville" placeholder="Dakar"
                             :value="$commande->city" autocomplete="address-level2" />

                    <x-field name="region" label="Région" optional placeholder="Dakar"
                             :value="$commande->region" />
                </div>

                {{-- Au Sénégal, un repère vaut mieux qu'un code postal que
                     personne n'utilise. --}}
                <x-textarea name="delivery_notes" label="Indications pour le livreur" :optional="true"
                            placeholder="En face de la pharmacie, portail vert"
                            :rows="2" :maxlength="500">{{ $commande->delivery_notes }}</x-textarea>

                <div class="step-card__foot">
                    <a href="{{ route('dashboard') }}" class="step-back">Plus tard</a>
                    <x-button>Enregistrer l'adresse</x-button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
