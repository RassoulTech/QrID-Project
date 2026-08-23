<?php

namespace App\Http\Controllers\Profile;

use App\Enums\VarianteCarte;
use App\Events\ProfileCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\WizardStepOneRequest;
use App\Http\Requests\Profile\WizardStepThreeRequest;
use App\Http\Requests\Profile\WizardStepTwoRequest;
use App\Models\Template;
use App\Services\ProfileWizardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Parcours de création du profil, en trois étapes.
 *
 * Multi-pages avec persistance en session. Chaque « Continuer » est un POST
 * suivi d'une redirection : un rafraîchissement ne resoumet jamais, et le
 * retour arrière du navigateur ne casse rien.
 *
 * La navigation ne dépend d'aucun JavaScript.
 */
class ProfileWizardController extends Controller
{
    /*
     |--------------------------------------------------------------------------
     | LE NUANCIER A DISPARU — voir App\Enums\VarianteCarte
     |--------------------------------------------------------------------------
     | Cinq teintes au choix produisaient cinq marques : celui qui reçoit une
     | carte ambre et une carte grenat ne voit pas deux clients d'un même
     | service, il voit deux services. Chaque carte imprimée est un support de
     | communication pour la plateforme, et cette cohérence-là ne se délègue
     | pas au client.
     |
     | Restent DEUX variantes, présentées comme deux cartes et non comme des
     | pastilles. La différence de formulation compte : un nuancier invite à
     | composer, deux aperçus invitent à choisir.
     */

    public function __construct(private ProfileWizardService $wizard) {}

    /**
     * Entrée en modification d'un profil existant.
     *
     * On recharge le profil en session puis on renvoie sur l'étape 1 : le
     * parcours de création sert aussi de parcours d'édition.
     */
    public function edit(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return redirect()->route('profile.create.step1');
        }

        $this->authorize('update', $profile);

        $profile->load('socialLinks');
        $this->wizard->hydrateFrom($profile);

        /*
         | Garde anti-boucle.
         |
         | step1 renvoie ici quand un profil existe, et l'on renvoie vers step1.
         | Si la session ne se conserve pas (cookies bloqués), les deux routes
         | se renverraient la balle indéfiniment. On ne repart donc que si
         | l'état d'édition est bien retombé en session.
         */
        if (! $this->wizard->isEditing()) {
            return redirect()->route('profile.preview')
                ->with('warning', 'Impossible d\'ouvrir la modification : votre navigateur '
                    .'refuse les cookies nécessaires.');
        }

