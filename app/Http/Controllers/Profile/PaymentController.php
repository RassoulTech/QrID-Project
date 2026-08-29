<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CheckoutRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\Payment\CheckoutService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Paiement de l'abonnement — quatre écrans, une seule règle.
 *
 * RIEN n'est accordé avant le retour de l'opérateur. Le Payment naît en
 * « pending », l'abonnement n'est ouvert qu'à la confirmation, et le profil
 * n'est publié que dans la même transaction que l'abonnement.
 *
 * L'ordre des gardes suit la matrice de redirections : pas de profil, pas de
 * paiement — on ne fait pas payer quelqu'un pour une carte qui n'existe pas.
 */
class PaymentController extends Controller
{
    public function __construct(private CheckoutService $checkout) {}

    /**
     * Choix de la formule et du moyen de paiement.
     *
     * Première souscription ou RENOUVELLEMENT : c'est le même écran, mais il
     * ne raconte pas la même chose. On ne propose pas « souscrire » à qui a
     * déjà un abonnement en cours.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->profile) {
            return redirect()->route('profile.create.step1')
                ->with('info', __('profile.flash.carte_avant_formule'));
        }

        $abonnement = $user->activeSubscription();

        return view('abonnement.paiement', [
            'plans' => Plan::active()->where('price_fcfa', '>', 0)->orderBy('price_fcfa')->get(),
            'methods' => Payment::METHODS,
            'subscription' => $abonnement,

            // Un essai gratuit en cours n'est pas un abonnement payé : celui
            // qui en dispose fait bien une PREMIÈRE souscription.
            'renewal' => $abonnement !== null && ! $abonnement->isTrial(),

            /*
             | AUCUNE PASSERELLE BRANCHÉE ? ON LE DIT AVANT, PAS APRÈS.
             |
             | Tant qu'aucun contrat opérateur n'est signé, l'écran affichait
             | un formulaire complet qui menait à une page 500 — « le problème
             | vient de nous, notre équipe en a été informée ». C'était faux
             | deux fois : rien n'est en panne, et personne n'était informé.
             |
             | Le client sortait son argent et recevait une erreur serveur.
             */
            'paiementDisponible' => $this->checkout->estDisponible(),
            'supportWhatsapp' => config('registration.support_whatsapp'),

            /*
             | LE RACCOURCI DE L'EXPLOITANT.
             |
             | Sans passerelle, l'encaissement se fait à la main : quelqu'un
             | écrit sur WhatsApp, un administrateur prolonge l'abonnement
             | depuis la fiche client. Or l'administrateur qui se heurte
             | LUI-MÊME à cet écran a déjà le pouvoir de le débloquer — il lui
             | manquait seulement le chemin, à trois écrans de là.
             |
             | Ce n'est pas un pouvoir nouveau : la prolongation existe, elle
             | exige un motif et reste journalisée. C'est un lien.
             */
            'ficheAdmin' => $user->isAdmin()
                ? route('admin.clients.show', $user)
                : null,
        ]);
    }

    /** Ouvre le paiement puis envoie l'utilisateur chez l'opérateur. */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->profile) {
            return redirect()->route('profile.create.step1');
        }

        /*
         | LA GARDE EST ICI, AVANT start(), ET C'EST TOUT L'INTÉRÊT.
         |
         | start() écrit un Payment « pending » puis initiate() lève. Chaque
         | clic laissait donc un paiement fantôme en base — et ce sont eux
         | qui alimentent l'alerte « en attente depuis plus d'une heure » du
         | récapitulatif du soir. L'absence de passerelle se serait signalée
         | comme une panne d'encaissement.
         */
        if (! $this->checkout->estDisponible()) {
            return back()->with(
                'warning',
                'Le paiement en ligne n\'est pas encore ouvert. Écrivez-nous sur WhatsApp : '
                .'nous activons votre carte à la main, dès réception.'
            );
        }

        $payment = $this->checkout->start($user, $request->plan(), $request->string('method')->value());

        return redirect()->away($this->checkout->redirectUrl($payment));
    }

    /**
     * Écran de simulation — DÉVELOPPEMENT UNIQUEMENT.
     *
     * Il tient la place de la page de l'opérateur et offre les trois issues
     * réelles : encaissement, refus, abandon. Sans lui, un parcours de
     * paiement ne serait testable qu'en supposant qu'il réussit toujours.
     */
    public function simulate(Request $request, Payment $payment): View
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        abort_unless($payment->user_id === $request->user()->id, 403);

        return view('abonnement.simulation', ['payment' => $payment]);
    }

    /**
     * Retour de l'opérateur.
     *
     * Le paiement appartient-il bien à la personne connectée ? Sinon un lien
     * de retour recopié activerait l'abonnement de quelqu'un d'autre.
     */
    public function callback(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $statut = (string) $request->query('statut', 'echec');

        if ($statut === 'annule') {
            $this->checkout->fail($payment, 'abandon de l\'utilisateur');

            return redirect()->route('abonnement.paiement')->with(
                'warning',
                'Paiement annulé. Rien n\'a été débité et vos informations sont intactes.'
            );
        }

        $confirme = $this->checkout->succeed($payment, [
            'statut' => $statut,
            'reference' => $request->query('reference'),
        ]);

        if (! $confirme) {
            return redirect()->route('abonnement.paiement')->with(
                'error',
                'Le paiement n\'a pas abouti. Aucune somme n\'a été débitée — vous pouvez réessayer.'
            );
        }

        // Écran de confirmation plutôt que retour sec au tableau de bord :
        // c'est le moment où l'on remet au client ce qu'il vient d'acheter —
        // sa carte, son lien, ses fichiers à imprimer.
        return redirect()->route('abonnement.confirmation');
    }

    /**
     * Confirmation — la carte est en ligne.
     *
     * Accessible uniquement à qui possède une carte publiée : y arriver sans
     * avoir payé n'aurait aucun sens, et l'écran promettrait des
     * téléchargements que la policy refuserait ensuite.
     */
    public function confirmation(Request $request, QrCodeService $qr): View|RedirectResponse
    {
        /*
         | Relecture en base, et non la relation déjà chargée : cet écran suit
         | IMMÉDIATEMENT l'écriture qui publie la carte et ouvre l'abonnement.
         | Un modèle mis en cache avant ce changement conclurait que la carte
         | n'est pas en ligne et renverrait le client au tableau de bord juste
         | après son paiement.
         */
        $profile = $request->user()->profile()->with('user.subscriptions')->first();

        if (! $profile || ! $profile->isPubliclyVisible()) {
            return redirect()->route('dashboard');
        }

        return view('abonnement.confirmation', [
            'profile' => $profile,
            'qrSvg' => $qr->svg($profile),
            'publicUrl' => route('profile.public', $profile->slug),
            'subscription' => $request->user()->activeSubscription(),
        ]);
    }
}
