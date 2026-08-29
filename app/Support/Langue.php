<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

/**
 * LA LANGUE COURANTE — un seul endroit décide.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CINQ SOURCES, DANS UN ORDRE QUI N'EST PAS ARBITRAIRE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. LE COMPTE, si quelqu'un est connecté. C'est le seul choix EXPLICITE et
 *    DURABLE : la personne l'a fait, il la suit de son téléphone à son poste.
 *    Il l'emporte donc sur tout le reste — y compris sur un cookie déposé
 *    depuis un autre navigateur, ou avant la connexion.
 *
 * 2. LA SESSION, pour un visiteur. Écrite au moment du choix, relue à chaque
 *    requête sans toucher au disque ni à la base.
 *
 * 3. LE COOKIE, en secours. Une session expire — au bout de deux heures par
 *    défaut, et à chaque redémarrage du conteneur avec un driver en mémoire.
 *    Sans ce niveau, quelqu'un qui revient le lendemain retrouve le français
 *    sans avoir rien demandé. C'est exactement ce que la consigne interdit :
 *    « l'utilisateur ne doit plus jamais reprendre son choix ».
 *
 * 4. ACCEPT-LANGUAGE, pour une PREMIÈRE visite. Un anglophone qui arrive par
 *    un lien partagé n'a ni compte, ni session, ni cookie : la seule chose
 *    qu'on sache de lui est ce que son navigateur annonce. La deviner mal
 *    coûte un clic ; ne pas la deviner du tout coûte une page illisible.
 *
 * 5. LE FRANÇAIS. Le produit est sénégalais.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA NÉGOCIATION EXIGE QU'ON LUI TENDE LA REQUÊTE — ET CE N'EST PAS UN DÉTAIL
 * ═══════════════════════════════════════════════════════════════════════
 * `courante()` ne va PAS chercher request() toute seule, alors que ce serait
 * plus court. Voici pourquoi.
 *
 * `SetRequestForConsole` fabrique une requête factice au démarrage de CHAQUE
 * commande artisan, à partir de APP_URL. Elle passe par `Request::create()`,
 * dont le tableau $server par défaut contient :
 *
 *     'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5'
 *
 * Une méthode qui lirait request() d'elle-même négocierait donc l'anglais
 * dans toute commande artisan et tout job en file — rappels d'échéance,
 * récapitulatifs quotidiens, sortie de `app:health`. Non pas parce que
 * quelqu'un l'aurait demandé, mais parce qu'une valeur par défaut d'une
 * bibliothèque tierce aurait décidé à la place du produit.
 *
 * On a d'abord songé à un `app()->runningInConsole()`. Il est vrai sous
 * PHPUnit : le garde aurait rendu la négociation impossible à tester, donc
 * impossible à protéger d'une régression.
 *
 * SEUL LE MIDDLEWARE HTTP TEND LA REQUÊTE. Une négociation porte sur un
 * navigateur ; hors requête HTTP il n'y en a pas, et l'en-tête ne veut rien
 * dire. La règle est portée par la signature plutôt que par un test d'état.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * RIEN N'EST ÉCRIT PENDANT LA LECTURE
 * ═══════════════════════════════════════════════════════════════════════
 * La négociation ne mémorise pas son résultat. Écrire en session ce qu'on
 * vient de déduire d'un en-tête ferait passer une SUPPOSITION pour un CHOIX :
 * on ne pourrait plus les distinguer, et le report à la connexion (plus bas)
 * écraserait la préférence du compte avec une devinette.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA PAGE PUBLIQUE SUIT LE VISITEUR, PAS LE PROPRIÉTAIRE
 * ═══════════════════════════════════════════════════════════════════════
 * Un client sénégalais qui a choisi le français garde une carte lisible par un
 * correspondant anglophone : c'est ce dernier qui la lit, et c'est donc SA
 * langue qui doit habiller les libellés. Le contenu du profil, lui, reste tel
 * que son propriétaire l'a écrit — on ne traduit ni une fonction, ni un nom
 * d'entreprise.
 *
 * Aucun code particulier n'est nécessaire pour cela : la chaîne ci-dessus ne
 * consulte QUE le visiteur. C'est en voulant faire autrement qu'il faudrait
 * écrire quelque chose.
 */
class Langue
{
    public const FRANCAIS = 'fr';

    public const ANGLAIS = 'en';

    /** Un an : une préférence de langue n'a pas à être redemandée. */
    public const DUREE_COOKIE = 60 * 24 * 365;

    private const COOKIE = 'langue';

    private const SESSION = 'langue';

    /** @return list<string> */
    public static function disponibles(): array
    {
        return [self::FRANCAIS, self::ANGLAIS];
    }

    /** Libellés courts, dans LEUR propre langue — jamais traduits. */
    public static function libelles(): array
    {
        return [
            self::FRANCAIS => 'Français',
            self::ANGLAIS => 'English',
        ];
    }

    /**
     * LA LANGUE QUE CETTE REQUÊTE DOIT SERVIR.
     *
     * Appelée par le middleware, et par lui seul. Partout ailleurs — vues,
     * composants, e-mails — c'est `active()` qu'il faut lire : la décision
     * est déjà prise, la relire ne peut que produire une seconde réponse.
     */
    public static function courante(?Request $requete = null): string
    {
        $utilisateur = Auth::user();

        if ($utilisateur !== null && self::valide($utilisateur->locale)) {
            return $utilisateur->locale;
        }

        if (Session::isStarted()) {
            $session = Session::get(self::SESSION);

            if (self::valide($session)) {
                return $session;
            }
        }

        $cookie = Cookie::get(self::COOKIE);

        if (self::valide($cookie)) {
            return $cookie;
        }

        return self::negociee($requete) ?? self::FRANCAIS;
    }

