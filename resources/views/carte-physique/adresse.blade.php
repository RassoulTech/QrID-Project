{{--
  MA CARTE PHYSIQUE — l'adresse de livraison.

  Cet écran n'existe qu'après un premier paiement encaissé : demander une
  adresse avant d'avoir encaissé ferait remplir un formulaire pour un objet
  qui n'existera peut-être jamais.
--}}
<x-app-layout :title="__('card.physique.titre')">
    <div class="step-card">
        <p class="step-card__kicker">{{ __('card.physique.kicker') }}</p>
        <h1 class="step-card__title">{!! __('card.physique.entete') !!}</h1>

        <p class="step-card__sub">{!! __('card.physique.sous', ['jours' => $delai]) !!}</p>

        {{-- L'ÉTAT AVANT LE FORMULAIRE. Le client vient souvent pour savoir où
             en est sa carte, pas pour saisir quelque chose. --}}
        <div class="carte-etat">
            <span class="carte-etat__pastille">{{ $commande->statutLibelle() }}</span>

            @if ($commande->shipped_at)
                <span class="carte-etat__date">{{ __('card.physique.expediee_le', ['date' => $commande->shipped_at->format('d/m/Y')]) }}</span>
            @elseif ($commande->produced_at)
                <span class="carte-etat__date">{{ __('card.physique.imprimee_le', ['date' => $commande->produced_at->format('d/m/Y')]) }}</span>
            @endif
        </div>

        @if (! $commande->adresseModifiable())
            {{-- Le bordereau est figé : le dire, plutôt que d'afficher un
                 formulaire dont la soumission serait refusée. --}}
            <x-alert type="info" :dismissible="false">
                {{ __('card.physique.verrouillee') }}
            </x-alert>

            <dl class="carte-recap">
                <dt>{{ __('card.physique.destinataire') }}</dt><dd>{{ $commande->recipient_name ?? '—' }}</dd>
                <dt>{{ __('common.champs.telephone') }}</dt><dd>{{ $commande->phone ?? '—' }}</dd>
                <dt>{{ __('common.champs.adresse') }}</dt><dd>{{ $commande->address_line ?? '—' }}</dd>
                <dt>{{ __('card.physique.ville') }}</dt><dd>{{ $commande->city ?? '—' }}</dd>
            </dl>
        @else
            <form method="POST" action="{{ route('carte.physique.update') }}" novalidate>
                @csrf
                @method('PATCH')

                <x-form-legende />

                <x-field name="recipient_name" :label="__('card.physique.nom_destinataire')"
                         :value="$commande->recipient_name" autocomplete="name" />

                <x-phone-field name="phone" :label="__('card.physique.telephone_destinataire')"
                               :value="$commande->phone"
                               :help="__('card.physique.telephone_aide')" />

                <x-field name="address_line" :label="__('common.champs.adresse')"
                         :placeholder="__('card.physique.adresse_exemple')"
                         :value="$commande->address_line" autocomplete="street-address" />

                <div class="f-row">
                    <x-field name="city" :label="__('card.physique.ville')"
                             :placeholder="__('card.physique.ville_exemple')"
                             :value="$commande->city" autocomplete="address-level2" />

                    <x-field name="region" :label="__('card.physique.region')" optional
                             :placeholder="__('card.physique.ville_exemple')"
                             :value="$commande->region" />
                </div>

                {{-- Au Sénégal, un repère vaut mieux qu'un code postal que
                     personne n'utilise. --}}
                <x-textarea name="delivery_notes" :label="__('card.physique.indications')" :optional="true"
                            :placeholder="__('card.physique.indications_exemple')"
                            :rows="2" :maxlength="500">{{ $commande->delivery_notes }}</x-textarea>

                <div class="step-card__foot">
                    <a href="{{ route('dashboard') }}" class="step-back">{{ __('card.physique.plus_tard') }}</a>
                    <x-button>{{ __('card.physique.enregistrer') }}</x-button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
