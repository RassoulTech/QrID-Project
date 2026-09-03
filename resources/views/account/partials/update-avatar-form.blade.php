{{--
  LA PHOTO DE COMPTE — celle qui remplace les initiales dans l'espace client.

  ═══════════════════════════════════════════════════════════════════════════
  ELLE N'EST PAS LA PHOTO DE LA CARTE, ET LE TEXTE LE DIT
  ═══════════════════════════════════════════════════════════════════════════
  La carte publique a une image : la couverture, choisie dans l'assistant, vue
  par les prospects. Celle-ci ne sort jamais de l'espace client.

  Sans cette phrase, un client qui importe ici sa photo s'attendrait à la voir
  sur sa carte, et conclurait que le téléversement n'a pas fonctionné.

  ═══════════════════════════════════════════════════════════════════════════
  L'APERÇU MONTRE L'ÉTAT ACTUEL, PAS UN IDÉAL
  ═══════════════════════════════════════════════════════════════════════════
  Le médaillon affiche ce que le compte a VRAIMENT : la photo importée, ou
  les initiales. C'est la même chose que ce qu'on voit en haut de l'écran —
  un aperçu qui montrerait autre chose serait pire que pas d'aperçu.
--}}
<div class="compte-avatar">

    <div class="compte-avatar__apercu">
        @if ($avatarUrl = $user->avatarUrl())
            <img src="{{ $avatarUrl }}" alt="" class="compte-avatar__image"
                 width="72" height="72">
        @else
            <span class="compte-avatar__initiales" aria-hidden="true">{{ $user->initiales() }}</span>
        @endif
    </div>

    <div class="compte-avatar__actions">
        <form method="POST" action="{{ route('compte.avatar.update') }}"
              enctype="multipart/form-data" class="compte-avatar__form">
            @csrf

            <label for="avatar" class="f__label">
                {{ __('profile.compte.avatar_titre') }}
                <span class="f__opt">{{ __('profile.compte.avatar_optionnel') }}</span>
            </label>

            <p class="f__help">{{ __('profile.compte.avatar_aide') }}</p>

            <input type="file" id="avatar" name="avatar" class="f__control"
                   accept="image/jpeg,image/png,image/webp">

            @error('avatar')
                <p class="f__error">{{ $message }}</p>
            @enderror

            <div class="compte-avatar__boutons">
                <x-button type="submit" size="sm">{{ __('profile.compte.avatar_enregistrer') }}</x-button>
            </div>
        </form>

        {{-- REVENIR AUX INITIALES n'est pas une suppression risquée : c'est
             l'état par défaut de tous les comptes. Aucune confirmation ne se
             justifie, et en demander une ferait croire à une action grave. --}}
        @if ($user->avatar_path)
            <form method="POST" action="{{ route('compte.avatar.destroy') }}">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="outline" size="sm">
                    {{ __('profile.compte.avatar_retirer') }}
                </x-button>
            </form>
        @endif
    </div>
</div>
