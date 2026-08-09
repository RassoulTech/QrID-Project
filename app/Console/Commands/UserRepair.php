<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Remet un compte en état de se connecter.
 *
 * Mot de passe réécrit, e-mail marqué vérifié, suspension levée. Le tout dans
 * une transaction : un compte à moitié réparé serait pire que rien.
 */
class UserRepair extends Command
{
    protected $signature = 'user:repair
        {email : Adresse du compte}
        {--password= : Nouveau mot de passe (généré si absent)}
        {--admin : Passer le compte en administrateur}';

    protected $description = 'Réinitialise le mot de passe d\'un compte, le marque vérifié et lève sa suspension';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Aucun compte pour cette adresse.');

            return self::FAILURE;
        }

        $motDePasse = $this->option('password') ?: Str::password(16, symbols: false);

        DB::transaction(function () use ($user, $motDePasse) {
            /*
             | Le mot de passe est écrit EN CLAIR : c'est le cast « hashed » du
             | modèle qui le hache. Appeler Hash::make() ici serait inutile —
             | le cast reconnaît une valeur déjà hachée et ne la re-hache pas,
             | mais s'en remettre à lui reste la seule écriture qui ne dépende
             | d'aucune supposition.
             */
            $user->forceFill([
                'password' => $motDePasse,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'is_blocked' => false,
                'blocked_at' => null,
                'blocked_reason' => null,
            ]);

            // saveQuietly : aucun événement, aucun e-mail déclenché.
            $user->saveQuietly();

            if ($this->option('admin')) {
                $user->forceFill(['role' => User::ROLE_ADMIN])->saveQuietly();
            }
        });

        $frais = $user->fresh();

        // Contrôle : on ne dit « c'est réparé » qu'après l'avoir vérifié.
        if (! Hash::check($motDePasse, $frais->getAuthPassword())) {
            $this->error('Le mot de passe écrit ne se vérifie pas. Le cast « hashed » du modèle '
                .'a probablement re-haché la valeur : contrôlez App\Models\User::casts().');

            return self::FAILURE;
        }

        $this->info('Compte réparé et vérifié.');
        $this->table(['Champ', 'Valeur'], [
            ['Adresse', $frais->email],
            ['Mot de passe', $motDePasse],
            ['Rôle', $frais->role ?? 'user'],
            ['E-mail vérifié', $frais->email_verified_at],
            ['Suspendu', $frais->is_blocked ? 'oui' : 'non'],
        ]);

        $this->newLine();
        $this->warn('Ce mot de passe s\'affiche une seule fois : notez-le maintenant.');

        return self::SUCCESS;
    }
}
