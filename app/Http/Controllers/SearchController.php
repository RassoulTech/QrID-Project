<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Recherche dans l'espace client.
 *
 * FORMULAIRE GET, terme en query string, résultats sur une page dédiée :
 * la recherche est partageable, rechargeable, et fonctionne sans une ligne
 * de JavaScript.
 *
 * Le périmètre est celui du COMPTE CONNECTÉ, et rien d'autre : sa carte, ses
 * paiements, ses notifications. Il n'existe aucun chemin permettant de
 * chercher dans les données d'un autre client.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $terme = trim((string) $request->query('q', ''));

        if (mb_strlen($terme) < 2) {
            return view('search.results', [
                'terme' => $terme,
                'tropCourt' => $terme !== '',
                'profil' => null,
                'paiements' => collect(),
                'notifications' => collect(),
            ]);
        }

        $user = $request->user();
        $motif = '%'.str_replace(['%', '_'], ['\%', '\_'], $terme).'%';

        // La carte : une seule, on vérifie simplement si elle correspond.
        $profil = $user->profile()
            ->where(fn ($q) => $q
                ->whereLikeSouple('first_name', $motif)
                ->orWhereLikeSouple('last_name', $motif)
                ->orWhereLikeSouple('job_title', $motif)
                ->orWhereLikeSouple('company', $motif)
                ->orWhereLikeSouple('slug', $motif))
            ->first();

        return view('search.results', [
            'terme' => $terme,
            'tropCourt' => false,
            'profil' => $profil,

            'paiements' => Payment::where('user_id', $user->id)
                ->where(fn ($q) => $q->whereLikeSouple('provider_ref', $motif)->orWhereLikeSouple('method', $motif))
                ->latest()->limit(10)->get(),

            'notifications' => Notification::where('user_id', $user->id)
                ->where(fn ($q) => $q->whereLikeSouple('title', $motif)->orWhereLikeSouple('body', $motif))
                ->latest()->limit(10)->get(),
        ]);
    }
}
