<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Produit les secrets de l'automatisation, et dit où les coller.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE COMMANDE PLUTÔT QU'UNE PHRASE DANS LA DOCUMENTATION
 * ═══════════════════════════════════════════════════════════════════════
 * « Choisissez un jeton » produit toujours le même résultat : une chaîne
 * courte, mémorisable, souvent dérivée du nom du projet. Or ce jeton protège
 * une adresse qui déclenche des envois d'e-mails.
 *
 * Quarante-huit octets tirés du générateur cryptographique ne se devinent
 * pas, et ne coûtent rien à produire — à condition que la commande existe.
 */
class AutomationToken extends Command
{
    protected $signature = 'automation:token';

    protected $description = 'Produit les secrets nécessaires à l\'automatisation Make.';

    public function handle(): int
    {
        $jeton = Str::random(48);
        $secret = Str::random(48);

        $this->newLine();
        $this->line('<comment>Secrets d\'automatisation — à coller dans .env et dans Render</comment>');
        $this->newLine();

        $this->line('AUTOMATION_SCHEDULE_TOKEN='.$jeton);
        $this->line('MAKE_WEBHOOK_SECRET='.$secret);

        $this->newLine();
        $this->line('<comment>Ce que Make doit appeler</comment>');
        $this->line('  Méthode : POST');
        $this->line('  URL     : '.rtrim((string) config('app.url'), '/').'/automation/schedule');
        $this->line('  En-tête : X-Automation-Token: '.$jeton);
        $this->line('  Cadence : toutes les minutes');

        $this->newLine();
        $this->line('<comment>Trois choses à savoir</comment>');
        $this->line('  1. Tant que AUTOMATION_SCHEDULE_TOKEN est vide, la route rend 404.');
        $this->line('     C\'est voulu : une route ouverte qu\'on croit fermée est pire');
        $this->line('     qu\'une route absente.');
        $this->line('  2. Le jeton passe en EN-TÊTE, pas dans l\'URL : une adresse complète');
        $this->line('     se retrouve dans les journaux et l\'historique du navigateur.');
        $this->line('  3. MAKE_WEBHOOK_SECRET sert au sens INVERSE — il signe ce que nous');
        $this->line('     envoyons à Make, pour que le scénario rejette les faux prospects.');
        $this->newLine();

        // Le fichier n'est PAS modifié automatiquement : écrire dans .env
        // depuis une commande, c'est risquer d'écraser une valeur en
        // production sur une faute de frappe. On imprime, l'humain colle.
        $this->warn('Ces valeurs ne sont pas enregistrées : copiez-les vous-même.');
        $this->newLine();

        return self::SUCCESS;
    }
}
