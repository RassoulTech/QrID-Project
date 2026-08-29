<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MotifRequest;
use App\Models\Profile;
use App\Services\Admin\ProfileDeactivationService;
use Illuminate\Http\RedirectResponse;

/**
 * Désactivation d'un profil, avec motif obligatoire.
 *
 * Le profil est résolu par son SLUG (getRouteKeyName), comme partout ailleurs
 * dans le produit. Une administration qui manipulerait des identifiants
 * numériques là où le reste du produit parle en slugs obligerait à traduire
 * mentalement d'un écran à l'autre.
 */
class ProfileDeactivationController extends Controller
{
    public function __construct(private ProfileDeactivationService $service) {}

    public function store(MotifRequest $request, Profile $profile): RedirectResponse
    {
        if ($profile->isDeactivated()) {
            return back()->with('status', __('admin.flash.profil_deja_desactive'));
        }

        $this->service->desactiver($profile, $request->motif());

        return back()->with('status', __('admin.flash.profil_desactive', ['nom' => $profile->full_name]));
    }

    public function destroy(MotifRequest $request, Profile $profile): RedirectResponse
    {
        if (! $profile->isDeactivated()) {
            return back()->with('status', __('admin.flash.profil_pas_desactive'));
        }

        $this->service->reactiver($profile, $request->motif());

        return back()->with('status',
            "La désactivation est levée. Le profil reste en brouillon : c'est à son propriétaire de le republier."
        );
    }
}