    /**
     * LA LANGUE EFFECTIVEMENT APPLIQUÉE — la seule vérité pour l'affichage.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI LES VUES NE DOIVENT PAS RAPPELER courante()
     * ═══════════════════════════════════════════════════════════════════
     * Le middleware a déjà tranché et posé le résultat sur l'application.
     * Une vue qui refait le calcul peut arriver à une AUTRE réponse — elle
     * n'a pas la requête sous la main, donc pas la négociation. Le sélecteur
     * cocherait alors « Français » sur une page rendue en anglais.
     *
     * Un désaccord entre ce qui est affiché et ce qui est coché est
     * exactement le genre de défaut qu'aucun test ne voit et que tout le
     * monde remarque.
     */
    public static function active(): string
    {
        $locale = App::getLocale();

        return self::valide($locale) ? $locale : self::FRANCAIS;
    }

    /**
     * CE QUE LE NAVIGATEUR ANNONCE, s'il annonce quelque chose d'utilisable.
     *
     * `Accept-Language: en-GB,en;q=0.9,fr;q=0.8` doit rendre « en ». Symfony
     * trie déjà par facteur de qualité : on prend la première langue de la
     * liste dont la RACINE nous est connue — « en-GB » compte pour « en »,
     * sans quoi on renverrait un Britannique au français.
     */
    private static function negociee(?Request $requete): ?string
    {
        if ($requete === null) {
            return null;
        }

        foreach ($requete->getLanguages() as $annoncee) {
            $racine = strtolower(substr((string) $annoncee, 0, 2));

            if (self::valide($racine)) {
                return $racine;
            }
        }

        return null;
    }

    public static function valide(?string $code): bool
    {
        return $code !== null && in_array($code, self::disponibles(), true);
    }

    /** La langue vers laquelle bascule le sélecteur — l'autre. */
    public static function inverse(): string
    {
        return self::active() === self::FRANCAIS ? self::ANGLAIS : self::FRANCAIS;
    }

    public static function libelle(?string $code = null): string
    {
        return self::libelles()[$code ?? self::active()] ?? 'Français';
    }

    /** Le code court affiché dans la barre : « FR », « EN ». */
    public static function code(?string $code = null): string
    {
        return mb_strtoupper($code ?? self::active());
    }

    /**
     * L'ÉTIQUETTE COMPLÈTE POUR og:locale ET <html lang>.
     *
     * Le français du produit est celui du Sénégal : « fr_SN » plutôt que
     * « fr_FR ». L'anglais n'a pas d'ancrage local ici — un correspondant
     * anglophone peut être n'importe où — donc « en ».
     */
    public static function etiquetteOuverte(): string
    {
        return self::active() === self::FRANCAIS ? 'fr_SN' : 'en';
    }

    public static function nomDuCookie(): string
    {
        return self::COOKIE;
    }

    public static function cleDeSession(): string
    {
        return self::SESSION;
    }

    /**
     * LE CHOIX EN SESSION, POSÉ AU MOMENT OÙ IL EST FAIT.
     *
     * Séparé de la lecture : c'est ce qui garantit qu'aucune supposition ne
     * s'écrit jamais là où seul un choix a le droit d'être.
     */
    public static function memoriserEnSession(string $code): void
    {
        if (self::valide($code)) {
            Session::put(self::SESSION, $code);
        }
    }

    /**
     * LE CHOIX FAIT AVANT LA CONNEXION SUIT LA PERSONNE APRÈS.
     *
     * Quelqu'un qui bascule la landing en anglais, puis crée un compte, ne
     * doit pas retomber en français sur le tableau de bord : il n'a rien
     * changé entre les deux écrans.
     *
     * LA CLÉ DE SESSION N'EXISTE QUE SI QUELQU'UN A CLIQUÉ. La négociation
     * n'écrit rien : trouver une valeur ici, c'est donc toujours trouver un
     * choix délibéré, fait dans ce navigateur quelques minutes plus tôt.
     *
     * La connexion conserve les données de session — regenerate() ne change
     * que l'identifiant. C'est ce qui rend ce report possible.
     */
    public static function reporterSurLeCompte(mixed $utilisateur): void
    {
        if ($utilisateur === null || ! Session::isStarted()) {
            return;
        }

        /*
         | LA SESSION D'ABORD, LE COOKIE ENSUITE — ET LE COOKIE COMPTE.
         |
         | Ne lire que la session paraissait suffisant : le choix vient
         | d'y être écrit. Mais la session est justement ce qui se perd.
         | Elle expire, elle est vidée par une tentative de connexion
         | refusée, elle disparaît au redémarrage du conteneur.
         |
         | Le scénario, observé dans un navigateur : un visiteur choisit
         | l'anglais, une première connexion échoue — la session est
         | vidée —, il se connecte pour de bon, et il n'y a plus rien à
         | reporter. Le compte reste en français, et la préférence du
         | compte l'emporte ensuite sur le cookie à CHAQUE requête.
         |
         | La langue disparaissait donc au moment précis où l'utilisateur
         | se connectait. Le cookie est le niveau prévu pour survivre à
         | la session : le report doit le consulter aussi.
         */
        $choix = Session::get(self::SESSION);

        if (! self::valide($choix)) {
            $choix = Cookie::get(self::COOKIE);
        }

        if (! self::valide($choix) || $choix === $utilisateur->locale) {
            return;   // rien à reporter, ou déjà à jour : pas d'écriture inutile
        }

        $utilisateur->forceFill(['locale' => $choix])->save();
    }
}
