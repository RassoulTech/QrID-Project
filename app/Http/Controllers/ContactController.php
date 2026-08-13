<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use App\Models\ContactMessage;
use App\Support\Courrier;
use App\Support\DestinatairesEquipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Le formulaire de contact de la page d'accueil.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ÉCRIRE D'ABORD, NOTIFIER ENSUITE — l'ordre est le point du fichier
 * ═══════════════════════════════════════════════════════════════════════
 * Le message est enregistré en base AVANT toute tentative d'envoi. Si l'envoi
 * échoue — et il a échoué trois jours durant sur ce projet, en production,
 * sans que rien ne le signale — le message reste.
 *
 * L'inverse perdrait un client qui a pris la peine d'écrire, qui n'aurait
 * aucune réponse, et qui n'aurait aucun moyen de savoir que sa demande s'est
 * évaporée. Il en conclurait que personne ne répond.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA CONFIRMATION EST LA MÊME DANS TOUS LES CAS
 * ═══════════════════════════════════════════════════════════════════════
 * Y compris quand l'alerte à l'équipe n'est pas partie. Ce n'est pas un
 * mensonge : le message EST reçu, il est en base, il sera lu. Faire porter à
 * l'expéditeur une panne de notification interne — dont il n'est pas
 * responsable et sur laquelle il ne peut rien — le pousserait à renvoyer
 * plusieurs fois le même message.
 *
 * L'échec, lui, est consigné dans mail_logs et visible sur l'écran « État
 * système ».
 */
class ContactController extends Controller
{
    public function store(ContactRequest $request): RedirectResponse
    {
        $donnees = $request->validated();

        // Le piège à robots n'a pas à voyager jusqu'en base.
        unset($donnees['site_web']);

        $message = ContactMessage::create($donnees + [
            'user_id' => $request->user()?->id,
        ]);

        $this->prevenirEquipe($message);

        /*
         | RETOUR À L'ANCRE DU FORMULAIRE.
         |
         | Sans elle, la confirmation s'affiche en bas d'une page d'accueil
         | longue, et l'utilisateur — replacé en haut — croit que rien ne s'est
         | passé. Il resoumet.
         */
        return redirect()
            ->to(route('home').'#contact')
            ->with('contact.envoye', true);
    }

    /**
     * Alerte l'équipe. NE PEUT PAS FAIRE ÉCHOUER L'ENREGISTREMENT.
     *
     * Courrier avale la panne et la consigne : le message est déjà en base au
     * moment où cette méthode est appelée, il n'y a donc rien à sauver et rien
     * à annuler.
     */
    private function prevenirEquipe(ContactMessage $message): void
    {
        $destinataires = $this->destinataires();

        if ($destinataires === []) {
            Log::channel('mail')->warning('Message de contact sans destinataire', [
                'contact_id' => $message->id,
            ]);

            return;
        }

        Courrier::informer($destinataires, new ContactMail(
            contact: $message,
            recipient: implode(', ', $destinataires),
        ));
    }

    /**
     * Résolue par DestinatairesEquipe, et non ici.
     *
     * Cette méthode dupliquait la règle d'AdminNotifier avec un ordre de
     * priorité différent, et surtout SANS écarter les adresses de
     * démonstration — ce qui faisait rejeter le message entier par le
     * fournisseur dès qu'un compte fictif figurait parmi les administrateurs.
     *
     * @return array<int, string>
     */
    private function destinataires(): array
    {
        return DestinatairesEquipe::contact();
    }
}
