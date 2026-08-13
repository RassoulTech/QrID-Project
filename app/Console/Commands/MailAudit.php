<?php

namespace App\Console\Commands;

use App\Enums\MotifAlerte;
use App\Mail\AdminAlertMail;
use App\Mail\AlreadyRegisteredMail;
use App\Mail\BaseMailable;
use App\Mail\ConfirmRegistrationMail;
use App\Mail\ContactMail;
use App\Mail\PasswordChangedMail;
use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSucceededMail;
use App\Mail\ProfilePublishedMail;
use App\Mail\ProfileReminderMail;
use App\Mail\ResetPasswordMail;
use App\Mail\SubscriptionExpiredMail;
use App\Mail\SubscriptionExpiringMail;
use App\Mail\WelcomeMail;
use App\Models\ContactMessage;
use App\Support\DestinatairesEquipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * AUDIT COMPLET DE LA CHAÎNE D'ENVOI.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'IL PROUVE, ET CE QU'AUCUN TEST NE PROUVE
 * ═══════════════════════════════════════════════════════════════════════
 * La suite de tests utilise Mail::fake() : elle constate qu'un message a été
 * DEMANDÉ, jamais qu'il quitte l'application. Entre les deux se trouvent le
 * transport, les clés d'API, les limites du fournisseur, la résolution des
 * destinataires — c'est-à-dire tout ce qui a réellement échoué sur ce projet.
 *
 * Cette commande envoie POUR DE VRAI, un message à la fois, et dit lequel
 * part. Elle répond à la seule question qui compte avant une ouverture
 * commerciale : « est-ce que mes clients recevront leurs e-mails ? »
 *
 * ═══════════════════════════════════════════════════════════════════════
 * UN SEUL DESTINATAIRE, LE VÔTRE
 * ═══════════════════════════════════════════════════════════════════════
 * Tous les messages partent vers l'adresse passée en argument, y compris ceux
 * destinés à l'équipe. C'est délibéré : l'audit vérifie le TRANSPORT, pas
 * l'aiguillage. L'aiguillage, lui, est affiché à part et vérifié par les
 * tests.
 *
 *     php artisan mail:audit vous@exemple.sn
 *     php artisan mail:audit vous@exemple.sn --dry-run
 *
 * ⚠ CHAQUE EXÉCUTION ENVOIE UNE DIZAINE D'E-MAILS RÉELS. Les fournisseurs
 * gratuits limitent le volume quotidien : à utiliser pour valider une
 * configuration, pas en boucle.
 */