        return redirect()->route('profile.create.step1');
    }

    // =======================================================================
    // ÉTAPE 1 — Qui êtes-vous
    // =======================================================================

    public function stepOne(): View|RedirectResponse
    {
        // Un profil existe déjà et l'on n'est pas en édition : on bascule sur
        // l'édition, qui recharge le profil en session puis revient ici.
        if (request()->user()->profile !== null && ! $this->wizard->isEditing()) {
            return redirect()->route('profile.edit');
        }

        return view('profile.wizard.step-1', [
            'step' => 1,
            'wizard' => $this->wizard,
        ]);
    }

    public function storeStepOne(WizardStepOneRequest $request): RedirectResponse
    {
        if ($request->user()->profile !== null && ! $this->wizard->isEditing()) {
            return redirect()->route('profile.edit');
        }

        $data = $request->safe()->except(['photo', 'cover']);

        /*
         | LES OCTETS SONT GARDÉS EN MÉMOIRE, pas relus sur le disque.
         |
         | C'est ce qu'on vient de produire, à l'instant, dans ce processus.
         | Toute relecture ultérieure peut échouer — et un échec silencieux
         | écrivait un profil avec un chemin et sans image.
         */
        $images = [];

        if ($request->hasFile('photo')) {
            $depot = $this->wizard->storePhoto($request->file('photo'));

            $data['photo_path'] = $depot['chemin'];
            $images['photo_path'] = $depot['chemin'];
            $images['photo_data'] = $depot['octets'];
        }

        if ($request->hasFile('cover')) {
            $depot = $this->wizard->storeCover($request->file('cover'));

            $data['cover_path'] = $depot['chemin'];
            $images['cover_path'] = $depot['chemin'];
            $images['cover_data'] = $depot['octets'];
        }

        $this->wizard->saveStep(1, $data);

        /*
         | ═══════════════════════════════════════════════════════════════
         | UNE IMAGE DÉPOSÉE SUR UN PROFIL QUI EXISTE EST ÉCRITE TOUT DE SUITE
         | ═══════════════════════════════════════════════════════════════
         | LE DÉFAUT QUE CELA CORRIGE, ET IL A COÛTÉ TROIS ALLERS-RETOURS.
         |
         | Le profil n'était écrit qu'à l'ÉTAPE 3. Quelqu'un qui ouvrait
         | « Modifier », déposait sa photo, la voyait apparaître dans la
         | vignette et refermait l'onglet — parce que tout semblait fait —
         | ne changeait rien du tout. Le fichier restait sur le disque, la
         | session gardait son chemin, et la carte publique continuait
         | d'afficher son repli. Rien n'échouait, rien n'était signalé, et
         | le client concluait que le produit ne savait pas garder une photo.
         |
         | La règle est donc : à la CRÉATION, on écrit à la fin, parce
         | qu'aucune ligne incomplète ne doit polluer la table. À l'ÉDITION,
         | le profil existe déjà — il n'y a plus rien à protéger, et une
         | image déposée est une intention claire. On l'écrit.
         |
         | Seules les IMAGES sont concernées. Un nom à moitié corrigé n'a
         | pas à partir en base avant que la personne ait validé son
         | parcours ; une photo, si : c'est le geste le plus explicite du
         | formulaire, et le plus coûteux à refaire.
         */
        $this->enregistrerImagesImmediatement($request, $images);

        return redirect()->route('profile.create.step2');
    }

    /**
     * Écrit photo et bannière sur le profil existant, sans attendre la fin.
     *
     * Sans effet à la création : il n'y a pas encore de profil à écrire, et
     * persist() s'en chargera. Sans effet non plus si rien n'a été déposé.
     */
    /**
     * @param  array<string, string|null>  $images  colonnes prêtes à écrire
     */
    private function enregistrerImagesImmediatement(Request $request, array $images): void
    {
        $profile = $request->user()->profile;

        if ($profile === null || $images === []) {
            return;
        }

        $profile->forceFill($images)->save();

        /*
         | L'ÉCHEC EST DIT, JAMAIS AVALÉ.
         |
         | Une image trop lourde pour la base est déposée sur le disque et
         | disparaîtra au prochain déploiement. C'est rare — il faut que GD
         | manque ou que l'image soit indécodable — mais c'était jusqu'ici
         | parfaitement silencieux : le client voyait sa photo, puis ne la
         | voyait plus, sans que rien ne le lui ait dit.
         */
        $perdues = array_keys(array_filter(
            ['photo_data' => $images['photo_data'] ?? true, 'cover_data' => $images['cover_data'] ?? true],
            fn ($octets) => $octets === null
        ));

        if ($perdues !== []) {
            session()->flash('alerte', __(
                "Votre image a été enregistrée, mais elle est trop lourde pour être conservée durablement. ".
                "Choisissez une image plus légère si elle venait à disparaître."
            ));
        }
    }

    // =======================================================================
    // ÉTAPE 2 — Comment vous joindre
    // =======================================================================

    public function stepTwo(): View
    {
        return view('profile.wizard.step-2', [
            'step' => 2,
            'wizard' => $this->wizard,
            'platforms' => ProfileWizardService::PLATFORMS,
        ]);
    }

    public function storeStepTwo(WizardStepTwoRequest $request): RedirectResponse
    {
        $data = array_merge(
            $request->safe()->except('phone', 'whatsapp', 'socials'),
            $request->canonicalPhones(),
            ['socials' => $request->cleanSocials()],
        );

        $this->wizard->saveStep(2, $data);

        // Le bouton « Ajouter un réseau » sans JavaScript : on renvoie sur la
        // même étape, une ligne vide de plus. Rien n'est perdu, l'utilisateur
        // continue quand il le décide.
        if ($request->input('action') === 'add_social') {
            return redirect()->route('profile.create.step2')->withInput();
        }

        return redirect()->route('profile.create.step3');
    }

    // =======================================================================
    // ÉTAPE 3 — Votre style → persistance et aperçu
    // =======================================================================

    public function stepThree(): View
    {
        return view('profile.wizard.step-3', [
            'step' => 3,
            'wizard' => $this->wizard,
            'templates' => Template::active()->orderBy('id')->take(3)->get(),
            'variantes' => VarianteCarte::toutes(),
        ]);
    }

    public function storeStepThree(WizardStepThreeRequest $request): RedirectResponse
    {
        $this->wizard->saveStep(3, $request->validated());

        $etaitEnEdition = $this->wizard->isEditing();

        $profile = $this->wizard->persist($request->user());

        /*
         | ÉVÉNEMENT ÉMIS À LA CRÉATION SEULEMENT, JAMAIS À L'ÉDITION.
         |
         | Le même parcours sert aux deux : sans cette condition, chaque
         | correction d'une faute de frappe relancerait l'alerte « nouvelle
         | carte créée » vers l'équipe, et remettrait le compteur des rappels
         | à zéro pour quelqu'un qui a déjà publié.
         */
        if (! $etaitEnEdition) {
            event(new ProfileCreated($profile));
        }

        // L'état de session n'a plus lieu d'être : le profil existe en base.
        // clear() supprimerait la photo, or elle appartient au profil désormais.
        session()->forget('profile_wizard');

        $message = $etaitEnEdition
            ? 'Vos modifications sont enregistrées.'
            : 'Votre profil est prêt. Il reste à l\'activer.';

        return redirect()->route('profile.preview')->with('success', $message);
    }
}
