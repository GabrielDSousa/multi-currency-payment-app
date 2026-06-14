#!/usr/bin/env bash
echo "Running composer"
composer global require hirak/prestissimo
composer install --no-dev --working-dir=/var/www/html

echo "Starting container ..."
./vendor/bin/sail up -d --build

echo "Prepare the application ..."
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan passport:keys

echo "Caching ..."
./vendor/bin/sail artisan optimize

echo "Running migrations..."
./vendor/bin/sail artisan migrate:fresh --seed --force
