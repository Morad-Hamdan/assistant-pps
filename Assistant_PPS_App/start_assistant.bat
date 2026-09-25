@echo off
title Assistant
cd /d "%~dp0"

set PHP_DIR=%LocalAppData%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe

:: Detection automatique de l'IP reseau
for /f "tokens=*" %%a in ('powershell -NoProfile -Command "try{((Get-NetIPAddress -AddressFamily IPv4 | Where-Object{$_.InterfaceAlias -notlike '*Loopback*' -and $_.PrefixOrigin -ne 'WellKnown'}).IPAddress | Select-Object -First 1)}catch{'127.0.0.1'}"') do set IP=%%a

echo ============================================
echo    Assistant Decisionnel
echo ============================================
echo.

:: Demarrage FastAPI
echo [1/4] Demarrage du serveur FastAPI (port 8000)...
start "" /B uvicorn backend.main:app --reload --port 8000 --host 0.0.0.0
timeout /t 5 >nul

:: Demarrage Laravel
echo [2/4] Demarrage de l'interface Laravel (port 8080)...
set PATH=%PHP_DIR%;%PATH%
cd laravel
start "" /B php artisan serve --host=0.0.0.0 --port=8080
cd ..
timeout /t 7 >nul

:: Ouverture navigateur sur Laravel (IP reseau locale)
echo [3/4] Ouverture de l'interface...
start http://%IP%:8080

echo.
echo ============================================
echo    Morad est pret !
echo    Interface locale  : http://127.0.0.1:8080
echo    Interface reseau  : http://%IP%:8080
echo    API               : http://%IP%:8000/docs
echo ============================================
echo    Partager l'interface reseau avec les autres PC.
echo ============================================
echo    Appuyez sur une touche pour arreter.
echo ============================================
pause >nul

:: Arret
echo [4/4] Arret des serveurs...
taskkill /f /im uvicorn.exe >nul 2>&1
taskkill /f /im php.exe >nul 2>&1
echo [OK] Serveurs arretes.

exit
