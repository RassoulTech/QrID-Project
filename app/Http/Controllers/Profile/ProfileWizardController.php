<?php

namespace App\Http\Controllers\Profile;

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
    /**
     * Couleurs proposées. Liste fermée, dérivée de la palette : l'utilisateur
     * ne peut pas produire un profil illisible avec une couleur libre.
     */
    public const COLORS = [
        '#0B3B2E' => 'Vert',
        '#0F172A' => 'Nuit',
        '#7A3E12' => 'Ambre',
        '#0E5F73' => 'Océan',
        '#8C1D18' => 'Grenat',
    ];

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

        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->wizard->storePhoto($request->file('photo'));
        }

        $this->wizard->saveStep(1, $data);

        return redirect()->route('profile.create.step2');
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
            'colors' => self::COLORS,
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
