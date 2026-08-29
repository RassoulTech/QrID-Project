<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Services\Admin\TemplateAdminService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Gestion des modèles de carte.
 *
 * L'onglet est un paramètre de requête : « tous », « actifs », « premium ».
 * Trois URL distinctes pour trois filtres du même écran multiplieraient les
 * entrées de menu à maintenir sans rien apporter.
 *
 * COMPTE DES REQUÊTES — 2 : les modèles avec leur nombre de profils, et les
 * compteurs d'onglets.
 */
class TemplateController extends Controller
{
    public function __construct(private TemplateAdminService $service) {}

    public function index(Request $request): View
    {
        $onglet = in_array($request->query('onglet'), ['actifs', 'premium'], true)
            ? $request->query('onglet')
            : 'tous';

        $modeles = Template::query()
            // Le nombre de profils sert l'avertissement avant désactivation :
            // couper un modèle utilisé par quatre-vingts cartes n'est pas le
            // même geste que couper un modèle qui n'a jamais servi.
            ->withCount('profiles')
            ->when($onglet === 'actifs', fn ($q) => $q->where('is_active', true))
            ->when($onglet === 'premium', fn ($q) => $q->where('is_premium', true))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.templates.index', [
            'modeles' => $modeles,
            'onglet' => $onglet,
            'compteurs' => $this->compteurs(),
        ]);
    }

    public function toggle(Template $template): RedirectResponse
    {
        try {
            $this->service->basculer($template);
        } catch (RuntimeException $e) {
            return back()->withErrors(['modele' => $e->getMessage()]);
        }

        return back()->with('status', $template->is_active
            ? "Le modèle « {$template->name} » est proposé aux clients."
            : "Le modèle « {$template->name} » n'est plus proposé. Les cartes existantes ne changent pas.");
    }

    public function duplicate(Template $template): RedirectResponse
    {
        $copie = $this->service->dupliquer($template);

        return back()->with('status',
            "« {$copie->name} » créé, inactif. Relisez-le avant de le proposer aux clients."
        );
    }

    public function makeDefault(Template $template): RedirectResponse
    {
        try {
            $this->service->definirParDefaut($template);
        } catch (RuntimeException $e) {
            return back()->withErrors(['modele' => $e->getMessage()]);
        }

        return back()->with('status', __('admin.flash.modele_par_defaut', ['nom' => $template->name]));
    }

    /** @return array<string, int> */
    private function compteurs(): array
    {
        $ligne = Template::query()
            ->selectRaw('COUNT(*) as tous')
            ->selectRaw('SUM(is_active = 1) as actifs')
            ->selectRaw('SUM(is_premium = 1) as premium')
            ->first();

        return [
            'tous' => (int) ($ligne->tous ?? 0),
            'actifs' => (int) ($ligne->actifs ?? 0),
            'premium' => (int) ($ligne->premium ?? 0),
        ];
    }
}
