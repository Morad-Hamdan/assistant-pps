@echo off
title Assistant - Installation complete
cd /d "%~dp0ai_assistant"

echo ============================================
echo    Assistant — Installation automatique
echo ============================================
echo.

:: ── 1. PHP via Winget ──
echo [1/6] Verification de PHP...
where php >nul 2>&1
if errorlevel 1 (
    echo [..] PHP introuvable. Installation via Winget...
    winget install PHP.PHP.8.3 -h --accept-source-agreements >nul 2>&1
    if errorlevel 1 (
        echo [ERREUR] Impossible d'installer PHP.
        echo   Installe-le manuellement : winget install PHP.PHP.8.3
        pause
        exit /b 1
    )
    echo [OK] PHP installe.
) else (
    echo [OK] PHP deja installe.
)

:: ── 2. Python via Winget ──
echo [2/6] Verification de Python...
where python >nul 2>&1
if errorlevel 1 (
    echo [..] Python introuvable. Installation via Winget...
    winget install Python.Python.3.12 -h --accept-source-agreements >nul 2>&1
    if errorlevel 1 (
        echo [ERREUR] Impossible d'installer Python.
        echo   Installe-le manuellement : winget install Python.Python.3.12
        pause
        exit /b 1
    )
    echo [OK] Python installe.
) else (
    echo [OK] Python deja installe.
)

:: ── 3. Environnement virtuel Python ──
echo [3/6] Environnement virtuel Python...
if exist "venv" (
    echo [..] venv deja existant.
) else (
    python -m venv venv
    if errorlevel 1 (
        echo [ERREUR] Impossible de creer venv.
        pause
        exit /b 1
    )
    echo [OK] venv cree.
)

echo [..] Installation des packages Python...
call venv\Scripts\activate.bat
pip install -r backend\requirements.txt --quiet
if errorlevel 1 (
    echo [ERREUR] pip install a echoue.
    pause
    exit /b 1
)
echo [OK] Packages Python installes.

:: ── 4. Composer ──
echo [4/6] Verification de Composer...
set COMPOSER_CMD=composer
where composer >nul 2>&1
if errorlevel 1 (
    echo [..] Composer introuvable. Telechargement dans laravel/...
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

:: ── 5. Dependances Laravel ──
echo [5/6] Installation des dependances Laravel...
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

:: Generer APP_KEY si absente
php artisan key:generate --force >nul 2>&1
echo [OK] APP_KEY generee.

:: ── 6. Base de donnees ──
echo [6/6] Migration et seed de la base utilisateurs...
if not exist "database\database.sqlite" (
    php artisan migrate --seed --force >nul 2>&1
    if errorlevel 1 (
        echo [ERREUR] Migration echouee.
        pause
        exit /b 1
    )
    echo [OK] Base creee et seedee.
) else (
    echo [..] Base deja existante.
)

echo.
echo ============================================
echo    Installation terminee !
echo ============================================
echo.
echo   Modifie VOTRE_IP dans .env puis lance :
echo   lancer_morad.bat
echo.
pause
