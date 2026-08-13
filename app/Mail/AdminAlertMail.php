<?php

namespace App\Mail;

use App\Enums\MotifAlerte;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * L'alerte envoyée à l'équipe. UNE classe pour les SIX motifs du plan.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE SEULE CLASSE ET NON SIX
 * ═══════════════════════════════════════════════════════════════════════
 * Les six alertes demandées ont rigoureusement la même forme : un titre, une
 * poignée de couples libellé/valeur, un lien vers l'écran d'administration
 * concerné. Elles ne diffèrent que par leur contenu — ce qui est la définition
 * même d'un paramètre, pas d'une classe.
 *
 * Six classes auraient produit six gabarits presque identiques. Le jour où le
 * pied de page change, on en corrige cinq et l'on oublie le sixième ; le
 * défaut ne se voit que chez le destinataire, des semaines plus tard.
 *
 * Ce qui distingue vraiment les motifs — l'urgence — est porté par l'enum
 * MotifAlerte, en un seul endroit, et décide du sujet comme du bandeau.
 *
 * PAS DE DONNÉES CLIENT AU-DELÀ DU NÉCESSAIRE. Ces messages partent vers des
 * boîtes personnelles, hors de l'application : nom, adresse et montant
 * suffisent à agir. Téléphone et adresse postale restent dans l'espace
 * d'administration, derrière une authentification.
 */
class AdminAlertMail extends BaseMailable
{
    /**
     * @param  array<string, string>  $lignes  couples libellé => valeur
     */
    public function __construct(
        public MotifAlerte $motif,
        public array $lignes,
        public ?string $url = null,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        /*
         | Le préfixe sert au TRI. Une règle de boîte de réception sur
         | « [Action] » suffit à séparer ce qui appelle une intervention de ce
         | qui se lit le soir — sans quoi les six motifs se mélangent et le
         | canal cesse d'être ouvert.
         */
        $prefixe = $this->motif->estUrgent() ? '[Action] ' : '[Info] ';

        return new Envelope(
            subject: $prefixe.$this->motif->titre().' — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.alerte',
            text: 'emails.admin.alerte_text',
        );
    }
}
