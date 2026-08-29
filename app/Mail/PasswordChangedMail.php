<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Alerte de sécurité : le mot de passe vient d'être modifié.
 *
 * IL S'ADRESSE À CELUI QUI N'A RIEN FAIT. Le titulaire légitime le survole et
 * le supprime ; sa raison d'être est l'autre cas, où ce message est le seul
 * signal avant la perte du compte. Toute sa rédaction en découle : l'action
 * de secours doit être visible en une seconde, sans faire défiler.
 *
 * Le bouton est SOMBRE et non vert. Le vert de la marque dit « tout va
 * bien » ; ce message n'en sait rien.
 *
 * L'IP est affichée telle quelle, jamais géolocalisée : derrière un opérateur
 * mobile sénégalais, une ville serait fausse une fois sur deux et détournerait
 * l'attention du seul fait qui compte.
 */
class PasswordChangedMail extends BaseMailable
{
    public function __construct(
        public string $name,
        public string $date,
        public ?string $ip,
        public string $resetUrl,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.mot_de_passe_change.sujet'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.password-changed',
            text: 'emails.client.password-changed_text',
        );
    }
}
