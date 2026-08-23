import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            /*
             | DEUX POINTS D'ENTRÉE JS, ET LE SECOND EST VOLONTAIREMENT NU.
             |
             | app.js embarque Bootstrap et une douzaine de modules : c'est le
             | script de l'application, pour qui est connecté.
             |
             | carte-publique.js ne contient qu'une chose — l'ouverture des
             | contacts natifs — parce que la page publique s'ouvre après un
             | scan, sur le téléphone d'un inconnu, souvent en 3G. Lui faire
             | télécharger le tableau de bord serait absurde.
             |
             | Vite émet des <script type="module">, donc chargés en DEFER :
             | le HTML n'attend jamais le script.
             */
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/carte-publique.js',
            ],
            refresh: true,
        }),
    ],

    build: {
        // Fichiers séparés pour le CSS et le JS : le style ne dépend
        // jamais du script.
        cssCodeSplit: true,
    },

    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 5.3 est encore écrit avec @import et les anciennes
                // fonctions de couleur : Sass en émet des centaines
                // d'avertissements que NOUS ne pouvons pas corriger (le code
                // fautif est dans node_modules). On les tait pour que le build
                // ne noie pas les avertissements venant de resources/sass.
                // À retirer quand Bootstrap passera à @use (v6).
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
            },
        },
    },
});
