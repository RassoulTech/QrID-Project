<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\CardOrder;
use App\Models\Payment;
use App\Support\AdminActionType;
use App\Support\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CARTES À PRODUIRE — l'écran de l'atelier.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ON N'ENVOIE PAS UNE CARTE À L'IMPRIMEUR, ON ENVOIE UN LOT
 * ═══════════════════════════════════════════════════════════════════════
 * Le coût unitaire d'une commande de vingt cartes est sans commune mesure avec
 * celui de vingt commandes d'une carte. Cet écran existe donc pour regrouper,
 * pas pour traiter à l'unité.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES COMMANDES SANS ADRESSE SONT MONTRÉES, PAS CACHÉES
 * ═══════════════════════════════════════════════════════════════════════
 * Un client qui paie puis ferme l'onglet a droit à sa carte, et personne ne le
 * saurait si l'écran ne listait que les commandes complètes. Ce sont
 * précisément celles-là qu'il faut relancer — elles apparaissent avec leur
 * ancienneté, qui dit depuis combien de temps on leur doit quelque chose.
 */
class CardOrderController extends Controller
{
    private const PAR_PAGE = 30;

    public function index(Request $request): View
    {
        $commandes = $this->requete($request)
            ->paginate(self::PAR_PAGE)
            ->withQueryString();

        return view('admin.cartes.index', [
            'commandes' => $commandes,
            'statut' => $request->query('statut'),
            'lot' => $request->query('lot'),
            'statuts' => CardOrder::STATUTS,
            'seuil' => (int) config('cartes.seuil_lot'),
            'compteurs' => $this->compteurs(),
            'economie' => $this->economie(),
        ]);
    }

    /**
     * Constitue un lot avec les commandes choisies.
     *
     * L'IDENTIFIANT EST LISIBLE PAR UN HUMAIN. « LOT-20260818-A3F2 » se recopie
     * au téléphone avec l'imprimeur ; un identifiant numérique s'échangerait
     * mal et se confondrait avec un numéro de commande.
     */
    public function batch(Request $request): RedirectResponse
    {
        $valide = $request->validate([
            /*
             | UNE BORNE HAUTE, ET ELLE MANQUAIT.
             |
             | La règle disait « un tableau, au moins un élément » et rien de
             | plus. Le `whereIn` juste en dessous recevait donc une liste de
             | taille illimitée : MySQL construit une clause IN aussi longue
             | que ce qu'on lui envoie, et le `filter()` en PHP charge tout
             | en mémoire avant de trier.
             |
             | Ce n'est pas un trou de sécurité — l'écran est derrière
             | ['auth','verified','admin'] — mais c'est une porte ouverte sur
             | un plantage : un administrateur qui coche « tout sélectionner »
             | sur une page de mille commandes suffit, sans la moindre
             | mauvaise intention.
             |
             | 200 est le seuil de lot du produit multiplié par une marge
             | confortable. Au-delà, l'écran demande de traiter en plusieurs
             | fois, ce qui est de toute façon ce qu'on fait à l'imprimeur.
             */
            'commandes' => ['required', 'array', 'min:1', 'max:200'],

            // `exists` en plus de `integer` : un identifiant inventé
            // traversait la validation et n'était écarté que par la requête
            // suivante. Le refuser ici rend le message d'erreur exact.
            'commandes.*' => ['integer', 'exists:card_orders,id'],
        ]);

        $lot = 'LOT-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));

        /*
         | ON NE MET EN LOT QUE CE QUI EST RÉELLEMENT PRODUCTIBLE.
         |
         | Une commande sans adresse partirait chez l'imprimeur sans qu'on
         | sache où l'envoyer : la carte serait fabriquée, payée, et resterait
         | dans un carton. Le filtre est ici, et pas seulement dans les cases à
         | cocher — une requête forgée contournerait l'écran.
         */
        $eligibles = CardOrder::whereIn('id', $valide['commandes'])
            ->where('status', CardOrder::STATUS_PENDING)
            ->get()
            ->filter(fn (CardOrder $commande) => $commande->adresseComplete());

        if ($eligibles->isEmpty()) {
            return back()->with('warning', __('admin.flash.aucune_adresse'));
        }

        CardOrder::whereIn('id', $eligibles->pluck('id'))->update([
            'status' => CardOrder::STATUS_IN_BATCH,
            'batch_id' => $lot,
        ]);

        AdminAction::log(
            AdminActionType::LOT_CARTES,
            null,
            "Lot {$lot} — {$eligibles->count()} carte(s) envoyées en production"
        );

        return back()->with('success', __('admin.flash.lot_cree', ['lot' => $lot, 'compte' => $eligibles->count()]));
    }

