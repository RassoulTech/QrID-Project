<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV en flux.
 *
 * `streamDownload` et non un fichier construit en mémoire : l'export des
 * paiements grossit avec le produit, et un `->get()` sur toute la table
 * finirait par épuiser la mémoire du serveur un jour où personne ne
 * regardait. On écrit ligne par ligne, la mémoire reste plate.
 *
 * DEUX DÉTAILS QUI NE SONT PAS DES DÉTAILS, parce que le fichier sera ouvert
 * dans Excel sur un poste francophone :
 *
 *   · le BOM UTF-8 en tête, sans quoi « Ndiaye » s'affiche « NdiayeÂ » ;
 *   · le point-virgule comme séparateur, la virgule étant le séparateur
 *     décimal dans cette locale — sans lui, tout atterrit dans une colonne.
 */
final class CsvExport
{
    public static function stream(string $nomFichier, array $entetes, iterable|Builder $lignes, ?callable $transforme = null): StreamedResponse
    {
        return response()->streamDownload(function () use ($entetes, $lignes, $transforme) {
            $sortie = fopen('php://output', 'w');

            // BOM UTF-8 — pour Excel, et pour lui seul.
            fwrite($sortie, "\xEF\xBB\xBF");

            fputcsv($sortie, $entetes, ';');

            if ($lignes instanceof Builder) {
                // Par lots de 500 : la requête ne remonte jamais toute la
                // table d'un coup, même sur un export de plusieurs années.
                $lignes->chunk(500, function ($lot) use ($sortie, $transforme) {
                    foreach ($lot as $ligne) {
                        fputcsv($sortie, $transforme ? $transforme($ligne) : (array) $ligne, ';');
                    }
                });
            } else {
                foreach ($lignes as $ligne) {
                    fputcsv($sortie, $transforme ? $transforme($ligne) : (array) $ligne, ';');
                }
            }

            fclose($sortie);
        }, $nomFichier, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            // Sans cela, un proxy peut servir un export périmé à
            // l'administrateur suivant.
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** Nom horodaté : deux exports du même écran ne s'écrasent pas. */
    public static function nom(string $base): string
    {
        return $base.'-'.now()->format('Y-m-d-Hi').'.csv';
    }
}
