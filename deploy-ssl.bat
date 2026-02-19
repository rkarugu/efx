@echo off
REM SSL Deployment Script for Laravel Application (Windows)
REM Usage: deploy-ssl.bat [production|development]

set MODE=%1
if "%MODE%"=="" set MODE=production

echo 🚀 Deploying SSL configuration for %MODE% environment...

if "%MODE%"=="production" (
    echo 📝 Switching to production SSL configuration...
    
    REM Backup current .htaccess
    if exist .htaccess (
        for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c%%a%%b)
        for /f "tokens=1-2 delims=/:" %%a in ('time /t') do (set mytime=%%a%%b)
        copy .htaccess .htaccess.backup.%mydate%_%mytime%
        echo ✅ Backed up current .htaccess
    )
    
    REM Copy production .htaccess
    copy .htaccess.production .htaccess
    echo ✅ Applied production .htaccess with SSL enforcement
    
    REM Update environment (basic replacement)
    powershell -Command "(gc .env) -replace 'APP_ENV=local', 'APP_ENV=production' | Out-File -encoding ASCII .env"
    powershell -Command "(gc .env) -replace 'APP_DEBUG=true', 'APP_DEBUG=false' | Out-File -encoding ASCII .env"
    echo ✅ Updated environment to production
    
    REM Clear Laravel caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    echo ✅ Cleared Laravel caches
    
    REM Optimize for production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo ✅ Optimized for production
    
    echo 🎉 Production SSL deployment complete!
    echo 📋 Next steps:
    echo    1. Ensure your domain points to this server
    echo    2. Install SSL certificate (see SSL_SETUP_GUIDE.md)
    echo    3. Test your site at https://yourdomain.com
    
) else if "%MODE%"=="development" (
    echo 🔧 Switching to development configuration...
    
    REM Update environment
    powershell -Command "(gc .env) -replace 'APP_ENV=production', 'APP_ENV=local' | Out-File -encoding ASCII .env"
    powershell -Command "(gc .env) -replace 'APP_DEBUG=false', 'APP_DEBUG=true' | Out-File -encoding ASCII .env"
    echo ✅ Updated environment to development
    
    REM Clear caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    echo ✅ Cleared caches
    
    echo 🎉 Development configuration restored!
    
) else (
    echo ❌ Invalid mode. Use 'production' or 'development'
    exit /b 1
)

echo 🔍 Current configuration:
findstr "APP_ENV" .env
findstr "APP_DEBUG" .env
findstr "APP_URL" .env
