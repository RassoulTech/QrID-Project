<?php

namespace App\Support;

use App\Models\Profile;
use InvalidArgumentException;

/**
 * LES MESSAGES WHATSAPP — un registre, des variables, un seul constructeur
 * de lien.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CETTE CLASSE REMPLACE
 * ═══════════════════════════════════════════════════════════════════════════
 * WhatsApp était présent à quatre endroits, chacun avec sa propre façon de
 * construire une adresse `wa.me` :
 *
 *   FormatsSenegalPhone   pour joindre le titulaire d'une carte
 *   whatsapp-fab          pour écrire au support, message déduit de la page
 *   landing/contact       un lien écrit en dur dans le gabarit
 *   config/registration   une URL de support, encore
 *
 * Quatre implémentations veulent dire quatre endroits où corriger le jour où
 * WhatsApp change son format d'URL, où l'on veut compter les partages, ou
 * simplement où un numéro contient une espace de trop.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LA RÈGLE DE CONFIDENTIALITÉ, ÉCRITE ICI PARCE QU'ELLE SE PERD AILLEURS
 * ═══════════════════════════════════════════════════════════════════════════
 * AideContextuelle l'a posée pour le canal support : aucune donnée
 * personnelle dans le message. Le texte part hors de l'application, dans une
 * URL que le navigateur retient et que WhatsApp Web affiche.
 *
 * Elle reste vraie, avec UNE exception, et il faut la nommer pour qu'elle ne
 * s'élargisse pas toute seule :
 *
 *   INTERDIT   les données d'un tiers, l'adresse e-mail d'un compte, un
 *              identifiant interne, un jeton, tout ce qui n'est pas déjà
 *              public.
 *
 *   PERMIS     ce que la personne DIFFUSE VOLONTAIREMENT sur sa propre
 *              carte : son nom d'affichage et l'adresse publique de sa page.
 *              Les cacher n'ajouterait aucune protection — la page est faite
 *              pour être vue, et c'est précisément ce qu'elle partage.
 *
 * La distinction tient en une question : « cette information est-elle déjà
 * lisible par quiconque ouvre le lien ? » Si non, elle n'entre pas dans un
 * message.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LES TEXTES SONT DANS LES FICHIERS DE LANGUE, PAS ICI
 * ═══════════════════════════════════════════════════════════════════════════
 * Un message WhatsApp est du texte lu par un humain : il suit la même loi que
 * le reste du produit. Écrit dans une constante PHP, il imposerait le
 * français à un visiteur anglophone sans qu'aucun test de traduction ne le
 * voie — une chaîne hors de `lang/` échappe au vérificateur.
 */
final class Whatsapp
{
    /**
     * Les catégories reconnues. Une clé de gabarit commence toujours par
     * l'une d'elles.
     *
     * Elles ne sont pas décoratives : elles disent CE QUE FAIT le message, et
     * c'est ce qui permet plus tard de compter les partages séparément des
     * demandes d'aide, ou de router les uns vers une API et les autres vers
     * un simple lien.
     */
    public const CATEGORIES = [
        'partage',      // je diffuse quelque chose qui m'appartient
        'invitation',   // j'invite quelqu'un à me rejoindre
        'contact',      // j'écris au titulaire d'une carte
        'assistance',   // j'écris à l'équipe du produit
    ];

    /**
     * Le texte d'un gabarit, ses variables remplacées.
     *
     * @param  string  $cle  « partage.carte », « contact.titulaire »…
     * @param  array<string, string|null>  $variables
     */
    public static function texte(string $cle, array $variables = []): string
    {
        self::verifierLaCategorie($cle);

        $variables = array_map(
            fn ($valeur) => trim((string) $valeur),
            $variables,
        );

        /*
         | « messages-whatsapp » ET NON « whatsapp », ET CE N'EST PAS UN
         | DÉTAIL DE NOMMAGE.
         |
         | Laravel lit `__('WhatsApp')` comme une demande de FICHIER de
         | traduction quand un fichier de ce nom existe. Le produit affiche
         | ce mot comme libellé de bouton — `__('WhatsApp')` dans la
         | maquette du hero — et un fichier `whatsapp.php` le faisait donc
         | rendre le tableau entier des gabarits à la place du mot.
         |
         | Pire : le défaut dépend du SYSTÈME DE FICHIERS. Sur Windows, où
         | la casse n'est pas distinguée, `whatsapp.php` répond à
         | « WhatsApp » ; sur Linux, non. Une page cassée en développement
         | et intacte en production, ou l'inverse.
         |
         | Un nom composé ne peut être confondu avec aucun libellé.
         */
        return trim(__('messages-whatsapp.'.$cle, $variables));
    }

