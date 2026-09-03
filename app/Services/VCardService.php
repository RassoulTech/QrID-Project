<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Support\Facades\Log;

/**
 * La fiche contact téléchargeable — « Enregistrer le contact ».
 *
 * C'est le geste qui termine le parcours du produit : on scanne un QR, on
 * regarde une carte, et on garde le contact. Sans lui, le visiteur doit
 * recopier un numéro à la main, ce que personne ne fait — la carte est vue
 * puis oubliée, et le scan n'aura servi à rien.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI LA VERSION 3.0 ET NON LA 4.0
 * ═══════════════════════════════════════════════════════════════════════
 * La 4.0 est plus propre sur le papier. Mais le parc téléphonique visé est
 * majoritairement Android, souvent ancien, et plusieurs lecteurs de
 * contacts d'avant 2020 ignorent purement et simplement un VERSION:4.0 —
 * l'utilisateur voit son téléphone ouvrir le fichier puis n'enregistrer
 * personne. La 3.0 est comprise partout, iOS compris. Sur un geste dont
 * l'échec est SILENCIEUX, la compatibilité passe avant l'élégance.
 */
class VCardService
{
    /**
     * Au-delà, la photo est abandonnée.
     *
     * Un contact n'a pas besoin d'un portrait en pleine résolution, et
     * certains lecteurs Android renoncent à analyser un fichier de plusieurs
     * mégaoctets — en n'enregistrant rien, sans rien dire.
     */
    private const PHOTO_MAX_OCTETS = 500_000;

    /** Le nom du fichier proposé au téléchargement : « mouhamed-dione.vcf ». */
    public function nomFichier(Profile $profile): string
    {
        return $profile->slug.'.vcf';
    }

    /**
     * La fiche complète, prête à être servie.
     *
     * Les fins de ligne sont des CRLF : la RFC 6350 les impose, et un lecteur
     * strict rejette un fichier en LF seuls.
     */
    public function pour(Profile $profile): string
    {
        $lignes = ['BEGIN:VCARD', 'VERSION:3.0'];

        /*
         | N est STRUCTURÉ : nom;prénom;;;. Les champs vides comptent — les
         | retirer décale tout ce qui suit, et le prénom se retrouve rangé
         | dans le nom de famille.
         */
        $lignes[] = 'N:'.$this->echapper($profile->last_name).';'
            .$this->echapper($profile->first_name).';;;';
        $lignes[] = 'FN:'.$this->echapper($profile->full_name);

        if ($profile->job_title) {
            $lignes[] = 'TITLE:'.$this->echapper($profile->job_title);
        }

        if ($profile->company) {
            $lignes[] = 'ORG:'.$this->echapper($profile->company);
        }

        /*
         | Le numéro est déjà stocké au format international « +221… ». C'est
         | ce qui permet à la fiche de rester appelable depuis l'étranger, ou
         | depuis un téléphone dont l'indicatif par défaut n'est pas le 221.
         */
        if ($profile->phone) {
            $lignes[] = 'TEL;TYPE=CELL,VOICE:'.$this->echapper($profile->phone);
        }

        // Le WhatsApp n'est ajouté QUE s'il diffère du numéro principal :
        // sinon la fiche enregistrée montre deux fois la même ligne.
        if ($profile->whatsapp && $profile->whatsapp !== $profile->phone) {
            $lignes[] = 'TEL;TYPE=CELL:'.$this->echapper($profile->whatsapp);
        }

        if ($profile->public_email) {
            $lignes[] = 'EMAIL;TYPE=INTERNET:'.$this->echapper($profile->public_email);
        }

        if ($profile->website) {
            $lignes[] = 'URL:'.$this->echapper($profile->website);
        }

        /*
         | ADR est structuré en sept composantes séparées par des
         | points-virgules. L'adresse saisie ici est libre — « Dakar,
         | Plateau » — et part donc dans la composante « rue », échappée : sa
         | virgule, sans cela, y serait lue comme un séparateur de valeurs.
         */
        if ($profile->address) {
            $lignes[] = 'ADR;TYPE=WORK:;;'.$this->echapper($profile->address).';;;;';
        }

        if ($profile->bio) {
            $lignes[] = 'NOTE:'.$this->echapper($profile->bio);
        }

        // Les réseaux, après le site : plusieurs URL sont permises, mais les
        // lecteurs les plus pauvres ne retiennent que la première.
        if ($profile->relationLoaded('socialLinks')) {
            foreach ($profile->socialLinks as $lien) {
                $lignes[] = 'URL:'.$this->echapper($lien->url);
            }
        }

        if ($photo = $this->photo($profile)) {
            $lignes[] = $photo;
        }

        /*
         | SOURCE porte l'adresse de la carte : le contact enregistré garde le
         | chemin du retour, et retrouve une fiche à jour même si le numéro
         | qu'il a gardé a changé depuis.
         */
        $lignes[] = 'SOURCE:'.$this->echapper(route('profile.public', $profile->slug));
        $lignes[] = 'REV:'.now()->utc()->format('Y-m-d\TH:i:s\Z');
        $lignes[] = 'END:VCARD';

        return implode("\r\n", $lignes)."\r\n";
    }

