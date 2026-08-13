<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * LE MESSAGE D'AIDE PRÉ-REMPLI, DÉDUIT DE LA PAGE OÙ L'ON SE TROUVE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI PAS UN MESSAGE UNIQUE
 * ═══════════════════════════════════════════════════════════════════════
 * « Bonjour, j'ai une question » oblige la personne à tout écrire — au moment
 * précis où elle est bloquée, souvent sur un téléphone, souvent agacée. La
 * moitié renonce, et celle qui écrit produit un message que l'équipe devra
 * faire préciser par un second échange.
 *
 * Un message qui NOMME l'écran fait deux choses d'un coup : il épargne la
 * saisie, et il dit à l'équipe où la personne était. « Je bloque à l'étape 2
 * de la création de ma carte » se traite immédiatement ; « j'ai un problème »
 * demande trois allers-retours.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUI EST ÉCRIT ICI, ET CE QUI NE L'EST PAS
 * ═══════════════════════════════════════════════════════════════════════
 * Le message décrit L'ENDROIT, jamais le problème. « Je n'arrive pas à
 * publier ma carte » suppose une panne qui n'existe peut-être pas et met la
 * personne en position de plainte avant même d'avoir parlé. On écrit donc
 * « je suis sur l'écran d'aperçu de ma carte et j'ai besoin d'aide » : c'est
 * vrai dans tous les cas, et cela laisse la phrase suivante ouverte.
 *
 * AUCUNE DONNÉE PERSONNELLE n'est glissée dans le message — ni nom, ni
 * adresse, ni identifiant. Le texte part dans WhatsApp, hors de
 * l'application, et se retrouve dans une URL que le navigateur enregistre
 * dans son historique. Une phrase suffit ; l'équipe reconnaîtra la personne à
 * son numéro.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE REPLI EST DÉLIBÉRÉMENT NEUTRE
 * ═══════════════════════════════════════════════════════════════════════
 * Une page non listée — il en existera toujours — donne un message général
 * plutôt qu'un message faux. Nommer un écran où l'on n'est pas est pire que
 * de n'en nommer aucun.
 */
final class AideContextuelle
{
    /**
     * Route → phrase, sans le « Bonjour, » ni le nom du produit, ajoutés
     * ensuite. Les entrées sont groupées par parcours pour qu'une page
     * nouvelle se range à l'évidence.
     *
     * @var array<string, string>
     */
    private const MESSAGES = [
        // --- Découverte -----------------------------------------------------
        'home' => 'je découvre %s et j\'aimerais en savoir plus',
        'profile.demo' => 'je viens de voir un exemple de carte %s et j\'ai une question',
        'legal.conditions' => 'j\'ai une question sur les conditions générales de %s',
        'legal.confidentialite' => 'j\'ai une question sur la confidentialité chez %s',
        'legal.mentions' => 'j\'ai une question sur les mentions légales de %s',

        // --- Accès au compte ------------------------------------------------
        // Ce sont les pages où l'aide compte le plus : quelqu'un qui ne peut
        // pas entrer n'a aucun autre canal pour le dire.
        'login' => 'je n\'arrive pas à me connecter à mon espace %s',
        'register' => 'j\'ai besoin d\'aide pour créer mon compte %s',
        'password.request' => 'je n\'arrive pas à réinitialiser mon mot de passe %s',
        'password.reset' => 'je n\'arrive pas à choisir un nouveau mot de passe sur %s',
        'registration.pending' => 'je n\'ai pas reçu mon e-mail de confirmation %s',
        'registration.expired' => 'mon lien de confirmation %s a expiré',

        // --- Création de la carte -------------------------------------------
        'profile.create.step1' => 'je suis à l\'étape 1 de la création de ma carte %s et j\'ai besoin d\'aide',
        'profile.create.step2' => 'je suis à l\'étape 2 de la création de ma carte %s et j\'ai besoin d\'aide',
        'profile.create.step3' => 'je suis à l\'étape 3 de la création de ma carte %s et j\'ai besoin d\'aide',
        'profile.preview' => 'je suis sur l\'aperçu de ma carte %s et j\'ai besoin d\'aide',
        'profile.edit' => 'je modifie ma carte %s et j\'ai besoin d\'aide',

        // --- Abonnement et paiement -----------------------------------------
        // Les seules pages où de l'argent est en jeu : le message doit le dire
        // pour que la demande soit traitée en premier.
        'abonnement.paiement' => 'j\'ai besoin d\'aide pour le paiement de mon abonnement %s',
        'abonnement.confirmation' => 'j\'ai une question sur mon paiement %s',
        'abonnement.retour' => 'j\'ai une question sur mon paiement %s',

        // --- Espace client ---------------------------------------------------
        'dashboard' => 'j\'ai besoin d\'aide sur mon espace %s',
        'profil.index' => 'j\'ai une question sur ma carte %s',
        'carte.qr' => 'j\'ai une question sur le QR Code de ma carte %s',
        'statistiques' => 'j\'ai une question sur les statistiques de ma carte %s',
        'compte.edit' => 'j\'ai besoin d\'aide sur les paramètres de mon compte %s',
        'notifications.index' => 'j\'ai une question sur mes notifications %s',
    ];

    /** Ce qu'on envoie quand la page n'est pas listée. */
    private const REPLI = 'j\'ai besoin d\'aide sur %s';

    /**
     * Le message complet, prêt à être encodé dans un lien WhatsApp.
     *
     * @param  string|null  $route  nom de route ; par défaut, la route courante
     */
    public static function message(?string $route = null): string
    {
        $route = $route ?? Route::currentRouteName();

        $phrase = self::MESSAGES[$route] ?? self::REPLI;

        return 'Bonjour, '.sprintf($phrase, config('app.name')).'.';
    }

    /**
     * Le motif du formulaire de contact qui correspond à la page.
     *
     * Sert aux liens « Nous écrire » posés ailleurs que sur la page d'accueil :
     * ils arrivent sur le formulaire avec le bon motif déjà choisi, plutôt
     * qu'avec une liste où il faut deviner lequel s'applique.
     */
    public static function motif(?string $route = null): string
    {
        $route = $route ?? Route::currentRouteName();

        return match (true) {
            str_starts_with((string) $route, 'abonnement.') => 'assistance',
            str_starts_with((string) $route, 'profile.create.') => 'assistance',
            in_array($route, ['login', 'register', 'password.request', 'password.reset',
                'registration.pending', 'registration.expired', 'dashboard', 'compte.edit',
                'profil.index', 'carte.qr', 'statistiques', 'profile.preview', 'profile.edit'], true) => 'assistance',
            default => 'information',
        };
    }

    /** Toutes les routes couvertes — les tests s'en servent pour ne rien oublier. */
    public static function routesCouvertes(): array
    {
        return array_keys(self::MESSAGES);
    }
}
