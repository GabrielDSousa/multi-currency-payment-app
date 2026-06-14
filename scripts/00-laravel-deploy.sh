#!/usr/bin/env bash

echo "Running composer"
composer global require hirak/prestissimo
composer install --no-dev --optimize-autoloader

echo "Prepare the application ..."
php artisan key:generate
php artisan passport:keys

echo "Caching ..."
php artisan optimize

echo "Running migrations..."
php artisan migrate --force
php artisan db:seed --force
