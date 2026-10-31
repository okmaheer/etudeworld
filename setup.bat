@echo off
cd /d "%~dp0"
echo.
echo === Checking PHP ===
php -v | findstr /b "PHP"
php -r "$m=[];foreach(['pdo_sqlite','fileinfo','mbstring','openssl','zip'] as $e){if(!extension_loaded($e))$m[]=$e;} if($m){echo 'PHP is missing these add-ons: '.implode(', ',$m).PHP_EOL; exit(1);}"
if errorlevel 1 goto :missingext
echo PHP looks good.
echo.
echo === Step 1 of 4: Installing Laravel files (can take a few minutes) ===
call composer install
if errorlevel 1 goto :fail
echo.
echo === Step 2 of 4: Settings file ===
if not exist .env copy .env.example .env >nul
findstr /r /c:"^APP_KEY=base64" .env >nul || call php artisan key:generate --ansi
echo Settings ready.
echo.
echo === Step 3 of 4: Database ===
if not exist database\database.sqlite type nul > database\database.sqlite
call php artisan migrate --force
if errorlevel 1 goto :fail
echo.
echo === Step 4 of 4: Admin login ===
set /p MAKEADMIN=Do you want to create an admin login now? (Y/N): 
if /i "%MAKEADMIN%"=="Y" call php artisan admin:create
echo.
echo Done! Double-click start.bat to open the website.
echo Admin panel: http://127.0.0.1:8000/admin
pause
exit /b 0

:missingext
echo.
echo.
echo How to switch them on (takes 1 minute):
echo   1. Open this file in Notepad:  C:\xampp\php\php.ini
echo   2. Press Ctrl+F and search for each missing name, e.g.  extension=pdo_sqlite
echo   3. Delete the ; at the start of that line, so it reads exactly:  extension=pdo_sqlite
echo   4. Save the file, then run setup.bat again.
pause
exit /b 1

:fail
echo.
echo Something went wrong. Please copy the messages above and send them to Claude.
pause
exit /b 1
