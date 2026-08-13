<?php

namespace App\Console\Commands;

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Console\Command;

/**
 * CE QU'IL FAUT DÉCLARER DANS LA CONSOLE GOOGLE, au caractère près.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE COMMANDE EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * `redirect_uri_mismatch` est l'échec le plus fréquent de cette intégration,
 * et le plus opaque : Google refuse sans jamais dire QUELLE adresse il
 * attendait ni laquelle il a reçue. On compare alors de mémoire deux chaînes
 * qui se ressemblent, et l'on cherche pendant une heure un « /auth/google/
 * retour » manquant ou une barre oblique en trop.
 *
 * L'adresse est DÉRIVÉE de APP_URL par config/services.php, jamais écrite à
 * la main : cette commande ne fait que l'afficher, telle que l'application
 * l'enverra réellement. Ce qu'elle imprime est donc, par construction, ce
 * qu'il faut coller dans la console.
 */
class GoogleCheck extends Command
{
    protected $signature = 'google:check {--prod= : URL de production, pour obtenir aussi son adresse de retour}';

    protected $description = 'Affiche exactement ce qu\'il faut déclarer dans la console Google.';

    public function handle(): int
    {
        $configure = GoogleController::estDisponible();

        $this->newLine();
        $this->line('<comment>Connexion Google — état</comment>');

        $this->table(['Paramètre', 'Valeur'], [
            ['Identifiant client', $this->masquer((string) config('services.google.client_id'))],
            ['Secret client', $this->masquer((string) config('services.google.client_secret'))],
            ['APP_URL', config('app.url')],
            ['Bouton affiché', $configure ? 'oui' : 'NON — les clés manquent'],
        ]);

        $this->newLine();
        $this->line('<comment>URI de redirection autorisés — à coller dans la console Google</comment>');
        $this->line('Console Google Cloud → Clients → votre ID client OAuth');
        $this->newLine();

        $adresses = [config('services.google.redirect')];

        if ($prod = $this->option('prod')) {
            $adresses[] = rtrim((string) $prod, '/').'/auth/google/retour';
        }

        foreach ($adresses as $i => $adresse) {
            $this->line('  URI '.($i + 1).' : <info>'.$adresse.'</info>');
        }

        $this->newLine();
        $this->line('<comment>Trois pièges, dans l\'ordre de fréquence</comment>');
        $this->line('  1. LE CHEMIN MANQUE. « https://exemple.com » seul ne suffit pas :');
        $this->line('     il faut « https://exemple.com/auth/google/retour ».');
        $this->line('  2. UNE BARRE OBLIQUE EN TROP à la fin. Google compare littéralement.');
        $this->line('  3. LE STATUT « TEST » dans le menu Audience : seuls les comptes');
        $this->line('     ajoutés comme testeurs peuvent se connecter, les autres voient');
        $this->line('     « Accès bloqué ».');

        $this->newLine();
        $this->line('« Origines JavaScript autorisées » reste VIDE : ce champ ne sert qu\'aux');
        $this->line('connexions faites depuis le navigateur, jamais depuis un serveur.');
        $this->newLine();

        if (! $configure) {
            $this->error('GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET sont vides : le bouton reste masqué.');
            $this->line('Les renseigner dans .env, puis : php artisan config:clear');

            return self::FAILURE;
        }

        $this->info('Les clés sont en place. Si Google refuse encore, comparez son');
        $this->info('« détails de l\'erreur » à l\'URI ci-dessus, caractère par caractère.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Assez pour reconnaître la clé, jamais assez pour s'en servir.
     *
     * Cette commande finit copiée-collée dans une conversation pour demander
     * de l'aide — le secret n'a rien à y faire.
     */
    private function masquer(string $valeur): string
    {
        if ($valeur === '') {
            return '— ABSENT —';
        }

        return mb_strlen($valeur) <= 12
            ? str_repeat('•', mb_strlen($valeur))
            : mb_substr($valeur, 0, 8).str_repeat('•', 8).mb_substr($valeur, -4);
    }
}
