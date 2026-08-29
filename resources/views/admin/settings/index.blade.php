{{--
  PARAMÈTRES DE LA PLATEFORME — écran 7.

  Deux colonnes : les offres à gauche, l'éditeur de la formule choisie à
  droite. La formule sélectionnée est un SEGMENT D'URL, pas un état de
  session : le lien vers une formule précise se copie et se rouvre tel quel.

  LE SLUG EST IMMUABLE APRÈS CRÉATION. Il est écrit dans payments.payload à
  chaque encaissement, et CheckoutService s'en sert pour retrouver la formule
  au retour de l'opérateur. Le renommer casserait rétroactivement tous les
  paiements en cours de route.
--}}
<x-admin-layout
    :title="__('admin.parametres.titre')"
    :subtitle="__('admin.parametres.sous_titre')"
>
    <nav class="adm-onglets" aria-label="{{ __('admin.parametres.sections') }}">
        @foreach ($onglets as $cle => $libelle)
            <a href="{{ route('admin.settings', ['onglet' => $cle === 'plans' ? null : $cle]) }}"
               @class(['adm-onglet', 'is-active' => $onglet === $cle])
               @if ($onglet === $cle) aria-current="true" @endif>{{ __($libelle) }}</a>
        @endforeach
    </nav>

    @if ($onglet !== 'plans')
        {{-- ÉTAT VIDE ASSUMÉ. Ces deux onglets n'ont aucun réglage derrière
             eux dans ce produit : ni table de configuration, ni option
             modifiable à chaud. Afficher des champs qui n'écrivent nulle part
             serait exactement le faux écran que ce produit s'interdit. --}}
        <div class="adm-bloc">
            <x-empty-state icon="document"
                :title="__('admin.parametres.aucun_reglage')"
                message="{{ $onglet === 'securite'
                    ? 'La durée des sessions, la politique de mot de passe et la limitation des tentatives sont fixées dans la configuration du serveur. Les modifier depuis une page web les rendrait modifiables par quiconque obtiendrait un accès administrateur.'
                    : 'Le nom du produit, les coordonnées de support et les mentions légales sont fixés dans la configuration du dépôt. Cette section attend les réglages qui auront un sens à être changés à chaud.' }}" />
        </div>
    @else
        <div class="adm-grille adm-grille--2-1" style="grid-template-columns:1fr">
            <div class="adm-grille adm-grille--1-1" style="margin-bottom:0">

                {{-- ==================== OFFRES ACTUELLES ==================== --}}
                <section class="adm-bloc">
                    <div class="adm-bloc__tete">
                        <h2 class="adm-bloc__titre">{{ __('admin.parametres.offres_actuelles') }}</h2>
                    </div>

                    @forelse ($plans as $plan)
                        <a href="{{ route('admin.settings.plan', $plan) }}"
                           @class(['adm-offre', 'is-active' => $selection && $selection->is($plan)])>
                            <span class="adm-offre__tete">
                                <span class="adm-offre__nom">{{ $plan->name }}</span>
                                @if (! $plan->is_active)
                                    <x-badge variant="secondary">{{ __('admin.parametres.retiree') }}</x-badge>
                                @endif
                            </span>

                            <span class="adm-offre__periode">{{ $plan->periodicite() }}</span>

                            <span class="adm-offre__prix">
                                {{ number_format($plan->price_fcfa, 0, ',', ' ') }}
                                <small>FCFA</small>
                            </span>

                            {{-- Les inclusions, telles qu'elles seront lues par le
                                 client sur la page de paiement. --}}
                            <span class="adm-offre__liste">
                                @foreach (($plan->features ?? []) as $avantage)
                                    <span class="adm-offre__item">{{ $avantage }}</span>
                                @endforeach
                            </span>
                        </a>
                    @empty
                        <x-empty-state icon="payment"
                            :title="__('admin.parametres.aucune_formule_titre')"
                            :message="__('admin.parametres.aucune_formule_message')" />
                    @endforelse

                    {{-- CRÉATION — un formulaire à part entière, pas un lien vers
                         un écran vide. Le slug n'est demandé qu'ici : il ne sera
                         plus modifiable ensuite. --}}
                    <details class="adm-creer">
                        <summary class="adm-creer__ouvrir">{{ __('admin.parametres.creer_formule') }}</summary>

                        <form method="POST" action="{{ route('admin.settings.plan.store') }}" class="adm-form">
                            @csrf

                            <div class="adm-champ">
                                <label for="new_name">{{ __('admin.parametres.nom_formule') }}</label>
                                <input type="text" id="new_name" name="name" class="adm-modale__champ"
                                       value="{{ old('name') }}" required maxlength="120">
                            </div>

                            <div class="adm-champ">
                                <label for="new_slug">{{ __('admin.parametres.identifiant_technique') }}</label>
                                <input type="text" id="new_slug" name="slug" class="adm-modale__champ"
                                       value="{{ old('slug') }}" required maxlength="120"
                                       pattern="[A-Za-z0-9_\-]+"
                                       placeholder="mensuel-pro">
                                <span class="adm-champ__aide">
                                    {{ __('admin.parametres.identifiant_definitif') }}
                                </span>
                                @error('slug')<span class="adm-modale__erreur">{{ $message }}</span>@enderror
                            </div>

                            <div class="adm-champ">
                                <label for="new_price">{{ __('admin.parametres.prix_fcfa') }}</label>
                                <input type="number" id="new_price" name="price_fcfa" class="adm-modale__champ"
                                       value="{{ old('price_fcfa', 0) }}" required min="0" step="1">
                                <span class="adm-champ__aide">
                                    {{ __('admin.parametres.prix_aide') }}
                                </span>
                            </div>

                            <div class="adm-champ">
                                <label for="new_duration">{{ __('admin.parametres.periodicite') }}</label>
                                <select id="new_duration" name="duration_days" class="adm-select" required>
                                    @foreach ($periodicites as $jours => $libelle)
                                        <option value="{{ $jours }}" @selected(old('duration_days') == $jours)>
                                            {{ $libelle }} ({{ $jours }} jours)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="adm-btn adm-btn--vert">{{ __('admin.parametres.creer') }}</button>
                        </form>
                    </details>
                </section>

                {{-- ==================== ÉDITEUR ==================== --}}
                <section class="adm-bloc">
                    @if ($selection === null)
                        <x-empty-state icon="payment"
                            :title="__('admin.parametres.aucune_a_modifier_titre')"
                            :message="__('admin.parametres.aucune_a_modifier_message')" />
                    @else
                        <div class="adm-bloc__tete">
                            <h2 class="adm-bloc__titre">{{ __('admin.parametres.editeur') }}</h2>
                            <span class="adm-table__second">{{ $selection->name }}</span>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.plan.update', $selection) }}"
                              class="adm-form">
                            @csrf
                            @method('PATCH')

                            <div class="adm-champ">
                                <label for="name">{{ __('admin.parametres.nom_formule') }}</label>
                                <input type="text" id="name" name="name" class="adm-modale__champ"
                                       value="{{ old('name', $selection->name) }}" required maxlength="120">
                                @error('name')<span class="adm-modale__erreur">{{ $message }}</span>@enderror
                            </div>

                            <div class="adm-champ">
                                <label for="slug_lecture">{{ __('admin.parametres.identifiant_technique') }}</label>
                                {{-- En lecture seule, et le champ le dit. Le désactiver
                                     sans explication laisserait chercher pourquoi. --}}
                                <input type="text" id="slug_lecture" class="adm-modale__champ"
                                       value="{{ $selection->slug }}" readonly disabled>
                                <span class="adm-champ__aide">
                                    {{ __('admin.parametres.identifiant_fige') }}
                                </span>
                            </div>

                            <div class="adm-champ">
                                <label for="price_fcfa">{{ __('admin.parametres.prix_fcfa') }}</label>
                                <input type="number" id="price_fcfa" name="price_fcfa" class="adm-modale__champ"
                                       value="{{ old('price_fcfa', $selection->price_fcfa) }}"
                                       required min="0" step="1">
                                <span class="adm-champ__aide">
                                    {{ __('admin.parametres.prix_aide_edition') }}
                                </span>
                                @error('price_fcfa')<span class="adm-modale__erreur">{{ $message }}</span>@enderror
                            </div>

                            <div class="adm-champ">
                                <label for="duration_days">{{ __('admin.parametres.periodicite') }}</label>
                                <select id="duration_days" name="duration_days" class="adm-select" required>
                                    @foreach ($periodicites as $jours => $libelle)
                                        <option value="{{ $jours }}"
                                            @selected(old('duration_days', $selection->duration_days) == $jours)>
                                            {{ $libelle }} ({{ $jours }} jours)
                                        </option>
                                    @endforeach

                                    {{-- Une durée hors catalogue existe en base et reste
                                         valide : la retirer de la liste la remplacerait
                                         silencieusement au premier enregistrement. --}}
                                    @unless (array_key_exists($selection->duration_days, $periodicites))
                                        <option value="{{ $selection->duration_days }}" selected>
                                            {{ $selection->duration_days }} jours
                                        </option>
                                    @endunless
                                </select>
                            </div>

                            <fieldset class="adm-champ">
                                <legend>{{ __('admin.parametres.elements_inclus') }}</legend>

                                @php $avantages = old('features', $selection->features ?? []); @endphp

                                @foreach ($avantages as $i => $avantage)
                                    <input type="text" name="features[]" class="adm-modale__champ adm-champ__ligne"
                                           value="{{ $avantage }}" maxlength="160"
                                           aria-label="Élément inclus n° {{ $i + 1 }}">
                                @endforeach

                                {{-- Deux lignes vides pour ajouter sans JavaScript. Les
                                     lignes laissées vides sont filtrées côté serveur :
                                     ajouter puis se raviser est un geste normal, pas
                                     une erreur de saisie. --}}
                                <input type="text" name="features[]" class="adm-modale__champ adm-champ__ligne"
                                       maxlength="160" placeholder="{{ __('admin.parametres.ajouter_element') }}"
                                       aria-label="{{ __('admin.parametres.nouvel_element') }}">
                                <input type="text" name="features[]" class="adm-modale__champ adm-champ__ligne"
                                       maxlength="160" placeholder="{{ __('admin.parametres.ajouter_element') }}"
                                       aria-label="{{ __('admin.parametres.nouvel_element') }}">

                                <span class="adm-champ__aide">
                                    {{ __('admin.parametres.vider_pour_retirer') }}
                                </span>
                            </fieldset>

                            <label class="adm-coche">
                                <input type="checkbox" name="is_active" value="1"
                                       @checked(old('is_active', $selection->is_active))>
                                <span>{{ __('admin.parametres.proposee_vente') }}</span>
                            </label>

                            <button type="submit" class="adm-btn adm-btn--vert">{{ __('common.actions.enregistrer') }}</button>
                        </form>
                    @endif
                </section>
            </div>
        </div>
    @endif
</x-admin-layout>
