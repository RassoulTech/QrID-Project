import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Un seul point d'entrée JS : app.js importe ses modules.
            // Vite émet un <script type="module">, donc chargé en DEFER
            // par défaut — le HTML n'attend jamais le script.
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
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
