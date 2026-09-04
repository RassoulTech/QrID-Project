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

# -----------------------------------------------------------------------------
# 1 ter. MARQUE-PLACES NON REMPLACÉS
# -----------------------------------------------------------------------------
# Le premier déploiement a échoué exactement ainsi : DB_HOST valait la chaîne
# « COLLEZ_ICI_L_HOTE_AIVEN », recopiée depuis un modèle de configuration.
#
# Tester seulement l'ABSENCE d'une variable ne suffit donc pas : un marque-place
# est une valeur non vide, il franchit le contrôle, et l'erreur ne sort que
# quarante lignes de trace PDO plus loin — sous la forme « Name does not
# resolve », qui envoie chercher un problème de réseau ou de pare-feu.
#
# Une variable dont la valeur ressemble à une consigne n'est pas configurée.
# On le dit en une ligne, à l'endroit où c'est réparable.
for _cle in APP_KEY APP_URL DB_HOST DB_PORT DB_PASSWORD DB_USERNAME \
            MAIL_PASSWORD ADMIN_PASSWORD; do
    eval "_valeur=\${$_cle:-}"

    case "$_valeur" in
        *COLLEZ_ICI*|*CHOISISSEZ_*|*VOTRE_*|*A_REMPLIR*|*CHANGE_ME*|*xxxxx*)
            echo "ERREUR — ${_cle} contient encore un marque-place :"
            echo ""
            echo "    ${_cle}=${_valeur}"
            echo ""
            echo "  Cette valeur vient d'un modèle de configuration et n'a pas été"
            echo "  remplacée. Ouvrez les variables d'environnement de Render et"
            echo "  saisissez la valeur réelle."
            echo ""
            echo "  Rien n'a été modifié en base : le démarrage s'arrête ici."
            exit 1
            ;;
    esac
done

# APP_URL est GRAVÉE dans les QR Codes. Une carte imprimée avec la mauvaise
# adresse est une carte à jeter, et le PDF est déjà chez le client.
if [ -z "${APP_URL:-}" ]; then
    echo "ATTENTION — APP_URL est absente."
    echo "  Les QR Codes encoderont une adresse par défaut, probablement fausse."
fi

# -----------------------------------------------------------------------------
# 1 bis. CERTIFICAT TLS DE LA BASE DE DONNÉES
# -----------------------------------------------------------------------------
# Aiven impose le chiffrement (ssl-mode=REQUIRED) et présente un certificat
# signé par SA PROPRE autorité, pas par une autorité publique. Le magasin de
# certificats du système ne peut donc pas le valider : sans le fichier ca.pem
# d'Aiven, la connexion est refusée.
#
# LE CERTIFICAT ARRIVE PAR UNE VARIABLE, PAS PAR LE DÉPÔT. Un fichier .pem
# versionné ne serait pas un secret — un certificat d'autorité est public par
# nature — mais ce dépôt-ci est public, et le fichier lierait le code à un
# projet Aiven précis. Surtout, le jour où Aiven renouvelle son autorité, il
# faudrait modifier le code et redéployer ; ici, c'est une variable à changer.
#
# Laissée vide, la connexion se fera sans vérification du certificat : elle
# reste chiffrée, mais rien ne prouve l'identité du serveur. Acceptable pour
# un essai, pas pour de vraies données de clients.
if [ -n "${DB_SSL_CA_CONTENT:-}" ]; then
    echo "→ certificat TLS de la base"
    printf '%s\n' "$DB_SSL_CA_CONTENT" > /tmp/mysql-ca.pem
    chmod 644 /tmp/mysql-ca.pem

    # Le nom lu par config/database.php (clé MYSQL_ATTR_SSL_CA).
    MYSQL_ATTR_SSL_CA=/tmp/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA
else
    echo "→ pas de certificat TLS fourni (DB_SSL_CA_CONTENT absente)"
fi

# -----------------------------------------------------------------------------
# 1 quater. FILE D'ATTENTE SANS WORKER — le piège silencieux
# -----------------------------------------------------------------------------
# Les e-mails partent par Mail::queue(). Avec un pilote autre que « sync », le
# message est écrit dans la table `jobs` et attend qu'un worker le reprenne.
#
# Or aucun worker ne tourne : le plan gratuit de Render n'exécute qu'un
# service web. Les messages s'empilent donc sans jamais partir, ET SANS LA
# MOINDRE ERREUR — la page confirme même l'envoi à l'utilisateur.
#
# C'est exactement ce qui a fait échouer la réinitialisation de mot de passe :
# la page répondait, le jeton était créé, le délai de sécurité s'armait, et
# aucun message n'arrivait.
#
# On avertit ici plutôt que d'imposer `sync` de force : le jour où un worker
# existera, `database` deviendra le bon réglage et rien ne devra être défait.
if [ "${QUEUE_CONNECTION:-sync}" != "sync" ]; then
    echo ""
    echo "  ATTENTION — QUEUE_CONNECTION vaut « ${QUEUE_CONNECTION} »."
    echo ""
    echo "    Les e-mails seront déposés dans la table jobs et n'en sortiront"
    echo "    que si un worker exécute « php artisan queue:work »."
    echo "    Sans worker, AUCUN e-mail ne partira, et aucune erreur ne le dira."
    echo ""
    echo "    Passez QUEUE_CONNECTION à « sync » tant qu'aucun worker n'existe."
    echo ""
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
# 4 bis. DONNÉES DE RÉFÉRENCE
# -----------------------------------------------------------------------------
# `migrate` crée les tables VIDES. Sans cette étape, une base neuve n'aurait
# ni formule tarifaire ni modèle de carte :
#
#   · aucune formule → l'inscription échoue, l'essai gratuit n'existant pas ;
#   · aucun modèle   → l'étape 2 du parcours de création est vide ;
#   · aucun compte administrateur.
#
# Autrement dit : l'application démarrerait, et personne ne pourrait s'en
# servir. Le défaut ne se serait vu qu'à la première inscription.
#
# TROIS GARANTIES rendent cette commande sûre à chaque démarrage :
#
#   1. les trois seeders passent par updateOrCreate — rejouables sans doublon ;
#   2. DatabaseSeeder n'appelle DemoSeeder et AdminDemoSeeder que si
#      app()->environment('local'). En production, APP_ENV vaut « production » :
#      les 60 comptes de démonstration ne peuvent PAS arriver en ligne ;
#   3. AdminSeeder s'ignore de lui-même si ADMIN_EMAIL et ADMIN_PASSWORD sont
#      absents, sans faire échouer le démarrage.
if [ "${RUN_SEEDERS:-true}" = "true" ]; then
    echo "→ données de référence (formules, modèles, administrateur)"
    php artisan db:seed --force --ansi
else
    echo "→ données de référence ignorées (RUN_SEEDERS=false)"
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

# `optimize:clear` EFFAÇAIT LES VUES COMPILÉES À LA CONSTRUCTION.
#
# Il vide tout : configuration, routes, vues, événements. C'était cohérent
# quand tout était reconstruit juste après — mais les vues et les événements
# viennent désormais de l'image, où ils ont été compilés une fois pour toutes.
# Les effacer ici obligerait à refaire au réveil un travail déjà fait, et
# `view:cache` est le poste le plus lourd du démarrage.
#
# On efface donc ce qu'on va reconstruire, et RIEN d'autre : la configuration,
# parce qu'un cache hérité de la construction porterait les variables de la
# machine de construction, et les routes pour la même raison de prudence.
php artisan config:clear --ansi
php artisan route:clear --ansi
php artisan cache:clear --ansi || true

php artisan config:cache --ansi
php artisan route:cache --ansi

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
