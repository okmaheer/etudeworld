@echo off
cd /d "%~dp0"
echo Updating the database (only changes something if there is a new update)...
call php artisan migrate --force
if errorlevel 1 (
  echo.
  echo Database update failed. Please copy the messages above and send them to Claude.
  pause
  exit /b 1
)
echo.
echo Etude World is running at http://127.0.0.1:8000   (Admin: http://127.0.0.1:8000/admin)
echo Close this window to stop the website.
start "" http://127.0.0.1:8000
call php artisan serve
