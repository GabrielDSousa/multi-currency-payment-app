FROM serversideup/php:8.5-fpm-nginx

# Switch to root to install dependencies and copy files
USER root

# Install PHP dependencies
RUN install-php-extensions intl bcmath

# Switch back to non-root user for security
USER www-data

# Copy application files with correct ownership
COPY --chown=www-data:www-data . /var/www/html
