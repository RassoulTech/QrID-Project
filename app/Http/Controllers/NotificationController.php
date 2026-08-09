<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifications du compte.
 *
 * Aucune notification n'est jamais lue par un autre compte que le sien :
 * toutes les requêtes partent de la relation de l'utilisateur connecté, pas
 * d'un identifiant reçu en URL.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->alerts()->latest()->paginate(20),
        ]);
    }

    /**
     * Ouvrir une notification la marque lue, puis mène où elle pointe.
     *
     * Le marquage se fait ICI et non à l'affichage de la liste : une alerte
     * vue du coin de l'œil dans un menu déroulant n'a pas été lue.
     */
    public function open(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->isUnread()) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return redirect()->to($notification->url ?? route('dashboard'));
    }

    /** Tout marquer comme lu — une seule requête, jamais une boucle. */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->alerts()->unread()->update(['read_at' => now()]);

        return back()->with('success', 'Toutes vos notifications sont marquées comme lues.');
    }
}
