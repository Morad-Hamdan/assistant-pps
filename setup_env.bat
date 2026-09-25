@echo off
title Assistant - Configuration de l'environnement
cd /d "%~dp0ai_assistant"

echo ============================================
echo    Assistant — Configuration env.
echo ============================================
echo.

:: ── 1. Environnement virtuel Python ──
echo [1/4] Environnement virtuel Python...
if exist "venv" (
    echo [..] venv deja existant.
) else (
    python -m venv venv
    if errorlevel 1 (
        echo [ERREUR] python -m venv a echoue.
        pause
        exit /b 1
    )
    echo [OK] venv cree.
)

echo [..] Installation des packages...
call venv\Scripts\activate.bat
pip install -r backend\requirements.txt --quiet
if errorlevel 1 (
    echo [ERREUR] pip install a echoue.
    pause
    exit /b 1
)
echo [OK] Packages installes.

:: ── 2. Composer ──
echo [2/4] Verification de Composer...
set COMPOSER_CMD=composer
where composer >nul 2>&1
if errorlevel 1 (
    echo [..] Composer introuvable. Telechargement...
    cd /d "%~dp0ai_assistant\laravel"
    powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/installer' -OutFile 'composer-setup.php' -UseBasicParsing" >nul 2>&1
    php composer-setup.php --quiet >nul 2>&1
    del composer-setup.php >nul 2>&1
    if not exist "composer.phar" (
        echo [ERREUR] Telechargement de Composer echoue.
        pause
        exit /b 1
    )
    echo [OK] Composer.phar telecharge.
    set COMPOSER_CMD=php composer.phar
) else (
    echo [OK] Composer deja installe.
    cd /d "%~dp0ai_assistant\laravel"
)

:: ── 3. Dependances Laravel ──
echo [3/4] Installation des dependances Laravel...
if exist "vendor" (
    echo [..] vendor deja existant.
) else (
    %COMPOSER_CMD% install --no-interaction --quiet
    if errorlevel 1 (
        echo [ERREUR] composer install a echoue.
        pause
        exit /b 1
    )
    echo [OK] vendor installe.
)

:: Generer APP_KEY
php artisan key:generate --force >nul 2>&1
echo [OK] APP_KEY generee.

:: ── 4. Base de donnees ──
echo [4/4] Migration et seed...
if not exist "database\database.sqlite" (
    php artisan migrate --seed --force >nul 2>&1
    if errorlevel 1 (
        echo [ERREUR] Migration echouee.
        pause
        exit /b 1
    )
    echo [OK] Base initialisee.
) else (
    echo [..] Base deja existante.
)

echo.
echo ============================================
echo    Environnement pret !
echo    Lance lancer_morad.bat pour demarrer.
echo ============================================
echo.
pause
