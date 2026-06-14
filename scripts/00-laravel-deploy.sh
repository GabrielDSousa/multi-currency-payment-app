#!/usr/bin/env bash

echo "Installing composer"
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
rm composer-setup.php

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
