#!/bin/sh
# Assistant PPS — point d'entree du conteneur.
#
# Lance les deux services dans un meme conteneur :
#   * FastAPI   (Python)  -> interne, uniquement appele par Laravel
#   * Laravel   (PHP)     -> interface publique, sur $PORT
#
# Chaque redemarrage repart d'un jeu de donnees de demonstration propre :
# les donnees RH reelles ne sont jamais presentes dans l'image.
set -eu

cd /app

API_PORT="${API_PORT:-8010}"
PORT="${PORT:-8080}"

# Laravel appelle FastAPI via cette variable (voir NexusController).
export FASTAPI_URL="http://127.0.0.1:${API_PORT}"
export APP_URL="${APP_URL:-http://localhost:${PORT}}"
export APP_DEBUG="${APP_DEBUG:-false}"
# Le serveur PHP integre est mono-thread par defont : on ouvre quelques
# processus pour supporter quelques visiteurs simultanes. Valeur par defaut
# volontairement basse — l'offre gratuite de l'hebergeur plafonne a 512 Mo
# de RAM pour l'ensemble du conteneur.
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-2}"

echo "=================================================="
echo "  Assistant PPS — preparation"
echo "  interface : 0.0.0.0:${PORT}"
echo "  API       : ${FASTAPI_URL}"
echo "  APP_URL   : ${APP_URL}"
echo "=================================================="

# --- 1. Fichier .env -------------------------------------------------------
# laravel/.env n'est jamais copie dans l'image (voir .dockerignore) : on
# part du modele sans secret, puis on genere une cle de chiffrement fraiche.
if [ ! -f laravel/.env ]; then
    echo "[1/5] creation de laravel/.env (modele sans secret)"
    cp laravel/.env.docker laravel/.env
    php laravel/artisan key:generate --force --no-interaction >/dev/null 2>&1
else
    echo "[1/5] laravel/.env deja present"
fi

# --- 2. Base Laravel -------------------------------------------------------
# Le seeder (ADMIN / MKTANGER) n'est pas idempotent : on ne l'appelle que
# lorsque la base vient d'etre creee.
echo "[2/5] migrations Laravel"
DB=laravel/database/database.sqlite
FIRST_BOOT=0
if [ ! -f "$DB" ]; then
    touch "$DB"
    FIRST_BOOT=1
fi
php laravel/artisan migrate --force --no-interaction

if [ "$FIRST_BOOT" = "1" ]; then
    echo "       creation des comptes de demonstration"
    php laravel/artisan db:seed --force --no-interaction
fi

# --- 3. Donnees RH de demonstration ---------------------------------------
# 100 % fictives (matricules DEMO-*). --force est requis : la base contient
# deja des lignes apres un redemarrage avec volume persistant.
echo "[3/5] jeu de donnees RH de demonstration (fictif)"
python3 backend/scripts/seed_demo.py --force

# --- 4. API FastAPI --------------------------------------------------------
echo "[4/5] demarrage de l'API FastAPI"
python3 -m uvicorn backend.main:app --host 127.0.0.1 --port "${API_PORT}" &

# Laravel n'a aucun try/catch autour de ses appels Http : si l'API n'est pas
# prete, la premiere page rend une 500. On attend donc un reponse valide.
i=0
until curl -sf "http://127.0.0.1:${API_PORT}/api/employee/meta" >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 40 ]; then
        echo "       ECHEC : l'API FastAPI ne repond pas"
        exit 1
    fi
    sleep 1
done
echo "       API prete"

# --- 5. Interface Laravel --------------------------------------------------
echo "[5/5] interface Laravel demarree"
echo "=================================================="
exec php laravel/artisan serve --host=0.0.0.0 --port="${PORT}"
