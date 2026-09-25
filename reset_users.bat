@echo off
cd /d "%~dp0ai_assistant\laravel"
echo ========================================
echo   Reset des utilisateurs (migrate:fresh --seed)
echo ========================================
echo.
php artisan migrate:fresh --seed
echo.
if %errorlevel% equ 0 (
    echo [OK] Utilisateurs mis a jour !
) else (
    echo [ERREUR] Verifie que PHP est installe et que le dossier laravel/ existe.
    echo          Lance d abord install.bat si ce n est pas deja fait.
)
echo.
pause
