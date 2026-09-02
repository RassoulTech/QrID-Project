// =============================================================================
// PARTAGE — la feuille du système, quand elle existe.
//
// CE QUE CELA REMPLACE
// Le bouton « Partager » ouvrait une surcouche qui ne montrait QU'UN QR CODE.
// Faire scanner un code suppose pourtant que l'autre personne soit en face de
// vous : c'est le cas d'usage le plus étroit. Le geste le plus fréquent, après
// un scan, est d'envoyer le lien dans une conversation.
//
// DÉGRADATION SANS JAVASCRIPT
// La feuille s'ouvre par `:target`, sans une ligne de script. Elle contient
// déjà le QR Code et l'adresse écrite en clair, sélectionnable à la main. Ce
// module n'AJOUTE que deux choses :
//
//   · le bouton de partage système, révélé seulement si `navigator.share`
//     existe — un bouton qui ne ferait rien sur un navigateur de bureau
//     serait pire que pas de bouton ;
//   · la copie en un geste, là où il fallait sélectionner puis copier.
//
// POURQUOI PAS `navigator.canShare` SEUL
// `canShare` n'existe pas sur toutes les implémentations qui portent `share`.
// On teste donc `share`, et l'on n'appelle `canShare` que pour les fichiers,
// où il est nécessaire et où il est présent partout où les fichiers le sont.
// =============================================================================

export default function partage() {
    natif();
    copier();
}

function natif() {
    const bouton = document.querySelector('[data-partage-natif]');

    if (!bouton || typeof navigator.share !== 'function') {
        return;
    }

    // Il est `hidden` dans le HTML : on ne le montre qu'une fois certain que
    // le geste aboutira.
    bouton.hidden = false;

    bouton.addEventListener('click', async () => {
        try {
            await navigator.share({
                title: bouton.dataset.titre,
                url: bouton.dataset.url,
            });
        } catch {
            /*
             | UNE ANNULATION N'EST PAS UNE ERREUR.
             |
             | Fermer la feuille système rejette la promesse avec AbortError,
             | exactement comme un vrai échec. Distinguer les deux demanderait
             | de se fier au nom de l'erreur, qui varie d'un navigateur à
             | l'autre — et dans les deux cas il n'y a rien à dire à
             | l'utilisateur : la feuille de secours est toujours ouverte
             | derrière, avec la copie et le QR.
             */
        }
    });
}

function copier() {
    const bouton = document.querySelector('[data-partage-copier]');

    if (!bouton) {
        return;
    }

    const libelle = bouton.querySelector('[data-partage-libelle]');
    const url = bouton.dataset.url;

    bouton.addEventListener('click', async () => {
        const origine = libelle.textContent;

        try {
            // API moderne, disponible uniquement en contexte sécurisé.
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                // Repli : on sélectionne l'adresse affichée, l'utilisateur
                // voit exactement ce qu'il copie.
                const cible = bouton.querySelector('.pubc__partage-url');
                const plage = document.createRange();
                plage.selectNodeContents(cible);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(plage);
                document.execCommand('copy');
            }

            libelle.textContent = bouton.dataset.fait;
            bouton.setAttribute('aria-live', 'polite');
            setTimeout(() => { libelle.textContent = origine; }, 2000);
        } catch {
            // Échec : l'adresse reste affichée et sélectionnable. Rien n'est
            // perdu, et un message d'erreur n'apprendrait rien.
        }
    });
}
