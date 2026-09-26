# Assistant PPS — conteneur de demonstration
#
# Rassemble FastAPI (Python) et Laravel (PHP) dans UN SEUL service :
# Laravel est l'interface publique, FastAPI reste en interne et n'est
# jamais expose dehors.
#
# Construction (depuis la racine du depot — ou depuis GitHub, ce Dockerfile
# est a la racine pour etre detecte automatiquement) :
#     docker build -t assistant-pps .
#
# Execution :
#     docker run -p 8080:8080 -e APP_URL=https://mon-demo.example assistant-pps
#
# APP_URL doit pointer vers l'URL publique reelle une fois en ligne.

# composer fournit l'outil en /usr/bin/composer ; on ne fait que le copier.
FROM composer:2 AS composer-bin

# php:8.3-cli : l'interface est servie par le serveur PHP integre
# (php artisan serve), php-fpm ne serait pas utilise.
FROM php:8.3-cli

LABEL org.opencontainers.image.title="Assistant PPS" \
      org.opencontainers.image.description="Dashboard qualifications — FastAPI + Laravel"

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer

# --- systeme -----------------------------------------------------------------
# python3        : API FastAPI
# libsqlite3-dev : extension pdo_sqlite de PHP (base Laravel)
# libonig-dev    : extension mbstring
# $PHPIZE_DEPS   : outils de compilation exigees par docker-php-ext-install
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        python3 \
        python3-venv \
        curl \
        ca-certificates \
        libsqlite3-dev \
        libonig-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_sqlite mbstring \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# --- dependances Python -------------------------------------------------------
# Un venv evite l'erreur "externally-managed-environment" (PEP 668) de Debian.
# requirements-serve.txt : uniquement ce qui sert a repondre aux requetes
# (pandas/numpy servent a la migration Excel, impossible en ligne de toute facon).
COPY ai_assistant/backend/requirements-serve.txt backend/requirements-serve.txt
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --no-cache-dir --disable-pip-version-check \
        -r backend/requirements-serve.txt

ENV PATH="/opt/venv/bin:$PATH" \
    PYTHONUNBUFFERED=1

# --- application --------------------------------------------------------------
# vendor/ et .env sont exclus par .dockerignore : les paquets sont
# reconstruits sous Linux plutot que recopies depuis Windows.
COPY ai_assistant/ /app/

# --no-scripts : le script "post-autoload-dump" execute artisan
# (package:discover) alors que laravel/.env n'existe pas encore a ce stade
# — il est cree au demarrage par docker-entrypoint.sh. Laravel decouvre les
# paquets automatiquement au premier chargement si le manifeste est absent.
RUN composer install \
        --no-dev \
        --no-scripts \
        --working-dir=laravel \
        --no-interaction \
        --no-progress \
        --optimize-autoloader

# --- permissions ---------------------------------------------------------------
# Le conteneur tourne en non-root (www-data) et doit pouvoir ecrire la base
# SQLite, les logs et le repertoire de sortie.
RUN mkdir -p \
        laravel/database \
        laravel/storage/framework/cache/data \
        laravel/storage/framework/sessions \
        laravel/storage/framework/views \
        laravel/storage/logs \
        laravel/bootstrap/cache \
        backend/data \
    && chown -R www-data:www-data /app \
    && rm -f laravel/.env \
    && sed -i 's/\r$//' /app/docker-entrypoint.sh \
    && chmod +x /app/docker-entrypoint.sh

# Rien de confidentiel ne doit survivre a la construction.
RUN find /app -name '.env' -not -name '.env.docker' -not -name '.env.example' -delete \
    && find /app \( -name '*.sqlite' -o -name '*.db' -o -name '*.xlsx' \) -delete 2>/dev/null || true

USER www-data

ENV PORT=8080 \
    API_PORT=8010 \
    APP_DEBUG=false

EXPOSE 8080

ENTRYPOINT ["/app/docker-entrypoint.sh"]
