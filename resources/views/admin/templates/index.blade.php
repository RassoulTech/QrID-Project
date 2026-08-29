{{--
  GESTION DES MODÈLES — écran 6.

  Une grille de cartes, pas un tableau : ce que l'on compare ici est VISUEL.
  Un tableau de noms ne dirait rien de ce qui distingue un modèle d'un autre.

  L'aperçu est reconstruit en CSS à partir des attributs du modèle. Aucune
  image n'est stockée : un fichier d'aperçu se désynchronise du rendu réel
  dès la première retouche du gabarit, et personne ne s'en aperçoit.
--}}
<x-admin-layout
    :title="__('admin.modeles.titre')"
    :subtitle="__('admin.modeles.sous_titre')"
>
    <x-slot:actions>
        {{-- Pas de bouton « Nouveau modèle » : créer un gabarit demande d'en
             écrire le rendu, ce qui ne se fait pas depuis un formulaire. La
             duplication d'un modèle existant est le point de départ prévu. --}}
        <span class="adm-head__sous">
            {{ __('admin.modeles.creation_aide') }}
        </span>
    </x-slot:actions>

    <nav class="adm-onglets" aria-label="{{ __('admin.modeles.filtrer') }}">
        @foreach ([
            'tous' => ['Tous les modèles', $compteurs['tous']],
            'actifs' => ['Actifs', $compteurs['actifs']],
            'premium' => ['Premium', $compteurs['premium']],
        ] as $cle => [$libelle, $nombre])
            <a href="{{ route('admin.templates.index', ['onglet' => $cle === 'tous' ? null : $cle]) }}"
               @class(['adm-onglet', 'is-active' => $onglet === $cle])
               @if ($onglet === $cle) aria-current="true" @endif>
                {{ $libelle }}
                <span class="adm-onglet__n">{{ $nombre }}</span>
            </a>
        @endforeach
    </nav>

    @if ($modeles->isEmpty())
        <div class="adm-bloc">
            <x-empty-state icon="document"
                :title="__('admin.modeles.aucun_titre')"
                :message="__('admin.modeles.aucun_message')" />
        </div>
    @else
        <div class="adm-modeles">
            @foreach ($modeles as $modele)
                <article class="adm-modele">

                    {{-- APERÇU — reconstruction fidèle du gabarit, pas une image. --}}
                    <div class="adm-modele__apercu adm-modele__apercu--{{ $modele->slug }}" aria-hidden="true">
                        <span class="adm-modele__bandeau"></span>
                        <span class="adm-modele__pastille"></span>
                        <span class="adm-modele__trait"></span>
                        <span class="adm-modele__trait adm-modele__trait--court"></span>
                    </div>

                    <div class="adm-modele__corps">
                        <div class="adm-modele__tete">
                            <h2 class="adm-modele__nom">{{ $modele->name }}</h2>

                            {{-- L'interrupteur est un formulaire PATCH : on modifie
                                 l'état d'une ressource, on n'en crée aucune. --}}
                            <form method="POST" action="{{ route('admin.templates.toggle', $modele) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        @class(['adm-switch', 'is-on' => $modele->is_active])
                                        role="switch"
                                        aria-checked="{{ $modele->is_active ? 'true' : 'false' }}"
                                        aria-label="{{ $modele->is_active ? __('common.actions.desactiver') : __('common.actions.activer') }} {{ __('admin.modeles.le_modele') }} {{ $modele->name }}">
                                    <span class="adm-switch__bille"></span>
                                </button>
                            </form>
                        </div>

                        <p class="adm-modele__meta">
                            @if ($modele->is_default)
                                <x-badge variant="success">{{ __('admin.modeles.par_defaut') }}</x-badge>
                            @endif
                            @if ($modele->is_premium)
                                <x-badge variant="warning">Premium</x-badge>
                            @endif
                            <span class="adm-table__second">
                                {{ $modele->profiles_count }} carte{{ $modele->profiles_count > 1 ? 's' : '' }}
                            </span>
                        </p>

                        <div class="adm-modele__actions">
                            <form method="POST" action="{{ route('admin.templates.duplicate', $modele) }}">
                                @csrf
                                <button type="submit" class="adm-lien adm-lien--action">Dupliquer</button>
                            </form>

                            {{-- L'action n'apparaît que si elle a un sens : proposer
                                 « Définir par défaut » sur le modèle qui l'est déjà
                                 laisserait croire qu'il reste quelque chose à faire. --}}
                            @if (! $modele->is_default && $modele->is_active)
                                <form method="POST" action="{{ route('admin.templates.default', $modele) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="adm-lien adm-lien--action">{{ __('admin.modeles.definir_defaut') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-admin-layout>
