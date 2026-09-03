<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * LA PHOTO DE COMPTE — importer, ou revenir aux initiales.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ELLE N'EST PAS LA PHOTO DE LA CARTE
 * ═══════════════════════════════════════════════════════════════════════════
 * La carte publique a une image : la couverture, choisie dans l'assistant,
 * vue par les prospects. Celle-ci est l'avatar de l'espace client — en haut à
 * droite de chaque écran, et nulle part ailleurs.
 *
 * Quelqu'un peut vouloir un bandeau soigné sur sa carte commerciale et sa
 * propre tête dans son espace. Les confondre priverait de ce choix.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LES OCTETS SONT ÉCRITS EN BASE, PAS SEULEMENT SUR LE DISQUE
 * ═══════════════════════════════════════════════════════════════════════════
 * Le disque du conteneur est recréé à chaque déploiement. Un avatar qui n'y
 * vivrait que là disparaîtrait à la première mise en ligne, et le client
 * conclurait que le produit ne sait pas garder une image.
 */
class AvatarController extends Controller
{
    /** Un avatar recadré tient largement là-dedans. */
    private const MAX_OCTETS = 512 * 1024;

    public function update(Request $request): RedirectResponse
    {
        $valide = $request->validate([
            /*
             | 2 Mo À L'ENVOI, 512 Ko EN BASE.
             |
             | Les deux bornes ne mesurent pas la même chose : la première
             | refuse un fichier avant de le lire, la seconde décide s'il
             | mérite d'être conservé en base après recadrage. Une photo de
             | téléphone fait couramment 4 Mo ; recadrée en 256 pixels, elle
             | en fait quarante mille.
             */
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => __('profile.compte.avatar_requis'),
            'avatar.image' => __('validation.messages.carte.image_invalide'),
            'avatar.mimes' => __('validation.messages.carte.image_formats'),
            'avatar.max' => __('validation.messages.carte.image_trop_lourde'),
            'avatar.uploaded' => __('validation.messages.carte.image_envoi_echoue'),
        ]);

        $user = $request->user();
        $ancien = $user->avatar_path;

        $chemin = 'avatars/'.Str::uuid()->toString().'.jpg';
        $octets = $this->carre($valide['avatar']);

        try {
            Storage::disk('public')->put($chemin, $octets);
        } catch (Throwable) {
            return back()->with('error', __('profile.compte.avatar_echec'));
        }

        $user->forceFill([
            'avatar_path' => $chemin,
            // Au-delà du plafond, le disque seul : le cas ne survient qu'au
            // repli de `carre()`, quand GD manque ou que l'image est
            // indécodable. C'est une dégradation, pas une panne.
            'avatar_data' => strlen($octets) <= self::MAX_OCTETS ? $octets : null,
        ])->save();

        // L'ancien fichier part APRÈS l'écriture du nouveau : l'inverse
        // laisserait le compte sans avatar si l'écriture échouait.
        if ($ancien) {
            try {
                Storage::disk('public')->delete($ancien);
            } catch (Throwable) {
                // Un fichier orphelin ne vaut pas un message d'erreur.
            }
        }

        return back()->with('success', __('profile.compte.avatar_enregistre'));
    }

    /**
     * REVENIR AUX INITIALES.
     *
     * Ce n'est pas une suppression de compte ni une action risquée : c'est
     * le retour à l'état par défaut, celui de tous les comptes qui n'ont
     * jamais rien importé. Aucune confirmation ne se justifie.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $ancien = $user->avatar_path;

        $user->forceFill(['avatar_path' => null, 'avatar_data' => null])->save();

        if ($ancien) {
            try {
                Storage::disk('public')->delete($ancien);
            } catch (Throwable) {
                // Idem : un orphelin sur le disque ne mérite pas d'alerte.
            }
        }

        return back()->with('success', __('profile.compte.avatar_retire'));
    }

    /**
     * L'IMAGE, RECADRÉE EN CARRÉ DE 256 PIXELS.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI RECADRER PLUTÔT QUE STOCKER TEL QUEL
     * ═══════════════════════════════════════════════════════════════════
     * L'avatar s'affiche dans un rond de trente-deux pixels. Conserver
     * l'original ferait voyager quatre mégaoctets à chaque chargement de
     * page pour remplir un médaillon large comme un ongle.
     *
     * SI GD MANQUE, on rend le fichier d'origine plutôt que de refuser
     * l'import : une bibliothèque absente sur le serveur n'est pas la faute
     * du client, et un avatar lourd vaut mieux qu'un avatar impossible.
     */
    private function carre(UploadedFile $fichier): string
    {
        $brut = (string) file_get_contents($fichier->getRealPath());

        if (! function_exists('imagecreatetruecolor')) {
            return $brut;
        }

        try {
            $source = @imagecreatefromstring($brut);

            if ($source === false) {
                return $brut;
            }

            $l = imagesx($source);
            $h = imagesy($source);
            $cote = min($l, $h);

            // Le carré est pris au CENTRE : un visage se trouve au milieu
            // bien plus souvent qu'en haut à gauche.
            $x = (int) (($l - $cote) / 2);
            $y = (int) (($h - $cote) / 2);

            $cible = imagecreatetruecolor(256, 256);
            imagecopyresampled($cible, $source, 0, 0, $x, $y, 256, 256, $cote, $cote);

            ob_start();
            imagejpeg($cible, null, 82);
            $sortie = (string) ob_get_clean();

            imagedestroy($source);
            imagedestroy($cible);

            return $sortie !== '' ? $sortie : $brut;
        } catch (Throwable) {
            return $brut;
        }
    }
}