class MailAudit extends Command
{
    protected $signature = 'mail:audit
                            {email : L\'adresse qui recevra tous les messages}
                            {--dry-run : Fabrique les messages sans les envoyer}';

    protected $description = 'Envoie chaque e-mail du produit et dit lequel part réellement.';

    public function handle(): int
    {
        $destinataire = (string) $this->argument('email');
        $simulation = (bool) $this->option('dry-run');

        if (! filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse invalide : '.$destinataire);

            return self::FAILURE;
        }

        $this->contexte($destinataire, $simulation);

        if (config('mail.default') === 'log' && ! $simulation) {
            $this->error('Transport « log » : rien ne partira réellement.');
            $this->line('Corriger MAIL_MAILER dans .env puis : php artisan config:clear');

            return self::FAILURE;
        }

        $resultats = [];
        $echecs = 0;

        foreach ($this->messages() as $nom => $message) {
            [$etat, $detail, $ms] = $this->essayer($destinataire, $message, $simulation);

            if ($etat === 'ÉCHEC') {
                $echecs++;
            }

            $resultats[] = [$nom, $etat, $ms.' ms', $detail];
        }

        $this->newLine();
        $this->table(['E-mail', 'État', 'Durée', 'Détail'], $resultats);

        return $this->conclure($echecs, count($resultats), $simulation);
    }

    // -----------------------------------------------------------------------

    /**
     * TOUS les e-mails du produit, avec des données réalistes.
     *
     * La liste est écrite à la main, et c'est voulu : une découverte
     * automatique des classes du dossier Mail passerait sans bruit à côté d'un
     * message dont le constructeur a changé — or c'est précisément ce qu'on
     * veut voir échouer ici.
     *
     * @return array<string, BaseMailable>
     */
    private function messages(): array
    {
        $url = rtrim((string) config('app.url'), '/');

        $contact = new ContactMessage([
            'name' => 'Awa Ndiaye',
            'email' => 'awa@exemple.sn',
            'phone' => '+221 77 383 13 64',
            'subject' => 'commande',
            'message' => "Bonjour,\n\nJe souhaite commander cinquante cartes imprimées pour mon cabinet.",
        ]);
        $contact->created_at = now();

        return [
            // --- Parcours d'inscription -------------------------------------
            'Confirmation d\'inscription' => new ConfirmRegistrationMail(
                'Awa Ndiaye', $url.'/inscription/confirmer/jeton-de-controle', 60
            ),
            'Adresse déjà utilisée' => new AlreadyRegisteredMail(
                $url.'/login', $url.'/forgot-password'
            ),
            'Bienvenue' => new WelcomeMail(
                'Awa Ndiaye', $url.'/profil/creation/etape-1', 15, '28 août 2026'
            ),

            // --- Mot de passe -----------------------------------------------
            'Réinitialisation du mot de passe' => new ResetPasswordMail(
                $url.'/reset-password/jeton-de-controle', 60, 'awa@exemple.sn'
            ),
            'Mot de passe modifié' => new PasswordChangedMail(
                'Awa Ndiaye', now()->translatedFormat('j F Y à H:i'), '41.82.10.5', $url.'/forgot-password'
            ),

            // --- Carte -------------------------------------------------------
            'Rappel de publication (24 h)' => new ProfileReminderMail(
                'Awa Ndiaye', $url.'/profil/apercu', 1
            ),
            'Rappel de publication (72 h)' => new ProfileReminderMail(
                'Awa Ndiaye', $url.'/profil/apercu', 2
            ),
            'Carte en ligne' => new ProfilePublishedMail(
                'Awa Ndiaye', $url.'/p/awa-ndiaye', $url.'/dashboard'
            ),

            // --- Paiement ----------------------------------------------------
            // Sans pièces jointes : l'audit contrôle le transport, et un PDF
            // de 300 Ko multiplié par le nombre d'exécutions userait le quota
            // du fournisseur sans rien prouver de plus.
            'Paiement encaissé' => new PaymentSucceededMail(
                name: 'Awa Ndiaye',
                reference: 'AUDIT-'.now()->format('YmdHis'),
                montant: '2 500',
                moyen: 'Wave',
                formule: 'Mensuel',
                date: now()->translatedFormat('j F Y à H:i'),
                echeance: now()->addDays(30)->translatedFormat('j F Y'),
                publicUrl: $url.'/p/awa-ndiaye',
                dashboardUrl: $url.'/dashboard',
            ),
            'Paiement non abouti' => new PaymentFailedMail(
                'Awa Ndiaye', '2 500', 'Mensuel', $url.'/abonnement/paiement'
            ),

            // --- Abonnement ---------------------------------------------------
            'Échéance J-7' => new SubscriptionExpiringMail(
                'Awa Ndiaye', 7, now()->addDays(7)->translatedFormat('j F Y'), 'Mensuel', $url.'/abonnement/paiement'
            ),
            'Échéance jour même' => new SubscriptionExpiringMail(
                'Awa Ndiaye', 0, now()->translatedFormat('j F Y'), 'Mensuel', $url.'/abonnement/paiement'
            ),
            'Abonnement expiré' => new SubscriptionExpiredMail(
                'Awa Ndiaye', now()->subDay()->translatedFormat('j F Y'), $url.'/abonnement/paiement', $url.'/p/awa-ndiaye'
            ),

            // --- Équipe --------------------------------------------------------
            'Alerte équipe (information)' => new AdminAlertMail(
                MotifAlerte::CompteConfirme,
                ['Client' => 'Awa Ndiaye', 'Adresse' => 'awa@exemple.sn'],
                $url.'/admin/clients',
            ),
            'Alerte équipe (action)' => new AdminAlertMail(
                MotifAlerte::PaiementEchoue,
                ['Client' => 'Awa Ndiaye', 'Montant' => '2 500 FCFA', 'Raison' => 'contrôle d\'audit'],
                $url.'/admin/paiements',
            ),
            'Message du formulaire de contact' => new ContactMail($contact),
        ];
    }

    /**
     * Un envoi, chronométré, sans qu'aucune exception ne remonte.
     *
     * @return array{0:string, 1:string, 2:int}
     */
    private function essayer(string $destinataire, BaseMailable $message, bool $simulation): array
    {
        $depart = microtime(true);

        try {
            if ($simulation) {
                // Le rendu SEUL suffit à faire tomber un gabarit cassé : c'est
                // ce que Mail::fake() ne fait jamais.
                $message->render();
            } else {
                Mail::to($destinataire)->send($message);
            }

            return ['OK', '', (int) round((microtime(true) - $depart) * 1000)];
        } catch (Throwable $e) {
            return [
                'ÉCHEC',
                mb_substr($e->getMessage(), 0, 90),
                (int) round((microtime(true) - $depart) * 1000),
            ];
        }
    }

    /** Ce que l'audit va faire, avant de le faire. */
    private function contexte(string $destinataire, bool $simulation): void
    {
        $equipe = DestinatairesEquipe::alertes();

        $this->newLine();
        $this->line('<comment>Chaîne d\'envoi</comment>');

        $this->table(['Paramètre', 'Valeur'], [
            ['Transport', config('mail.default')],
            ['Expéditeur', config('mail.from.address')],
            ['Destinataire de l\'audit', $destinataire],
            ['File', config('queue.default')],
            ['Alertes équipe', $equipe === [] ? '— AUCUN DESTINATAIRE —' : implode(', ', $equipe)],
            ['Contact', implode(', ', DestinatairesEquipe::contact()) ?: '— AUCUN —'],
            ['Mode', $simulation ? 'simulation (aucun envoi)' : 'ENVOI RÉEL'],
        ]);

        if ($equipe === []) {
            $this->warn('Aucun destinataire pour les alertes d\'équipe : elles seront perdues.');
            $this->line('Renseigner ADMIN_ALERT_RECIPIENTS, ou créer un compte administrateur.');
        }

        if (! $simulation) {
            $this->newLine();
            $this->warn('Envoi réel en cours — une quinzaine de messages vont partir.');
        }
    }

    private function conclure(int $echecs, int $total, bool $simulation): int
    {
        $this->newLine();

        if ($echecs > 0) {
            $this->error("{$echecs} e-mail(s) sur {$total} n'ont pas pu être ".($simulation ? 'fabriqués' : 'envoyés').'.');
            $this->line('Le détail figure ci-dessus, et la trace complète dans : php artisan mail:history');

            return self::FAILURE;
        }

        if ($simulation) {
            $this->info("Les {$total} e-mails se fabriquent correctement. Relancer sans --dry-run pour éprouver le transport.");

            return self::SUCCESS;
        }

        $this->info("Les {$total} e-mails ont été acceptés par le fournisseur.");
        $this->newLine();
        $this->line('<comment>Ce que cela prouve</comment> : la chaîne fonctionne de bout en bout.');
        $this->line('<comment>Ce que cela ne prouve pas</comment> : la livraison finale, décidée par');
        $this->line('le serveur du destinataire. Vérifiez la boîte, et les indésirables.');

        return self::SUCCESS;
    }
}
