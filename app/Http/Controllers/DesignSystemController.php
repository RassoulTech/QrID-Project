<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Contracts\View\View;

/**
 * Référence visuelle des composants. Strictement locale : cette page révèle
 * la structure de l'interface et n'a rien à faire en production.
 */
class DesignSystemController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->environment('local'), 404);

        // firstOrFail() renverrait une 500 sur une base fraîche. Un profil
        // fabriqué en mémoire suffit : cette page ne montre que du style.
        $profile = Profile::query()->with('socialLinks')->orderBy('id')->first()
            ?? new Profile([
                'first_name' => 'Awa',
                'last_name' => 'Ndiaye',
                'job_title' => 'Consultante en gestion',
                'company' => 'Teranga Conseil',
                'phone' => '+221773831364',
                'public_email' => 'awa@exemple.sn',
                'address' => 'Sacré-Cœur 3, Dakar',
                'primary_color' => '#0B3B2E',
            ]);

        return view('design-system', ['profile' => $profile]);
    }
}