    /**
     * Le lien à ouvrir.
     *
     * ═══════════════════════════════════════════════════════════════════
     * UN SEUL FORMAT, MOBILE COMME BUREAU
     * ═══════════════════════════════════════════════════════════════════
     * `wa.me` n'est pas un choix parmi d'autres : c'est le point d'entrée
     * officiel, et c'est LUI qui décide où envoyer la personne. Sur
     * téléphone il ouvre l'application installée ; sur ordinateur il
     * redirige vers WhatsApp Web ou vers l'application de bureau selon ce
     * qui est disponible.
     *
     * Choisir nous-mêmes `api.whatsapp.com` ou `web.whatsapp.com` selon la
     * taille de l'écran reviendrait à deviner à sa place, et à se tromper
     * sur toutes les tablettes.
     *
     * SANS NUMÉRO, le lien ouvre le sélecteur de contacts : c'est
     * exactement ce qu'il faut pour un bouton « Partager », où l'on ne sait
     * pas encore à qui l'on écrit.
     */
    public static function lien(?string $numero, string $texte): string
    {
        $base = 'https://wa.me/';

        if ($numero !== null && $numero !== '') {
            // wa.me n'accepte QUE des chiffres. Un « + », une espace ou un
            // point produit un lien qui s'ouvre sur une erreur WhatsApp —
            // et la personne conclut que le bouton est cassé.
            $base .= preg_replace('/\D+/', '', $numero);
        }

        return $base.'?text='.rawurlencode($texte);
    }

    // =======================================================================
    // LES USAGES DU PRODUIT
    //
    // Chacun nomme un endroit précis de l'application. Un appelant ne
    // construit jamais sa clé à la main : il demande « le lien de partage de
    // cette carte », et le texte suit.
    // =======================================================================

    /**
     * « Partager ma carte » — depuis le tableau de bord ou la page publique.
     *
     * Sans numéro : la personne choisit son destinataire dans WhatsApp.
     */
    public static function partageDeLaCarte(Profile $profile, string $url): string
    {
        return self::lien(null, self::texte('partage.carte', [
            'nom' => $profile->full_name,
            'url' => $url,
        ]));
    }

    /** « Partager mon QR Code » — le même geste, depuis l'écran du QR. */
    public static function partageDuQrCode(Profile $profile, string $url): string
    {
        return self::lien(null, self::texte('partage.qr', [
            'nom' => $profile->full_name,
            'url' => $url,
        ]));
    }

    /**
     * « Écrire au titulaire » — depuis la page publique d'une carte.
     *
     * Le message ne porte AUCUNE donnée du visiteur : il ne s'est pas
     * identifié, et rien ne justifie de deviner qui il est.
     */
    public static function contactDuTitulaire(Profile $profile): ?string
    {
        $numero = $profile->whatsapp ?: $profile->phone;

        if (! $numero) {
            return null;
        }

        return self::lien($numero, self::texte('contact.titulaire', [
            'nom' => $profile->first_name,
        ]));
    }

    /**
     * « Inviter un confrère » — depuis l'espace client.
     *
     * L'invitation porte l'adresse du produit, jamais celle du parrain :
     * un lien de parrainage n'existe pas encore, et en simuler un
     * produirait un compteur que rien n'alimente.
     */
    public static function invitation(string $url): string
    {
        return self::lien(null, self::texte('invitation.confrere', ['url' => $url]));
    }

    /**
     * « Nous écrire » — le canal support, message déduit de la page.
     *
     * Il délègue à AideContextuelle, qui tient la correspondance écran par
     * écran depuis le début. La reprendre ici aurait produit deux listes à
     * maintenir, et c'est exactement ce que cette classe supprime.
     */
    public static function assistance(?string $route = null): ?string
    {
        $numero = trim((string) config('landing.support.whatsapp'));

        if ($numero === '') {
            return null;
        }

        return self::lien($numero, AideContextuelle::message($route));
    }

    /**
     * Une clé sans catégorie connue est une erreur de programmation, pas une
     * donnée douteuse : elle doit casser en développement plutôt que de
     * produire un message vide en production.
     */
    private static function verifierLaCategorie(string $cle): void
    {
        $categorie = str_contains($cle, '.') ? explode('.', $cle)[0] : $cle;

        if (! in_array($categorie, self::CATEGORIES, true)) {
            throw new InvalidArgumentException(
                "Catégorie WhatsApp inconnue : « {$categorie} ». ".
                'Attendu : '.implode(', ', self::CATEGORIES).'.'
            );
        }
    }
}
