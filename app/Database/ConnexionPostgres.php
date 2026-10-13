<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection;
use PDO;

/**
 * LA CONNEXION POSTGRESQL, À UNE DIFFÉRENCE PRÈS : LES OCTETS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE MYSQL ACCEPTAIT ET QUE POSTGRESQL REFUSE
 * ═══════════════════════════════════════════════════════════════════════
 * Les images de couverture et les avatars sont aussi écrits en base
 * (cover_data, avatar_data) : le disque du conteneur ne survit pas à un
 * déploiement. Laravel envoie toute chaîne comme du TEXTE. MySQL range ce
 * texte dans un BLOB sans le lire ; PostgreSQL, lui, le valide comme de
 * l'UTF-8 — et une image JPEG n'en est pas. L'enregistrement échoue sur
 * « invalid byte sequence for encoding UTF8 », au milieu de la création
 * d'une carte.
 *
 * Une chaîne qui n'est pas de l'UTF-8 valide, ou qui contient un octet nul,
 * ne PEUT pas être du texte pour PostgreSQL. On l'envoie donc comme ce
 * qu'elle est : un objet binaire (PDO::PARAM_LOB), que pdo_pgsql transmet
 * tel quel à une colonne bytea.
 *
 * Tout le reste est lié exactement comme le fait Laravel.
 *
 * La lecture a son pendant : pdo_pgsql rend un bytea sous forme de flux, et
 * le cast App\Casts\Octets le relit en chaîne.
 */
class ConnexionPostgres extends PostgresConnection
{
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_resource($value) => PDO::PARAM_LOB,
                    is_string($value) && self::estBinaire($value) => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR,
                },
            );
        }
    }

    private static function estBinaire(string $valeur): bool
    {
        return str_contains($valeur, "\0") || ! mb_check_encoding($valeur, 'UTF-8');
    }
}
