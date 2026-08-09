<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Détecte les écarts entre le schéma réel, les $fillable et les clés
 * réellement écrites dans le code.
 *
 * C'est exactement ce type d'écart qui a produit l'erreur
 * « Unknown column 'phone' in field list » sur pending_registrations.
 */
class SchemaCheck extends Command
{
    protected $signature = 'schema:check {--model= : Ne vérifier qu\'un modèle}';

    protected $description = 'Compare colonnes réelles, $fillable et clés écrites dans le code.';

    /** Colonnes gérées par Eloquent, absentes du $fillable à dessein. */
    private const IGNORED = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'remember_token', 'email_verified_at',
    ];

    private int $problems = 0;

    public function handle(): int
    {
        $models = $this->models();

        if ($only = $this->option('model')) {
            $models = array_filter($models, fn ($m) => class_basename($m) === $only);

            if (empty($models)) {
                $this->error("Modèle « {$only} » introuvable.");

                return self::FAILURE;
            }
        }

        $writtenKeys = $this->keysWrittenInCode();

        foreach ($models as $class) {
            $this->checkModel($class, $writtenKeys);
        }

        $this->newLine();

        if ($this->problems === 0) {
            $this->info('Schéma cohérent : aucun écart détecté.');

            return self::SUCCESS;
        }

        $this->error("{$this->problems} écart(s) détecté(s).");

        return self::FAILURE;
    }

    private function checkModel(string $class, array $writtenKeys): void
    {
        /** @var Model $model */
        $model = new $class;
        $table = $model->getTable();
        $name = class_basename($class);

        if (! Schema::hasTable($table)) {
            $this->line("<fg=yellow>[{$name}]</> table « {$table} » absente — migration non exécutée ?");
            $this->problems++;

            return;
        }

        $columns = Schema::getColumnListing($table);
        $fillable = $model->getFillable();
        $rows = [];

        // 1. Colonne en base, absente du $fillable (hors colonnes gérées).
        foreach (array_diff($columns, $fillable, self::IGNORED) as $column) {
            $rows[] = ['Colonne hors $fillable', $column,
                'Ajouter au $fillable, ou ignorer si volontaire'];
        }

        // 2. $fillable sans colonne correspondante — CAS GRAVE : erreur SQL garantie.
        foreach (array_diff($fillable, $columns) as $column) {
            $rows[] = ['<fg=red>$fillable sans colonne</>', $column,
                'Migration manquante ou colonne renommée'];
            $this->problems++;
        }

        // 3. Clé écrite dans le code, absente de la table — CAS GRAVE.
        foreach (($writtenKeys[$name] ?? []) as $key) {
            if (! in_array($key, $columns, true)) {
                $rows[] = ['<fg=red>Écrite dans le code, absente en base</>', $key,
                    'Vérifier la migration'];
                $this->problems++;
            }
        }

        if ($rows) {
            $this->newLine();
            $this->line("<options=bold>{$name}</> ({$table})");
            $this->table(['Type d\'écart', 'Colonne', 'Action'], $rows);
        } else {
            $this->line("<fg=green>✓</> {$name} ({$table}) — ".count($columns).' colonnes');
        }
    }

    /**
     * Extrait les clés de tableau passées à create() / updateOrCreate() / fill()
     * dans les services, contrôleurs et actions, regroupées par modèle.
     *
     * Analyse statique volontairement simple : elle signale, elle ne compile pas.
     */
    private function keysWrittenInCode(): array
    {
        $found = [];
        $paths = [app_path('Services'), app_path('Http/Controllers'), app_path('Actions')];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $code = $file->getContents();

                // Ex. : User::create([...]) ou Profile::updateOrCreate([...], [...])
                preg_match_all(
                    '/(\w+)::(?:create|updateOrCreate|firstOrCreate|make)\s*\((.*?)\)\s*;/s',
                    $code,
                    $matches,
                    PREG_SET_ORDER
                );

                foreach ($matches as [, $model, $args]) {
                    preg_match_all("/'([a-z_][a-z0-9_]*)'\s*=>/i", $args, $keys);

                    foreach ($keys[1] ?? [] as $key) {
                        $found[$model][] = $key;
                    }
                }
            }
        }

        return array_map(fn ($keys) => array_values(array_unique($keys)), $found);
    }

    /** @return list<class-string<Model>> */
    private function models(): array
    {
        $models = [];

        foreach (File::allFiles(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.Str::before($file->getFilename(), '.php');

            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                $models[] = $class;
            }
        }

        sort($models);

        return $models;
    }
}
