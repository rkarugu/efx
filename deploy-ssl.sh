#!/bin/bash

# SSL Deployment Script for Laravel Application
# Usage: ./deploy-ssl.sh [production|development]

MODE=${1:-production}

echo "🚀 Deploying SSL configuration for $MODE environment..."

if [ "$MODE" = "production" ]; then
    echo "📝 Switching to production SSL configuration..."
    
    # Backup current .htaccess
    if [ -f .htaccess ]; then
        cp .htaccess .htaccess.backup.$(date +%Y%m%d_%H%M%S)
        echo "✅ Backed up current .htaccess"
    fi
    
    # Copy production .htaccess
    cp .htaccess.production .htaccess
    echo "✅ Applied production .htaccess with SSL enforcement"
    
    # Update environment
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    echo "✅ Updated environment to production"
    
    # Clear Laravel caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    echo "✅ Cleared Laravel caches"
    
    # Optimize for production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo "✅ Optimized for production"
    
    echo "🎉 Production SSL deployment complete!"
    echo "📋 Next steps:"
    echo "   1. Ensure your domain points to this server"
    echo "   2. Install SSL certificate (see SSL_SETUP_GUIDE.md)"
    echo "   3. Test your site at https://yourdomain.com"
    
elif [ "$MODE" = "development" ]; then
    echo "🔧 Switching to development configuration..."
    
    # Restore development .htaccess (without HTTPS enforcement)
    git checkout .htaccess 2>/dev/null || echo "⚠️  Could not restore .htaccess from git"
    
    # Update environment
    sed -i 's/APP_ENV=production/APP_ENV=local/' .env
    sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
    echo "✅ Updated environment to development"
    
    # Clear caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    echo "✅ Cleared caches"
    
    echo "🎉 Development configuration restored!"
    
else
    echo "❌ Invalid mode. Use 'production' or 'development'"
    exit 1
fi

echo "🔍 Current configuration:"
echo "   APP_ENV: $(grep APP_ENV .env | cut -d'=' -f2)"
echo "   APP_DEBUG: $(grep APP_DEBUG .env | cut -d'=' -f2)"
echo "   APP_URL: $(grep APP_URL .env | cut -d'=' -f2)"
