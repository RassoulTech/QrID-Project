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

        return redirect()->to($this->destinationSure($notification->url));
    }

    /**
     * OÙ UNE NOTIFICATION A LE DROIT DE MENER.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI GARDER UNE VALEUR QUE PERSONNE NE PEUT ÉCRIRE
     * ═══════════════════════════════════════════════════════════════════
     * Aujourd'hui, `url` ne vient que d'appels à `route()` dans
     * NotificationService : aucune saisie d'utilisateur ne l'atteint, et
     * cette redirection est parfaitement sûre.
     *
     * Elle l'est PAR ACCIDENT. Rien dans le code ne dit qu'il en sera
     * toujours ainsi, et la première notification construite à partir
     * d'une donnée de profil — une campagne, un lien de partage, un retour
     * d'opérateur — ferait de cette ligne une redirection ouverte : un
     * lien portant le domaine de QrID, cliqué en confiance depuis la boîte
     * de réception, qui dépose la personne sur un site tiers. C'est le
     * véhicule classique de l'hameçonnage, et il emprunte ici notre
     * réputation.
     *
     * La règle est donc posée maintenant, pendant qu'elle ne coûte rien :
     * une notification mène quelque part DANS l'application, ou elle mène
     * au tableau de bord. Le jour où quelqu'un voudra qu'elle mène
     * ailleurs, il faudra l'écrire explicitement — et ce sera une décision,
     * pas un oubli.
     */
    private function destinationSure(?string $url): string
    {
        $repli = route('dashboard');

        if ($url === null || $url === '') {
            return $repli;
        }

        // Un chemin relatif ne peut pas quitter le domaine.
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        // Une adresse absolue n'est suivie que si elle porte NOTRE hôte.
        $hote = parse_url($url, PHP_URL_HOST);

        return $hote !== null && $hote === parse_url(config('app.url'), PHP_URL_HOST)
            ? $url
            : $repli;
    }

    /** Tout marquer comme lu — une seule requête, jamais une boucle. */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->alerts()->unread()->update(['read_at' => now()]);

        return back()->with('success', __('dashboard.flash.notifications_lues'));
    }
}
