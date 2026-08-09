@echo off
REM ---------------------------------------------------------------------------
REM Identite Pro - lance l'environnement de developpement d'un seul coup.
REM Trois fenetres : serveur Laravel, worker de file, Vite.
REM Les e-mails partent reellement via Gmail SMTP (voir docs/EMAIL.md).
REM Usage : double-clic, ou « ./dev.bat » depuis Git Bash.
REM ---------------------------------------------------------------------------

echo Demarrage de l'environnement Identite Pro...

start "Laravel - serveur" cmd /k "php artisan serve"
start "Laravel - worker file" cmd /k "php artisan queue:work database --queue=mail,default"
start "Vite - assets" cmd /k "npm run dev"

echo.
echo Application  : http://127.0.0.1:8000
echo Etat systeme : http://127.0.0.1:8000/admin/etat-systeme
echo.
echo Les trois processus tournent dans des fenetres separees.
echo Fermez-les pour arreter l'environnement.
pause
