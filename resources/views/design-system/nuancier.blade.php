{{-- Gabarit de App\View\Components\Nuancier : les pastilles d'un thème.
     La couleur de chaque pastille vient de la PROPRIÉTÉ CSS du rôle
     (`.ds-nuance--marque` → var(--marque)) ; le code affiché vient de
     `_tokens.scss`. Les deux ne peuvent pas diverger. --}}
<div class="ds-nuancier">
    @foreach ($nuances as $nuance)
        <div class="ds-nuance">
            <span class="ds-nuance__pastille ds-nuance--{{ $nuance['classe'] }}" aria-hidden="true"></span>
            <span class="ds-nuance__texte">
                <b>{{ $nuance['nom'] }}</b>
                {{ $nuance['role'] }}
                <code>{{ $nuance['hex'] }}</code>
            </span>
        </div>
    @endforeach
</div>
