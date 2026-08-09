<?php

namespace App\Http\Middleware;

use App\Services\ProfileWizardService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interdit d'atteindre une étape dont la précédente n'est pas complétée.
 *
 * Deux garde-fous en un :
 *  1. Un profil existe déjà  → le parcours de création n'a plus lieu d'être,
 *     on bascule sur l'aperçu (l'édition passera par ses propres routes).
 *  2. Étape hors séquence    → redirection vers la PREMIÈRE étape manquante,
 *     jamais vers l'accueil : l'utilisateur reprend exactement là où il en est.
 */
class EnsureWizardStepIsAvailable
{
    public function __construct(private ProfileWizardService $wizard) {}

    public function handle(Request $request, Closure $next, int $step): Response
    {
        // Profil déjà créé ET pas en cours d'édition : on bascule sur l'édition.
        if ($request->user()?->profile !== null && ! $this->wizard->isEditing()) {
            return redirect()->route('profile.edit');
        }

        // Pour atteindre l'étape N, les étapes 1..N-1 doivent être derrière soi.
        for ($previous = 1; $previous < $step; $previous++) {
            if (! $this->wizard->isStepCompleted($previous)) {
                return redirect()
                    ->route('profile.create.step'.$this->wizard->nextStep())
                    ->with('warning', 'Terminez d\'abord cette étape.');
            }
        }

        return $next($request);
    }
}