    /** Fait avancer tout un lot d'un état au suivant. */
    public function avancer(Request $request): RedirectResponse
    {
        $valide = $request->validate([
            'batch_id' => ['required', 'string', 'max:40'],
            'statut' => ['required', 'string', 'in:'.implode(',', array_keys(CardOrder::STATUTS))],
        ]);

        // Chaque passage porte SA date : c'est le délai, pas l'état, qui fait
        // écrire un client.
        $horodatage = match ($valide['statut']) {
            CardOrder::STATUS_PRODUCED => ['produced_at' => now()],
            CardOrder::STATUS_SHIPPED => ['shipped_at' => now()],
            CardOrder::STATUS_DELIVERED => ['delivered_at' => now()],
            default => [],
        };

        $touchees = CardOrder::where('batch_id', $valide['batch_id'])
            ->update(array_merge(['status' => $valide['statut']], $horodatage));

        AdminAction::log(
            AdminActionType::LOT_CARTES,
            null,
            "Lot {$valide['batch_id']} → ".(CardOrder::STATUTS[$valide['statut']] ?? $valide['statut'])
        );

        return back()->with('success', __('admin.flash.cartes_mises_a_jour', ['compte' => $touchees]));
    }

    /**
     * L'EXPORT POUR L'IMPRIMEUR.
     *
     * Une colonne par information nécessaire à l'étiquette, dans l'ordre où on
     * la recopie. Le fichier est destiné à un humain devant une machine, pas à
     * un système : d'où les libellés en clair plutôt que des codes.
     */
    public function export(Request $request): StreamedResponse
    {
        return CsvExport::stream(
            CsvExport::nom('cartes-a-produire'),
            ['Commande', 'Lot', 'Destinataire', 'Téléphone', 'Adresse', 'Ville', 'Région', 'Indications', 'Slug', 'Commandée le'],
            $this->requete($request)->orderBy('card_orders.id'),
            fn (CardOrder $commande) => [
                $commande->id,
                $commande->batch_id ?? '',
                $commande->recipient_name ?? '',
                $commande->phone ?? '',
                $commande->address_line ?? '',
                $commande->city ?? '',
                $commande->region ?? '',
                $commande->delivery_notes ?? '',
                $commande->profile?->slug ?? '',
                $commande->created_at?->format('d/m/Y') ?? '',
            ]
        );
    }

    /** @return array<string,int> */
    private function compteurs(): array
    {
        $lignes = CardOrder::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        $compteurs = ['tous' => (int) $lignes->sum()];

        foreach (array_keys(CardOrder::STATUTS) as $statut) {
            $compteurs[$statut] = (int) ($lignes[$statut] ?? 0);
        }

        /*
         | L'ANCIENNETÉ DE LA PLUS VIEILLE COMMANDE EN ATTENTE.
         |
         | C'est CE chiffre qui dit si la promesse de délai tient, pas le
         | nombre de commandes. Vingt commandes d'hier ne posent aucun
         | problème ; une seule qui attend depuis six semaines en pose un.
         */
        $plusAncienne = CardOrder::enAttente()->min('created_at');

        $compteurs['plus_ancienne'] = $plusAncienne
            ? (int) now()->diffInDays($plusAncienne)
            : 0;

        return $compteurs;
    }

    /**
     * LE SUIVI ÉCONOMIQUE, RÉDUIT À CE QUI DÉCIDE.
     *
     * Le coût des cartes produites et le revenu encaissé donnent la marge. Mais
     * le chiffre qui dira si le modèle tient est le TAUX DE RENOUVELLEMENT :
     * une carte offerte ne se rentabilise qu'à partir du SECOND paiement. Un
     * client qui paie une fois, reçoit sa carte et ne revient pas coûte de
     * l'argent.
     *
     * @return array<string, int|float>
     */
    private function economie(): array
    {
        $produites = CardOrder::whereNotNull('produced_at')->count();
        $cout = $produites * (int) config('cartes.cout_unitaire_fcfa');

        $revenu = (int) Payment::where('status', Payment::STATUS_SUCCESS)->sum('amount_fcfa');

        // Un client qui a payé au moins deux fois a renouvelé.
        $paiementsParClient = Payment::where('status', Payment::STATUS_SUCCESS)
            ->selectRaw('user_id, COUNT(*) as n')
            ->groupBy('user_id')
            ->pluck('n', 'user_id');

        $payants = $paiementsParClient->count();
        $renouveles = $paiementsParClient->filter(fn ($n) => $n >= 2)->count();

        return [
            'cartes_produites' => $produites,
            'cout_cartes' => $cout,
            'revenu' => $revenu,
            'marge' => $revenu - $cout,
            'clients_payants' => $payants,
            'taux_renouvellement' => $payants > 0 ? round($renouveles * 100 / $payants, 1) : 0.0,
        ];
    }

    private function requete(Request $request): Builder
    {
        return CardOrder::query()
            ->with(['user:id,name,email', 'profile:id,slug'])
            ->when($request->query('statut'), fn (Builder $q, string $statut) => $q->where('status', $statut))
            ->when($request->query('lot'), fn (Builder $q, string $lot) => $q->where('batch_id', $lot))
            // Les plus anciennes d'abord : ce sont elles qui font écrire.
            ->orderBy('card_orders.created_at');
    }
}
