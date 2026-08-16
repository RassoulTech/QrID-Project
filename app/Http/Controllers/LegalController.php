<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Pages légales — OBLIGATOIRES avant toute vente.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES PAGES SONT, ET CE QU'ELLES NE SONT PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Elles portent désormais l'identité réelle de l'éditeur — RCCM, NINEA,
 * adresse, responsable de publication — tirée de config/legal.php, elle-même
 * alimentée par les documents d'immatriculation.
 *
 * Ce sont des textes COMPLETS et non plus une trame à trous. Ils couvrent ce
 * qu'exigent la loi n° 2008-08 sur les transactions électroniques et la loi
 * n° 2008-12 sur la protection des données à caractère personnel.
 *
 * ILS N'ONT PAS ÉTÉ RELUS PAR UN JURISTE. C'est une rédaction technique et
 * honnête, pas un avis juridique. Une relecture reste nécessaire avant
 * l'ouverture commerciale — mais l'absence de relecture ne justifiait pas de
 * laisser des pages vides, qui exposent bien davantage.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * PLUS DE BLOC « À COMPLÉTER » SUR LA PAGE PUBLIQUE
 * ═══════════════════════════════════════════════════════════════════════
 * Il s'adressait à nous et se lisait par les clients. Sur une page qui
 * engage, un aveu d'inachèvement détruit la confiance qu'elle est censée
 * établir. Ce qui reste à faire vit dans le plan de lancement, pas sous les
 * yeux de qui vient vérifier à qui il achète.
 */
class LegalController extends Controller
{
    public function conditions(): View
    {
        $editeur = config('legal.editeur');

        return view('legal.page', [
            'title' => 'Conditions générales d\'utilisation et de vente',
            'updatedAt' => config('legal.mise_a_jour'),
            'blocks' => [
                ['heading' => 'Objet',
                    'text' => 'Les présentes conditions régissent l\'accès et l\'utilisation de '.config('app.name')
                        .", plateforme d'identité professionnelle numérique éditée par {$editeur['denomination']}, "
                        ."{$editeur['forme']} immatriculée au RCCM sous le numéro {$editeur['rccm']} et identifiée "
                        ."sous le NINEA {$editeur['ninea']}, dont l'établissement est situé {$editeur['adresse']}. "
                        .'Toute création de compte vaut acceptation sans réserve des présentes.'],

                ['heading' => 'Description du service',
                    'text' => config('app.name').' permet de créer une carte de visite numérique accessible par un '
                        .'lien public et par QR Code. Le service comprend la création du profil, la génération du '
                        .'QR Code, le fichier prêt pour l\'impression, et les statistiques de consultation. '
                        .'L\'impression physique des cartes ne fait pas partie du service et fait l\'objet, le cas '
                        .'échéant, d\'une commande distincte.'],

                ['heading' => 'Accès au compte',
                    'text' => 'L\'accès nécessite la création d\'un compte avec une adresse e-mail valide, vérifiée '
                        .'par un lien de confirmation. Chaque compte est personnel et strictement nominatif. Le '
                        .'titulaire est seul responsable de la confidentialité de ses identifiants et de toute '
                        .'activité effectuée depuis son compte. Toute utilisation frauduleuse doit être signalée '
                        .'sans délai.'],

                ['heading' => 'Essai gratuit',
                    'text' => 'Tout nouveau compte bénéficie d\'une période d\'essai de quinze (15) jours, sans '
                        .'paiement et sans qu\'aucun moyen de paiement ne soit demandé. Pendant cette période, la '
                        .'carte est pleinement fonctionnelle et publiable. À son terme, la carte cesse d\'être '
                        .'consultable publiquement en l\'absence d\'abonnement, sans qu\'aucune donnée ne soit '
                        .'supprimée.'],

                ['heading' => 'Prix et paiement',
                    'text' => 'Les prix sont indiqués en francs CFA (XOF), toutes taxes comprises. Le règlement '
                        .'s\'effectue par Wave, Orange Money ou Free Money. Le montant appliqué est celui affiché '
                        .'sur la page de paiement au moment de la commande. L\'abonnement est sans engagement de '
                        .'durée et ne fait l\'objet d\'aucune reconduction automatique : chaque période doit être '
                        .'réglée par une action volontaire du client.'],

                ['heading' => 'Absence de droit de rétractation',
                    'text' => 'Le service est un contenu numérique fourni immédiatement après paiement. '
                        .'Conformément aux usages applicables aux services numériques exécutés sans délai, le '
                        .'client renonce expressément à tout droit de rétractation dès la mise en ligne de sa '
                        .'carte. La période d\'essai gratuite de quinze jours a précisément pour objet de permettre '
                        .'une évaluation complète avant tout paiement.'],

                ['heading' => 'Durée et résiliation',
                    'text' => 'L\'abonnement court pour la durée réglée. Le client peut cesser de renouveler à tout '
                        .'moment, sans démarche ni justification : l\'absence de renouvellement met fin à l\'accès '
                        .'public à l\'échéance. Le compte et les données demeurent accessibles au client. '
                        .'L\'éditeur peut suspendre un compte en cas de manquement caractérisé aux présentes, après '
                        .'information du titulaire lorsque les circonstances le permettent.'],

                ['heading' => 'Obligations du client',
                    'text' => 'Le client garantit l\'exactitude des informations publiées sur sa carte et détenir '
                        .'les droits sur tout contenu qu\'il y dépose, notamment sa photographie. Sont interdits : '
                        .'l\'usurpation d\'identité, la publication de contenus illicites, diffamatoires ou '
                        .'contraires à l\'ordre public, ainsi que tout usage du service à des fins de démarchage '
                        .'non sollicité.'],

                ['heading' => 'Disponibilité et responsabilité',
                    'text' => 'L\'éditeur met en œuvre les moyens raisonnables pour assurer la disponibilité du '
                        .'service, sans garantie d\'un fonctionnement ininterrompu. Sa responsabilité ne saurait '
                        .'être engagée en cas d\'indisponibilité imputable au réseau, à l\'hébergeur ou à un cas de '
                        .'force majeure. En tout état de cause, la responsabilité de l\'éditeur est limitée aux '
                        .'sommes effectivement versées par le client au titre des douze derniers mois.'],

                ['heading' => 'Propriété intellectuelle',
                    'text' => 'La plateforme, sa marque, ses interfaces et ses composants demeurent la propriété '
                        .'exclusive de l\'éditeur. Les contenus publiés par le client restent sa propriété pleine '
                        .'et entière ; il concède à l\'éditeur la seule licence nécessaire à leur affichage sur sa '
                        .'carte publique, pour la durée du service.'],

                ['heading' => 'Données personnelles',
                    'text' => 'Le traitement des données est décrit dans la politique de confidentialité, qui fait '
                        .'partie intégrante des présentes conditions.'],

                ['heading' => 'Modification des conditions',
                    'text' => 'L\'éditeur peut modifier les présentes conditions. Les clients disposant d\'un '
                        .'abonnement en cours sont informés par e-mail de toute modification substantielle. La '
                        .'poursuite de l\'utilisation après information vaut acceptation.'],

                ['heading' => 'Droit applicable et différends',
                    'text' => 'Les présentes conditions sont soumises au droit sénégalais. En cas de différend, les '
                        .'parties s\'efforcent de trouver une solution amiable ; à défaut, compétence est attribuée '
                        ."aux tribunaux de {$editeur['ville']}."],
            ],
        ]);
    }

