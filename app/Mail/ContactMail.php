<?php

namespace App\Mail;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Le message du formulaire de contact, transmis à l'équipe.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * L'ADRESSE DE RÉPONSE EST CELLE DU CLIENT — c'est tout l'intérêt
 * ═══════════════════════════════════════════════════════════════════════
 * Sans `replyTo`, répondre exigerait de recopier l'adresse à la main depuis
 * le corps du message. Avec, il suffit d'appuyer sur « Répondre » : la
 * réponse part au bon endroit, dans le fil, comme n'importe quel échange.
 *
 * L'EXPÉDITEUR RESTE LE NÔTRE, et ne devient jamais celui du client. Écrire
 * un e-mail au nom d'une adresse qu'on ne possède pas est précisément ce que
 * SPF et DKIM existent pour empêcher : le message finirait en indésirables,
 * quand il ne serait pas rejeté.
 *
 * LE SUJET PORTE LE MOTIF, en clair. Une boîte de support se trie sur les
 * sujets ; « Nouveau message » cinquante fois ne se trie pas.
 */
class ContactMail extends BaseMailable
{
    public function __construct(
        public ContactMessage $contact,
        string $recipient = '',
    ) {
        $this->recipient = $recipient;
    }

    /**
     * Le libellé lisible du motif, pour le sujet comme pour le corps.
     *
     * IL SUIT LA LANGUE DE L'ÉQUIPE, pas celle du visiteur. Ce message part
     * vers la boîte de support : c'est elle qui doit pouvoir le trier, et un
     * sujet qui change de langue selon l'expéditeur rend ce tri impossible.
     * Le corps, lui, reprend le message tel qu'il a été écrit.
     */
    public function motif(): string
    {
        return in_array($this->contact->subject, ContactRequest::SUJETS, true)
            ? __('landing.contact.motifs.'.$this->contact->subject, [], config('app.locale'))
            : __('common.champs.message', [], config('app.locale'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Contact] '.$this->motif().' — '.$this->contact->name,
            replyTo: [new Address($this->contact->email, $this->contact->name)],
        );
    }

    public function content(): Content
    {
        /*
         | LE MOTIF EST PASSÉ EXPLICITEMENT PAR `with`.
         |
         | Laravel ne transmet à la vue que les PROPRIÉTÉS PUBLIQUES du
         | Mailable, jamais ses méthodes. Le gabarit appelait `$motif` en
         | comptant sur motif() : la variable n'existait pas, le rendu levait
         | « Undefined variable $motif », et le message de contact n'est jamais
         | parti.
         |
         | Le défaut était INVISIBLE aux tests : Mail::fake() intercepte avant
         | le rendu. Il n'apparaissait qu'à l'envoi réel — c'est-à-dire chez le
         | client, sur un message qu'on ne voit pas manquer.
         */
        return new Content(
            view: 'emails.admin.contact',
            text: 'emails.admin.contact_text',
            with: ['motif' => $this->motif()],
        );
    }
}
