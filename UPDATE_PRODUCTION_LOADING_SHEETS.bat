@echo off
REM Production Loading Sheets Update Script for Windows
REM This script updates the production system with the loading sheet fixes

echo ==========================================
echo Production Loading Sheets Update
echo ==========================================
echo.

REM Step 1: Backup Database
echo Step 1: Backing up database...
php artisan backup:run --only-db
if %errorlevel% neq 0 (
    echo [ERROR] Database backup failed
    pause
    exit /b 1
)
echo [OK] Database backup complete
echo.

REM Step 2: Pull latest code
echo Step 2: Pulling latest code...
git pull origin main
if %errorlevel% neq 0 (
    echo [ERROR] Git pull failed
    pause
    exit /b 1
)
echo [OK] Code updated
echo.

REM Step 3: Install/Update dependencies
echo Step 3: Checking dependencies...
composer install --no-dev --optimize-autoloader
echo [OK] Dependencies checked
echo.

REM Step 4: Run migrations
echo Step 4: Running migrations...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo [ERROR] Migrations failed
    pause
    exit /b 1
)
echo [OK] Migrations complete
echo.

REM Step 5: Clear caches
echo Step 5: Clearing caches...
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo [OK] Caches cleared
echo.

REM Step 6: Optimize for production
echo Step 6: Optimizing for production...
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo [OK] Optimization complete
echo.

REM Step 7: Restart queue workers
echo Step 7: Restarting queue workers...
php artisan queue:restart
echo [OK] Queue workers restarted
echo.

REM Step 8: Regenerate loading sheets (dry run first)
echo Step 8: Testing loading sheet regeneration (dry run)...
php artisan loading-sheets:regenerate --days=7 --dry-run
echo.

set /p REGEN="Do you want to regenerate loading sheets for the last 7 days? (y/n): "
if /i "%REGEN%"=="y" (
    echo Regenerating loading sheets...
    php artisan loading-sheets:regenerate --days=7 --force
    if %errorlevel% neq 0 (
        echo [ERROR] Loading sheet regeneration failed
    ) else (
        echo [OK] Loading sheets regenerated
    )
) else (
    echo Skipping loading sheet regeneration
)
echo.

REM Step 9: Verify
echo Step 9: Running verification checks...
echo.
echo Checking for duplicate loading sheets...
php artisan tinker --execute="$duplicates = DB::select('SELECT shift_id, bin_location_id, COUNT(*) as count FROM salesman_shift_store_dispatches GROUP BY shift_id, bin_location_id HAVING count > 1'); echo 'Duplicate dispatches found: ' . count($duplicates); if (count($duplicates) > 0) { echo PHP_EOL . 'WARNING: Duplicates still exist!' . PHP_EOL; print_r($duplicates); } else { echo PHP_EOL . 'OK: No duplicates found' . PHP_EOL; }"
echo.

REM Final summary
echo ==========================================
echo Production Update Complete!
echo ==========================================
echo.
echo Next steps:
echo 1. Monitor logs: tail -f storage/logs/laravel.log ^| grep "Loading Sheet"
echo 2. Check queue: php artisan queue:work --once
echo 3. Test creating a new shift and closing it
echo 4. Verify loading sheets are generated correctly
echo.
echo Debug endpoints available:
echo - /admin/salesman-orders/debug-loading-sheets
echo - /admin/salesman-orders/generate-loading-sheets/{shiftId}
echo.

pause