    public function confidentialite(): View
    {
        $editeur = config('legal.editeur');

        return view('legal.page', [
            'title' => 'Politique de confidentialité',
            'updatedAt' => config('legal.mise_a_jour'),
            'blocks' => [
                ['heading' => 'Responsable du traitement',
                    'text' => "{$editeur['denomination']}, {$editeur['adresse']}, "
                        ."NINEA {$editeur['ninea']}. Toute demande relative à vos données peut être adressée à "
                        .config('mail.from.address').'.'],

                ['heading' => 'Données que nous collectons',
                    'text' => 'Pour votre COMPTE : nom, adresse e-mail, numéro de téléphone, et — si vous choisissez '
                        .'ce mode de connexion — l\'identifiant de votre compte Google. Pour votre CARTE : les '
                        .'informations professionnelles que vous décidez de publier, votre photographie si vous en '
                        .'ajoutez une. Pour votre ABONNEMENT : le montant, le moyen et la référence de vos '
                        .'paiements.'],

                ['heading' => 'Ce que nous ne collectons pas',
                    'text' => 'Nous ne demandons ni ne conservons aucune coordonnée bancaire : les paiements sont '
                        .'traités par l\'opérateur que vous choisissez, et nous n\'en recevons que la confirmation '
                        .'et la référence. Nous n\'utilisons aucun traceur publicitaire et ne pratiquons aucun '
                        .'profilage à des fins de prospection.'],

                ['heading' => 'Pourquoi nous les traitons',
                    'text' => 'Faire fonctionner votre carte publique, gérer votre compte et votre abonnement, vous '
                        .'informer des événements qui vous concernent (confirmation, paiement, échéance), et '
                        .'répondre à vos demandes. Ces traitements sont nécessaires à l\'exécution du contrat qui '
                        .'nous lie.'],

                ['heading' => 'Statistiques de consultation',
                    'text' => 'Les consultations de votre carte sont comptabilisées afin de vous restituer une '
                        .'mesure d\'audience. Les adresses IP ne sont JAMAIS conservées en clair : seule une '
                        .'empreinte irréversible est enregistrée, qui permet de distinguer deux visites sans '
                        .'permettre d\'identifier un visiteur.'],

                ['heading' => 'Qui a accès à vos données',
                    'text' => 'Vos informations de carte sont PUBLIQUES par nature : c\'est l\'objet du service, et '
                        .'vous en décidez le contenu. Vos données de compte ne sont accessibles qu\'à vous et à '
                        .'l\'équipe de la plateforme. Elles ne sont ni vendues, ni louées, ni transmises à des '
                        .'tiers à des fins commerciales.'],

                ['heading' => 'Sous-traitants et hébergement',
                    'text' => 'Le service est hébergé par '.config('legal.hebergeur.nom').'. L\'envoi des e-mails est '
                        .'assuré par un prestataire technique. Ces intervenants agissent sur nos instructions et '
                        .'n\'utilisent vos données pour aucune finalité propre. Certains d\'entre eux étant situés '
                        .'hors du Sénégal, vos données peuvent être hébergées à l\'étranger.'],

                ['heading' => 'Durée de conservation',
                    'text' => 'Vos données de compte et de carte sont conservées tant que votre compte existe. Les '
                        .'informations relatives aux paiements sont conservées au-delà, en tant que pièces '
                        .'comptables, pour la durée légale applicable.'],

                ['heading' => 'Vos droits',
                    'text' => 'Conformément à la loi n° 2008-12 du 25 janvier 2008 relative à la protection des '
                        .'données à caractère personnel, vous disposez d\'un droit d\'accès, de rectification, '
                        .'d\'opposition et de suppression. La plupart de ces actions sont réalisables directement '
                        .'depuis votre espace client. Pour les autres, écrivez à '.config('mail.from.address')
                        .'. Vous pouvez également saisir la Commission de protection des données personnelles (CDP).'],

                ['heading' => 'Supprimer votre compte',
                    'text' => 'La suppression est possible à tout moment depuis vos paramètres. Elle entraîne la '
                        .'disparition de votre carte, de son lien public et de son QR Code. Les pièces comptables '
                        .'liées à d\'éventuels paiements sont conservées, comme la loi l\'impose.'],

                ['heading' => 'Sécurité',
                    'text' => 'Les mots de passe sont stockés sous forme chiffrée et irréversible. Les échanges '
                        .'entre votre navigateur et la plateforme sont chiffrés. Toute modification de mot de passe '
                        .'déclenche une alerte de sécurité par e-mail, qui ne peut pas être désactivée.'],
            ],
        ]);
    }

