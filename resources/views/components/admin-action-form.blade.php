{{--
  x-admin-action-form — action sensible avec MOTIF OBLIGATOIRE.

      <x-admin-action-form :action="route('admin.clients.block', $client)"
                           libelle="Bloquer" titre="Bloquer ce compte"
                           id="bloc-12" ton="danger"
                           texte="Les sessions ouvertes seront fermées." />

  UN VRAI <dialog> HTML, pas une fenêtre Bootstrap. Le navigateur gère seul le
  voile, le piège du focus, la fermeture par Échap et le retour du focus au
  bouton d'origine. Aucune ligne de JavaScript à écrire pour cela.

  … à une exception près : `showModal()` n'est atteignable que par script.
  Sans JavaScript, le <dialog> reste donc fermé et le bouton ne ferait rien.
  D'où le <noscript> : il rend le formulaire directement dans la page. Aucune
  action n'est perdue, elle est seulement moins élégante.

  LE MOTIF EST EXIGÉ PAR LE SERVEUR (MotifRequest), pas seulement par le
  `required` du champ. Un formulaire contourné ne suffit pas à écrire une
  ligne au journal sans justification.
--}}
@props([
    'action',
    'libelle',
    'titre',
    'id',
    'texte' => null,
    'ton' => 'vert',           // vert | danger
    'methode' => 'POST',       // POST | DELETE | PATCH
    'confirmation' => null,    // libellé du bouton de validation
])

<button type="button" class="adm-lien adm-lien--action"
        onclick="document.getElementById('{{ $id }}').showModal()">
    {{ $libelle }}
</button>

<dialog id="{{ $id }}" class="adm-modale">
    <form method="POST" action="{{ $action }}" class="adm-modale__corps">
        @csrf
        @if ($methode !== 'POST')
            @method($methode)
        @endif

        <h2 class="adm-modale__titre">{{ $titre }}</h2>

        @if ($texte)
            <p class="adm-modale__texte">{{ $texte }}</p>
        @endif

        {{-- Champs propres à l'action, quand elle en demande. La prolongation
             d'abonnement a besoin d'un nombre de jours ; les autres actions
             n'ont que leur motif. Un emplacement plutôt qu'un composant par
             action. --}}
        {{ $champs ?? '' }}

        <label for="motif-{{ $id }}" class="adm-modale__label">
            {{ __('admin.commun.motif') }} <span class="adm-modale__obligatoire">{{ __('common.champs.obligatoire') }}</span>
        </label>

        <textarea id="motif-{{ $id }}" name="motif" rows="3" class="adm-modale__champ"
                  required minlength="10" maxlength="500"
                  placeholder="{{ __('admin.commun.motif_journal') }}"></textarea>

        {{-- Les erreurs de validation reviennent ici : sans cela, un motif
             trop court renverrait sur la liste sans rien dire. --}}
        @error('motif')
            <p class="adm-modale__erreur">{{ $message }}</p>
        @enderror

        <div class="adm-modale__pied">
            <button type="button" class="adm-btn adm-btn--clair"
                    onclick="document.getElementById('{{ $id }}').close()">Annuler</button>

            <button type="submit" @class(['adm-btn', 'adm-btn--vert' => $ton !== 'danger', 'adm-btn--danger' => $ton === 'danger'])>
                {{ $confirmation ?? $libelle }}
            </button>
        </div>
    </form>
</dialog>

<noscript>
    {{-- Sans script, la boîte de dialogue ne s'ouvre pas. Le formulaire est
         alors rendu à plat, dans la page : l'action reste faisable. --}}
    <form method="POST" action="{{ $action }}" class="adm-modale__repli">
        @csrf
        @if ($methode !== 'POST')
            @method($methode)
        @endif
        <label for="motif-ns-{{ $id }}" class="adm-modale__label">{{ $titre }} — motif</label>
        <textarea id="motif-ns-{{ $id }}" name="motif" rows="2" class="adm-modale__champ"
                  required minlength="10" maxlength="500"></textarea>
        <button type="submit" class="adm-btn adm-btn--vert">{{ $confirmation ?? $libelle }}</button>
    </form>
</noscript>
