#!/bin/sh
# =============================================================================
# QrID — démarrage du conteneur
#
# CE QUI SE PASSE ICI NE PEUT PAS SE PASSER À LA CONSTRUCTION. Au moment du
# `docker build`, aucune variable d'environnement de Render n'existe : ni
# APP_KEY, ni les identifiants de la base. Mettre en cache la configuration à
# ce moment-là graverait des valeurs vides dans l'image.
#
# Tout ce qui dépend de l'environnement se fait donc AU DÉMARRAGE, ici.
# =============================================================================

# -e : la moindre commande en échec arrête le script.
# -u : une variable non définie est une erreur, pas une chaîne vide.
#
# Sans ces deux options, une migration ratée passerait inaperçue : le conteneur
# démarrerait, servirait des pages, et planterait à la première requête touchant
# une table absente.
set -eu

echo "─────────────────────────────────────────────"
echo " QrID — démarrage"
echo "─────────────────────────────────────────────"

cd /var/www

# -----------------------------------------------------------------------------
# 1. VÉRIFICATIONS QUI DOIVENT ÉCHOUER FORT
# -----------------------------------------------------------------------------
# Une variable manquante doit arrêter le démarrage avec un message clair, pas
# produire une page blanche que l'on cherchera une heure dans les journaux.

if [ -z "${APP_KEY:-}" ]; then
    echo "ERREUR — APP_KEY est absente."
    echo ""
    echo "  Sans elle, aucune session ni aucun cookie ne peut être déchiffré."
    echo "  Générez-la en local :   php artisan key:generate --show"
    echo "  Puis copiez la valeur complète (base64:...) dans les variables"
    echo "  d'environnement de Render."
    exit 1
fi

if [ -z "${DB_HOST:-}" ]; then
    echo "ERREUR — DB_HOST est absente. La base de données n'est pas configurée."
    exit 1
fi

# APP_URL est GRAVÉE dans les QR Codes. Une carte imprimée avec la mauvaise
# adresse est une carte à jeter, et le PDF est déjà chez le client.
if [ -z "${APP_URL:-}" ]; then
    echo "ATTENTION — APP_URL est absente."
    echo "  Les QR Codes encoderont une adresse par défaut, probablement fausse."
fi

# -----------------------------------------------------------------------------
# 2. LE PORT IMPOSÉ PAR RENDER
# -----------------------------------------------------------------------------
# Render choisit le port et le transmet par la variable PORT. nginx ne lit pas
# les variables d'environnement : on les injecte dans son gabarit.
#
# Écrire le port en dur donnerait un conteneur qui démarre parfaitement et que
# Render déclarerait « unhealthy » sans autre explication.
PORT="${PORT:-10000}"
export PORT

# envsubst reçoit ICI la liste des variables à remplacer. Sans cet argument,
# il remplacerait aussi $uri, $query_string et $document_root — les variables
# de nginx, pas celles du shell — par des chaînes vides. La configuration
# serait syntaxiquement valide et le routage entièrement cassé.
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

echo "→ nginx écoutera sur le port ${PORT}"

# -----------------------------------------------------------------------------
# 3. DÉCOUVERTE DES PAQUETS
# -----------------------------------------------------------------------------
# Reportée depuis la construction : `composer install --no-scripts` ne l'a pas
# faite, faute d'environnement. C'est elle qui enregistre les fournisseurs de
# services des paquets (dompdf, simple-qrcode).
echo "→ découverte des paquets"
php artisan package:discover --ansi

# -----------------------------------------------------------------------------
# 4. MIGRATIONS
# -----------------------------------------------------------------------------
# --force : sans lui, Laravel demande confirmation en production et le
# conteneur resterait bloqué sur une question que personne ne lira.
#
# Une migration en échec ARRÊTE le démarrage (set -e). C'est voulu : mieux vaut
# un conteneur qui refuse de démarrer qu'un conteneur qui sert des pages
# cassées sur un schéma incomplet. Render conserve alors la version précédente.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "→ migrations"
    php artisan migrate --force --ansi
else
    echo "→ migrations ignorées (RUN_MIGRATIONS=false)"
fi

# -----------------------------------------------------------------------------
# 5. LIEN DE STOCKAGE
# -----------------------------------------------------------------------------
# public/storage → storage/app/public. Sans lui, aucune photo de profil ni
# aucun QR Code mis en cache n'est servi : les images renvoient 404.
#
# À refaire à chaque démarrage : le lien vit dans le conteneur, qui est neuf.
echo "→ lien de stockage"
php artisan storage:link --force --ansi || true

# -----------------------------------------------------------------------------
# 6. MISES EN CACHE
# -----------------------------------------------------------------------------
# Après les migrations, jamais avant : la mise en cache de la configuration
# ouvre une connexion à la base sur certains pilotes.
#
# On efface d'abord. Un cache hérité de la construction — ou d'un démarrage
# précédent avec d'autres variables — servirait des valeurs périmées.
echo "→ caches"
php artisan optimize:clear --ansi
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi
php artisan event:cache --ansi

# -----------------------------------------------------------------------------
# 7. DROITS
# -----------------------------------------------------------------------------
# php-fpm tourne sous www-data et doit pouvoir écrire les journaux et les vues
# compilées. Les caches créés à l'étape précédente l'ont été par root.
chown -R www-data:www-data storage bootstrap/cache

# -----------------------------------------------------------------------------
# 8. DÉMARRAGE
# -----------------------------------------------------------------------------
# `exec` REMPLACE ce script par supervisord au lieu de le lancer comme fils.
# C'est ce qui permet à supervisord de recevoir directement les signaux
# d'arrêt de Docker : sans exec, un déploiement couperait les requêtes en
# cours au lieu de les laisser finir.
echo "─────────────────────────────────────────────"
echo " Prêt."
echo "─────────────────────────────────────────────"

exec supervisord -c /etc/supervisord.conf
