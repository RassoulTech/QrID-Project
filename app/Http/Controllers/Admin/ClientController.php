<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Admin\SubscriptionExtensionService;
use App\Support\CsvExport;
use App\Support\FiltrePeriode;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des clients et fiche client.
 *
 * FILTRES EN QUERY STRING, jamais en session. Un filtre mémorisé côté serveur
 * produit l'incident classique : on revient sur l'écran deux jours plus tard,
 * la liste est amputée, et rien à l'écran ne dit pourquoi. En query string,
 * l'URL dit toujours ce qu'elle montre — et le lien se partage.
 *
 * COMPTE DES REQUÊTES
 *   liste ...... 4 (comptage de pagination, lignes, profils, abonnements+plans)
 *   fiche ...... 7 (client, profil+modèle, abonnements+plans, paiements,
 *                   journal+auteurs, compteurs d'événements, abonnement courant)
 */
class ClientController extends Controller
{
    private const PAR_PAGE = 15;

    public function index(Request $request): View
    {
        $clients = $this->requete($request)
            ->paginate(self::PAR_PAGE)
            ->withQueryString();   // la pagination ne perd jamais les filtres

        return view('admin.clients.index', [
            'clients' => $clients,
            'recherche' => $request->query('q'),
            'statut' => $request->query('statut'),
            'periode' => FiltrePeriode::valide($request->query('periode')),
            'periodes' => FiltrePeriode::options(),
            'total' => $clients->total(),
        ]);
    }

    public function show(User $user): View
    {
        /*
         | Un administrateur SANS CARTE n'est pas un client : sa fiche n'aurait
         | rien à montrer.
         |
         | La condition portait sur le seul rôle, et son commentaire affirmait
         | qu'un administrateur « n'a ni profil ni abonnement ». C'était faux
         | dès qu'on promeut le compte de l'exploitant, qui se sert du produit
         | pour lui-même : sa fiche existait, et devenait inatteignable.
         |
         | Même règle que la liste — un client est un compte qui a une carte.
         */
        abort_if($user->isAdmin() && $user->profile === null, 404);

        $user->load([
            'profile.template',
            'subscriptions.plan',
        ]);

        return view('admin.clients.show', [
            'client' => $user,
            'profil' => $user->profile,
            'abonnements' => $user->subscriptions->sortByDesc('id'),
            'paiements' => Payment::query()
                ->where('user_id', $user->id)
                ->with('subscription.plan')
                ->latest('id')
                ->limit(20)
                ->get(),
            'journal' => AdminAction::query()
                ->with('admin:id,name')
                ->where(function (Builder $q) use ($user) {
                    $q->where(fn (Builder $c) => $c->where('target_type', User::class)->where('target_id', $user->id))
                        ->orWhere(fn (Builder $c) => $c->where('target_type', Profile::class)
                            ->where('target_id', $user->profile?->id ?? 0));
                })
                ->latest('created_at')
                ->limit(30)
                ->get(),
            'prolongeable' => app(SubscriptionExtensionService::class)->abonnementAProlonger($user),
            'joursMax' => SubscriptionExtensionService::JOURS_MAX,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return CsvExport::stream(
            CsvExport::nom('clients'),
            ['Nom', 'E-mail', 'Téléphone', 'Profil', 'Abonnement', 'Inscription', 'Compte'],
            $this->requete($request)->orderBy('users.id'),
            fn (User $client) => [
                $client->name,
                $client->email,
                $client->phone ?? '',
                $client->profile?->etatLibelle() ?? 'Aucun profil',
                $client->activeSubscription()?->plan?->name ?? 'Aucun',
                $client->created_at?->format('d/m/Y') ?? '',
                $client->isBlocked() ? 'Bloqué' : 'Actif',
            ]
        );
    }

    /**
     * LA MÊME REQUÊTE sert la liste et l'export.
     *
     * C'est le point : un export qui ne renvoie pas exactement ce que l'écran
     * affiche est pire qu'une absence d'export — on prend des décisions sur un
     * fichier que l'on croit être la liste filtrée.
     */
    private function requete(Request $request): Builder
    {
        return User::query()
            /*
             | UN CLIENT EST UN COMPTE QUI A UNE CARTE — pas un compte dont le
             | rôle vaut « user ».
             |
             | Le filtre ne regardait que le rôle. Conséquence : l'exploitant
             | qui se sert du produit pour lui-même DISPARAISSAIT de sa propre
             | liste de clients au moment où on le promeut administrateur — et
             | avec lui la seule voie pour prolonger son abonnement, débloquer
             | son compte ou lire ses paiements.
             |
             | Constaté le 17 août : ADMIN_EMAIL promeut le compte du
             | propriétaire à chaque déploiement, sa fiche devenait
             | introuvable, et l'encaissement manuel — l'issue C du plan —
             | n'était plus praticable sur lui.
             |
             | Un administrateur SANS carte reste hors de cette liste : il
             | n'est pas un client, et l'y faire figurer fausserait les
             | chiffres autant que le regard.
             */
            ->where(fn (Builder $q) => $q
                ->where('role', User::ROLE_USER)
                ->orWhereHas('profile')
            )
            // Chargées d'avance : la colonne « statut du profil » et la
            // colonne « abonnement » lisent ces relations sur chaque ligne.
            ->with(['profile:id,user_id,is_active,deactivated_at', 'subscriptions.plan:id,name'])
            ->when($request->query('q'), fn (Builder $q, string $terme) => $q->where(
                fn (Builder $c) => $c->where('name', 'like', "%{$terme}%")
                    ->orWhere('email', 'like', "%{$terme}%")
                    ->orWhere('phone', 'like', "%{$terme}%")
            ))
            ->tap(fn (Builder $q) => FiltrePeriode::appliquer(
                $q, $request->query('periode'), 'users.created_at'
            ))
            ->when($request->query('statut'), fn (Builder $q, string $statut) => match ($statut) {
                'actif' => $q->where('is_blocked', false),
                'bloque' => $q->where('is_blocked', true),
                'abonne' => $q->whereHas('subscriptions', fn (Builder $s) => $s->where('status', Subscription::STATUS_ACTIVE)),
                'sans_abonnement' => $q->whereDoesntHave('subscriptions', fn (Builder $s) => $s->where('status', Subscription::STATUS_ACTIVE)),
                default => $q,
            })
            ->latest('created_at');
    }
}
