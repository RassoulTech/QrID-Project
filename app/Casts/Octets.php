<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * UNE COLONNE BINAIRE SE LIT TOUJOURS EN CHAÎNE, QUEL QUE SOIT LE MOTEUR.
 *
 * MySQL et SQLite rendent un BLOB sous forme de chaîne. PostgreSQL, via
 * pdo_pgsql, rend un bytea sous forme de FLUX — une ressource PHP. Sans ce
 * cast, `blank($profile->cover_data)` répondrait « non vide » sur un flux,
 * puis la méthode qui doit renvoyer les octets renverrait une ressource et
 * lèverait une TypeError : la carte publique tomberait, image comprise.
 *
 * Le flux est relu DEPUIS LE DÉBUT à chaque accès (offset 0). Eloquent ne
 * met pas en cache le résultat d'un cast scalaire : il rappelle get() avec
 * la même ressource, déjà lue jusqu'au bout la première fois. Sans le
 * retour au début, le second accès rendrait une chaîne vide — une image
 * qui disparaît au deuxième affichage.
 *
 * L'écriture n'a rien à convertir : App\Database\ConnexionPostgres envoie
 * les octets comme un objet binaire.
 */
class Octets implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_resource($value)) {
            return stream_get_contents($value, null, 0);
        }

        return $value === null ? null : (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
