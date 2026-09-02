# =============================================================================
# QrID — image de production
#
# TROIS ÉTAPES, et c'est le point important : Node et Composer ne servent qu'à
# CONSTRUIRE. Ils pèsent plusieurs centaines de mégaoctets et n'ont rien à
# faire dans l'image qui tourne. Chaque étape ne transmet à la suivante que
# son résultat, jamais son outillage.
#
# Ce fichier est le procès-verbal des gestes qu'il a fallu faire à la main en
# développement : installer PHP, activer gd, installer Composer et Node. Ces
# gestes n'étaient écrits nulle part ; ils le sont désormais.
# =============================================================================


# -----------------------------------------------------------------------------
# ÉTAPE 1 — Les assets (CSS et JS)
# -----------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

# Les manifestes d'abord, le code ensuite. Docker met en cache chaque
# instruction : tant que package-lock.json ne change pas, l'installation des
# dépendances n'est pas rejouée. Copier tout le projet d'un coup ferait
# réinstaller 200 Mo de node_modules à chaque virgule changée dans une vue.
COPY package.json package-lock.json ./

# `npm ci` et non `npm install` : il installe EXACTEMENT le contenu du
# lock-file et échoue si celui-ci diverge de package.json. En production, on
# veut cette rigidité — pas une résolution de version qui varie d'un
# déploiement à l'autre.
RUN npm ci

# Uniquement ce dont Vite a besoin.
COPY vite.config.js ./
COPY resources/ ./resources/

RUN npm run build


# -----------------------------------------------------------------------------
# ÉTAPE 2 — Les dépendances PHP
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# --no-dev      : PHPUnit et Pint n'ont rien à faire en production.
# --no-scripts  : les scripts post-install appellent `artisan package:discover`,
#                 qui démarre l'application. Or à cette étape, ni le code
#                 complet ni les variables d'environnement ne sont là. La
#                 découverte des paquets est faite au démarrage du conteneur,
#                 par l'entrypoint.
# --ignore-platform-reqs : l'image Composer n'a pas gd ni pdo_mysql ; l'image
#                 finale, si. On vérifie les extensions là où elles comptent.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs

# Le reste du code, puis l'autoloader optimisé — il a besoin de voir toutes
# les classes de app/ pour construire sa table de correspondance.
COPY . .

# --no-scripts À NOUVEAU, et pour une raison précise : composer.json déclare
#   "post-autoload-dump": ["… ComposerScripts::postAutoloadDump",
#                          "@php artisan package:discover --ansi"]
# `dump-autoload` déclencherait donc `package:discover`, qui démarre
# l'application — sans APP_KEY ni base de données à cette étape. La
# construction échouerait.
#
# --optimize SANS --classmap-authoritative : le mode « autoritaire » interdit
# de charger toute classe absente de la table. C'est plus rapide, mais cela
# casse les paquets qui créent des classes à l'exécution. Le gain ne vaut pas
# ce risque sur une image qu'on ne peut pas essayer en local.
RUN composer dump-autoload --no-dev --optimize --no-scripts


# -----------------------------------------------------------------------------
# ÉTAPE 3 — L'image finale
# -----------------------------------------------------------------------------
FROM php:8.3-fpm-alpine

# --- Paquets système ---------------------------------------------------------
# nginx      : sert les fichiers statiques et transmet le PHP à php-fpm
# supervisor : un conteneur ne lance qu'UN processus ; il en faut deux
#              (nginx et php-fpm), d'où ce chef d'orchestre
# gettext    : fournit envsubst, qui injecte le port imposé par Render
#              dans la configuration nginx au démarrage
# mariadb-client : fournit mysqldump, dont app:sauvegarder a besoin. Il
#              MANQUAIT, et la commande echouait a chaque passage avec un
#              code 1 opaque. Aucun planificateur ne tournant, personne ne
#              l'avait jamais vue echouer : la panne est apparue le jour
#              meme de la mise en service du planificateur.
RUN apk add --no-cache \
        nginx \
        supervisor \
        gettext \
        mariadb-client \
        libpng \
        libjpeg-turbo \
        freetype \
        libzip

# NOTE : `icu` n'est PAS installé, donc pas d'extension `intl`. Vérifié dans
# le code — aucun usage de Number::, NumberFormatter ou IntlDateFormatter. Les
# montants passent par number_format() et les dates par Carbon, qui embarque
# ses propres traductions françaises. C'est une trentaine de mégaoctets en
# moins. À réintroduire le jour où `intl` deviendra nécessaire.

# --- Extensions PHP ----------------------------------------------------------
# LA LIGNE `gd` EST CELLE QUI COMPTE. En développement, il a fallu ouvrir
# C:\xampp\php\php.ini et décommenter `extension=gd` — une ligne écrite nulle
# part, absente du dépôt, et dont l'oubli ne provoquait pas d'erreur : le
# redimensionnement des photos se dégradait en silence. Elle est ici.
#
#   gd        : redimensionnement des photos de profil, QR Codes en PNG
#   pdo_mysql : la base de données
#   bcmath    : calculs sur les montants
#   exif      : orientation des photos prises au téléphone
#   zip       : archives (dompdf, Composer)
#   opcache   : compile le PHP une fois au lieu de le relire à chaque requête
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        pdo_mysql \
        bcmath \
        exif \
        zip \
        opcache \
    && apk del .build-deps

# --- Configuration ------------------------------------------------------------
COPY docker/php.ini          /usr/local/etc/php/conf.d/qrid.ini
COPY docker/nginx.conf       /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint

# « zzz » pour être chargé EN DERNIER : php-fpm lit son dossier de
# configuration par ordre alphabétique, et la dernière valeur l'emporte.
# Ce fichier impose clear_env = no — sans lui, aucune variable de Render
# n'atteindrait PHP. Voir le commentaire détaillé dans le fichier.
COPY docker/php-fpm.conf     /usr/local/etc/php-fpm.d/zzz-qrid.conf

RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www

# --- Le code -------------------------------------------------------------------
# On récupère les RÉSULTATS des deux premières étapes. Ni Node, ni npm, ni
# node_modules, ni Composer n'entrent dans cette image.
COPY --from=vendor /app /var/www
COPY --from=assets /app/public/build /var/www/public/build

# --- Droits ---------------------------------------------------------------------
# php-fpm tourne sous `www-data` dans l'image officielle. Il doit pouvoir
# écrire dans storage/ (journaux, vues compilées) et bootstrap/cache/ (caches
# de configuration et de routes).
#
# LES CHEMINS SONT ÉCRITS EN CLAIR, sans accolades. `mkdir -p a/{b,c}` est une
# expansion propre à bash ; le shell d'Alpine est `ash` (busybox), qui ne la
# garantit pas. Là où bash crée trois dossiers, ash en créerait UN SEUL, nommé
# littéralement « {cache,sessions,views} ». L'image se construirait sans
# erreur et Laravel échouerait à la première écriture de session.
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    # nginx écrit les corps de requête volumineux dans des fichiers
    # temporaires. Au-delà de client_body_buffer_size, une photo téléversée
    # y passe : sans ces droits, le téléversement échoue en 500 alors que
    # tout le reste fonctionne.
    && chown -R www-data:www-data /var/lib/nginx

# Documentaire : Render impose sa propre valeur via la variable PORT, que
# l'entrypoint injecte dans la configuration nginx.
EXPOSE 10000

ENTRYPOINT ["entrypoint"]
