<?php

namespace App\Support\Design;

use Illuminate\Foundation\Http\FormRequest;
use Throwable;

/**
 * QUELS CHAMPS SONT OBLIGATOIRES — la réponse vient du serveur.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE DÉFAUT QUE CELA SUPPRIME
 * ═══════════════════════════════════════════════════════════════════════
 * L'astérisque était passé à la main : `<x-input required />`. Rien ne
 * garantissait qu'il corresponde à la règle de validation.
 *
 * Un champ obligatoire côté serveur pouvait donc s'afficher facultatif.
 * L'utilisateur remplit, envoie, et reçoit une erreur sur un champ que
 * l'interface lui disait optionnel. Aucun test ne rougissait : les deux
 * affirmations vivaient dans deux fichiers qui ne se parlaient pas.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * COMMENT
 * ═══════════════════════════════════════════════════════════════════════
 * La vue déclare une fois quelle FormRequest gouverne son formulaire :
 *
 *     @php(App\Support\Design\Champs::gouvernePar(StoreEtape1Request::class))
 *
 * Les composants de champ lisent ensuite cet ensemble. Aucun ne reçoit
 * plus `required` à la main, et il devient impossible que les deux
 * divergent.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UN ÉTAT STATIQUE, ET POURQUOI C'EST ACCEPTABLE ICI
 * ═══════════════════════════════════════════════════════════════════════
 * Blade ne propage pas de contexte d'un composant vers ses descendants.
 * Passer la FormRequest à chaque champ reviendrait à réintroduire la
 * répétition qu'on supprime.
 *
 * L'état est donc statique, et remis à zéro à chaque déclaration. Le
 * rendu d'une vue est séquentiel et mono-requête : deux formulaires sur
 * une même page se déclarent l'un après l'autre, chacun avant ses
 * champs. C'est le cas de `account/edit`, qui en porte trois.
 */
final class Champs
{
    /** @var array<string, true> */
    private static array $obligatoires = [];

    private static ?string $source = null;

    /**
     * Déclare la règle de validation qui gouverne les champs suivants.
     *
     * @param  class-string<FormRequest>  $requete
     */
    public static function gouvernePar(string $requete): void
    {
        self::$obligatoires = [];
        self::$source = $requete;

        try {
            $instance = new $requete;

            if (! $instance instanceof FormRequest) {
                return;
            }

            foreach ($instance->rules() as $champ => $regles) {
                if (self::exige($regles)) {
                    // `photos.*.legende` doit rendre l'astérisque de
                    // `photos[0][legende]` : on retient la forme normalisée.
                    self::$obligatoires[self::normaliser($champ)] = true;
                }
            }
        } catch (Throwable) {
            /*
             | UNE FORMREQUEST PEUT DÉPENDRE DE LA REQUÊTE COURANTE pour
             | construire ses règles — un `Rule::unique()->ignore($this->user())`
             | par exemple. Hors requête, l'instanciation échoue.
             |
             | On ne laisse PAS l'exception remonter : un formulaire doit
             | s'afficher même si l'on n'a pas pu deviner ses obligations.
             | Sans astérisque, il reste utilisable ; avec une page blanche,
             | non.
             */
            self::$obligatoires = [];
        }
    }

    /** Le champ est-il obligatoire selon la règle déclarée ? */
    public static function estObligatoire(string $champ): bool
    {
        return isset(self::$obligatoires[self::normaliser($champ)]);
    }

    /** Une règle a-t-elle été déclarée pour ce formulaire ? */
    public static function gouverne(): bool
    {
        return self::$source !== null;
    }

    public static function oublier(): void
    {
        self::$obligatoires = [];
        self::$source = null;
    }

    /**
     * `required` compte, `required_if` aussi — mais pas `sometimes`.
     *
     * `required_without`, `required_if` et leurs variantes rendent le
     * champ obligatoire DANS CERTAINS CAS. L'astérisque les signale : il
     * vaut mieux annoncer une obligation conditionnelle que la taire.
     */
    private static function exige(mixed $regles): bool
    {
        if (is_string($regles)) {
            $regles = explode('|', $regles);
        }

        if (! is_array($regles)) {
            return false;
        }

        foreach ($regles as $regle) {
            if (! is_string($regle)) {
                continue;   // une règle-objet ne se lit pas ainsi
            }
            if ($regle === 'required' || str_starts_with($regle, 'required_')) {
                return true;
            }
        }

        return false;
    }

    /** `contacts.*.valeur` et `contacts[0][valeur]` désignent le même champ. */
    private static function normaliser(string $champ): string
    {
        $champ = str_replace(['][', '[', ']'], ['.', '.', ''], $champ);

        return (string) preg_replace('/\.\d+\./', '.*.', $champ);
    }
}
