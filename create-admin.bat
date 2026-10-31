@echo off
cd /d "%~dp0"
echo Create a new admin login, or reset the password of an existing one.
call php artisan admin:create
pause
