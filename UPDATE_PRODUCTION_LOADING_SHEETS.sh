#!/bin/bash

# Production Loading Sheets Update Script
# This script updates the production system with the loading sheet fixes

echo "=========================================="
echo "Production Loading Sheets Update"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Backup Database
echo -e "${YELLOW}Step 1: Backing up database...${NC}"
php artisan backup:run --only-db
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database backup complete${NC}"
else
    echo -e "${RED}✗ Database backup failed${NC}"
    exit 1
fi
echo ""

# Step 2: Pull latest code
echo -e "${YELLOW}Step 2: Pulling latest code...${NC}"
git pull origin main
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Code updated${NC}"
else
    echo -e "${RED}✗ Git pull failed${NC}"
    exit 1
fi
echo ""

# Step 3: Install/Update dependencies (if needed)
echo -e "${YELLOW}Step 3: Checking dependencies...${NC}"
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✓ Dependencies checked${NC}"
echo ""

# Step 4: Run migrations
echo -e "${YELLOW}Step 4: Running migrations...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations complete${NC}"
else
    echo -e "${RED}✗ Migrations failed${NC}"
    exit 1
fi
echo ""

# Step 5: Clear caches
echo -e "${YELLOW}Step 5: Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 6: Optimize for production
echo -e "${YELLOW}Step 6: Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Optimization complete${NC}"
echo ""

# Step 7: Restart queue workers
echo -e "${YELLOW}Step 7: Restarting queue workers...${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue workers restarted${NC}"
echo ""

# Step 8: Regenerate loading sheets (dry run first)
echo -e "${YELLOW}Step 8: Testing loading sheet regeneration (dry run)...${NC}"
php artisan loading-sheets:regenerate --days=7 --dry-run
echo ""

echo -e "${YELLOW}Do you want to regenerate loading sheets for the last 7 days? (y/n)${NC}"
read -r response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo -e "${YELLOW}Regenerating loading sheets...${NC}"
    php artisan loading-sheets:regenerate --days=7 --force
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Loading sheets regenerated${NC}"
    else
        echo -e "${RED}✗ Loading sheet regeneration failed${NC}"
    fi
else
    echo -e "${YELLOW}Skipping loading sheet regeneration${NC}"
fi
echo ""

# Step 9: Verify
echo -e "${YELLOW}Step 9: Running verification checks...${NC}"
echo ""
echo "Checking for duplicate loading sheets..."
php artisan tinker --execute="
\$duplicates = DB::select('
    SELECT shift_id, bin_location_id, COUNT(*) as count
    FROM salesman_shift_store_dispatches
    GROUP BY shift_id, bin_location_id
    HAVING count > 1
');
echo 'Duplicate dispatches found: ' . count(\$duplicates);
if (count(\$duplicates) > 0) {
    echo PHP_EOL . 'WARNING: Duplicates still exist!' . PHP_EOL;
    print_r(\$duplicates);
} else {
    echo PHP_EOL . '✓ No duplicates found' . PHP_EOL;
}
"
echo ""

# Final summary
echo "=========================================="
echo -e "${GREEN}Production Update Complete!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Monitor logs: tail -f storage/logs/laravel.log | grep 'Loading Sheet'"
echo "2. Check queue: php artisan queue:work --once"
echo "3. Test creating a new shift and closing it"
echo "4. Verify loading sheets are generated correctly"
echo ""
echo "Debug endpoints available:"
echo "- /admin/salesman-orders/debug-loading-sheets"
echo "- /admin/salesman-orders/generate-loading-sheets/{shiftId}"
echo ""
