#!/bin/bash
echo -e '\e[1m\e[34mChanging directory..\e[0m\n'
cd "/www/wwwroot/demo.efficentrix.co.ke" || exit
echo -e '\e[1m\e[34mPulling code from remote..\e[0m\n'
git reset --hard && git pull origin
echo -e '\e[1m\e[34m\nInstalling required packages..\e[0m\n'
composer install --no-interaction
echo -e '\e[1m\e[34m\nClearing config..\e[0m\n'
php artisan config:clear
echo -e '\e[1m\e[34m\nMigrating database..\e[0m\n'
php artisan migrate
php artisan db:seed
