@echo off
title Assistant - Mise a jour SQLite depuis Excel
cd /d "%~dp0"

echo ============================================
echo   Mise a jour SQLite depuis recap.xlsx
echo ============================================
echo.

:: Chemin racine du projet (ai_assistant)
set APP_DIR=%~dp0..\..

:: Detecter Python (venv > systeme)
set PYTHON_CMD=
if exist "%APP_DIR%\venv\Scripts\python.exe" (
    set PYTHON_CMD=%APP_DIR%\venv\Scripts\python.exe
) else (
    where python >nul 2>&1
    if not errorlevel 1 set PYTHON_CMD=python
)

if "%PYTHON_CMD%"=="" (
    echo [ERREUR] Python introuvable.
    echo Lance d'abord install.bat ou setup_env.bat
    pause
    exit /b 1
)

echo [1/2] Migration Excel vers SQLite...
%PYTHON_CMD% "%APP_DIR%\backend\scripts\migrate_excel_to_sqlite.py"
if errorlevel 1 (
    echo [ERREUR] La migration a echoue.
    pause
    exit /b 1
)

echo [2/2] OK - Donnees synchronisees !
echo.
echo   recap.xlsx     -> source (modifications manuelles)
echo   hr_database.db -> base SQLite mise a jour
echo.
echo Relance lancer_morad.bat pour voir les changements.
echo.
pause