    public function mentions(): View
    {
        $editeur = config('legal.editeur');
        $hebergeur = config('legal.hebergeur');

        return view('legal.page', [
            'title' => 'Mentions légales',
            'updatedAt' => config('legal.mise_a_jour'),
            'blocks' => [
                ['heading' => 'Éditeur du site',
                    'text' => "{$editeur['denomination']} — {$editeur['forme']}. "
                        ."Établissement : {$editeur['adresse']}. "
                        ."Registre du commerce et du crédit mobilier (RCCM) : {$editeur['rccm']}. "
                        ."Numéro d'identification nationale des entreprises et associations (NINEA) : {$editeur['ninea']}."],

                ['heading' => 'Responsable de la publication',
                    'text' => "{$editeur['responsable']}, {$editeur['qualite']}."],

                ['heading' => 'Contact',
                    'text' => 'Par e-mail : '.config('mail.from.address').'. '
                        ."Par téléphone et sur WhatsApp : {$editeur['telephone']}. "
                        .'Un formulaire de contact est également disponible sur la page d\'accueil.'],

                ['heading' => 'Hébergement',
                    'text' => "{$hebergeur['nom']} — {$hebergeur['adresse']} ({$hebergeur['site']})."],

                ['heading' => 'Propriété intellectuelle',
                    'text' => 'La structure du site, ses interfaces, sa marque et ses éléments graphiques sont '
                        .'protégés. Toute reproduction, même partielle, est interdite sans autorisation écrite. Les '
                        .'contenus publiés par les utilisateurs sur leur carte demeurent leur propriété.'],

                ['heading' => 'Données personnelles',
                    'text' => 'Le traitement des données personnelles est décrit dans la politique de '
                        .'confidentialité, accessible depuis le pied de page de chaque écran.'],

                ['heading' => 'Signalement',
                    'text' => 'Toute carte dont le contenu paraîtrait illicite, trompeur ou constitutif d\'une '
                        .'usurpation d\'identité peut être signalée à '.config('mail.from.address')
                        .'. Chaque signalement est examiné, et la carte concernée peut être retirée sans préavis.'],
            ],
        ]);
    }
}
