<?php

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * Contrat d'une passerelle de paiement mobile.
 *
 * Wave, Orange Money et Free Money fonctionnent tous les trois de la même
 * façon : on annonce un montant, l'opérateur renvoie une URL où l'utilisateur
 * confirme, puis il nous rappelle. Ce contrat fige ce déroulé pour que le
 * reste du produit n'ait jamais à savoir QUEL opérateur est branché.
 *
 * Une seule implémentation existe aujourd'hui — FakeGateway, qui permet de
 * dérouler tout le parcours sans un seul appel réseau. Le jour où un vrai
 * contrat opérateur est signé, il n'y a que cette interface à honorer :
 * aucun contrôleur, aucune vue, aucun test métier ne bouge.
 */
interface PaymentGateway
{
    /**
     * Ouvre une transaction chez l'opérateur.
     *
     * Le Payment est DÉJÀ créé en base, en statut « pending », avant tout
     * appel : si l'opérateur répond mal, ou pas du tout, la trace existe et
     * la somme réclamée est vérifiable.
     *
     * @return string L'URL vers laquelle envoyer l'utilisateur.
     */
    public function initiate(Payment $payment): string;

    /**
     * Le retour de l'opérateur confirme-t-il l'encaissement ?
     *
     * @param  array<string, mixed>  $callback  Ce que l'opérateur nous renvoie.
     */
    public function confirms(Payment $payment, array $callback): bool;

    /** Nom affichable de la passerelle, pour la journalisation. */
    public function name(): string;

    /**
     * Cette passerelle peut-elle encaisser ICI, maintenant ?
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CETTE QUESTION EXISTE
     * ═══════════════════════════════════════════════════════════════════
     * Une passerelle peut être branchée dans le code et pourtant inutilisable
     * dans l'environnement courant — FakeGateway est interdite en production,
     * une vraie passerelle le sera tant que ses clés manquent.
     *
     * Sans cette question, le produit ne l'apprend qu'en TOMBANT : le client
     * choisit sa formule, clique « Payer », et reçoit une page 500 qui lui
     * dit « le problème vient de nous ». C'est faux, et c'est le pire écran
     * possible — celui qui suit le seul clic où il sortait son argent.
     *
     * La demander AVANT permet de le dire honnêtement, et d'éviter d'écrire
     * un Payment « pending » que personne ne confirmera jamais.
     */
    public function estDisponible(): bool;
}