    /**
     * La photo, encodée dans le fichier — ou rien du tout.
     *
     * ELLE EST EMBARQUÉE, PAS LIÉE. Une PHOTO;VALUE=URI obligerait le
     * téléphone à retélécharger l'image plus tard : elle disparaîtrait le jour
     * où le profil est dépublié, ou simplement hors réseau. Un contact
     * enregistré doit survivre à la carte dont il vient.
     *
     * SON ABSENCE NE PEUT PAS EMPÊCHER L'ENREGISTREMENT. Un disque remplacé,
     * une photo effacée par un déploiement — le cas est réel, voir le risque
     * n° 3 du plan — coûtent le portrait, jamais la fiche. Même règle que
     * l'aperçu de partage sur la page publique.
     */
    private function photo(Profile $profile): ?string
    {
        if (! $profile->cover_path && blank($profile->cover_data)) {
            return null;
        }

        try {
            /*
             | ON DEMANDE LES OCTETS, PAS LE FICHIER.
             |
             | couvertureBinaire() lit le disque quand il l'a encore, la base sinon.
             | Le produit n'a qu'UNE image : la couverture est la photo.
             | Tester l'existence du fichier privait de portrait toutes les
             | fiches enregistrées après un déploiement, alors que la photo
             | était bel et bien conservée.
             */
            $binaire = (string) $profile->couvertureBinaire();

            if ($binaire === '' || strlen($binaire) > self::PHOTO_MAX_OCTETS) {
                return null;
            }

            $type = str_ends_with(strtolower((string) $profile->cover_path), '.png') ? 'PNG' : 'JPEG';

            return $this->plier('PHOTO;ENCODING=b;TYPE='.$type.':'.base64_encode($binaire));
        } catch (\Throwable $e) {
            Log::warning('Photo non embarquée dans la fiche contact', [
                'slug' => $profile->slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Le repliement des lignes longues, réservé au base64 de la photo.
     *
     * La RFC plafonne une ligne à 75 octets et poursuit la suivante après une
     * espace. Les lecteurs l'appliquent surtout à la photo, dont la ligne
     * atteint des centaines de milliers de caractères : non repliée, elle en
     * fait renoncer plusieurs.
     *
     * LES LIGNES DE TEXTE, ELLES, RESTENT ENTIÈRES. Replier de l'UTF-8 à
     * l'octet couperait « Thiès » ou « Aïssatou » au milieu d'un caractère et
     * produirait un nom illisible. Tous les lecteurs acceptent une ligne de
     * texte longue ; aucun n'accepte un caractère tronqué. Le base64, lui, est
     * de l'ASCII pur — le découper est sans risque.
     */
    private function plier(string $ligne): string
    {
        $morceaux = str_split($ligne, 74);

        return implode("\r\n ", $morceaux);
    }

    /**
     * L'échappement imposé par la RFC : barre oblique inverse, point-virgule,
     * virgule et sauts de ligne.
     *
     * L'ORDRE COMPTE. La barre oblique inverse passe en premier, sans quoi on
     * échapperait ensuite les barres que l'on vient soi-même d'ajouter.
     *
     * Sans cela, « Cabinet Sall, Diop & Associés » scinderait la valeur en
     * deux à la virgule, et le contact s'enregistrerait sous une entreprise
     * amputée — sans qu'aucune erreur soit levée nulle part.
     */
    private function echapper(?string $valeur): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
            trim((string) $valeur)
        );
    }
}
