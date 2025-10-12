FROM php:8.3-fpm-alpine

LABEL maintainer="ACS Team"
LABEL description="ACS - Auto Configuration Server for TR-069/TR-369 Device Management"

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    redis \
    supervisor \
    nginx \
    unzip \
    zip \
    protobuf

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    gd \
    mbstring \
    pdo \
    pdo_pgsql \
    soap \
    zip \
    pcntl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/acs

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/acs \
    && chmod -R 755 /var/www/acs/storage \
    && chmod -R 755 /var/www/acs/bootstrap/cache

# Copy Nginx configuration for Docker
COPY deploy/docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy PHP-FPM configuration
COPY deploy/docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy supervisor configuration
COPY deploy/docker/supervisord.conf /etc/supervisord.conf

# Create necessary directories
RUN mkdir -p /var/log/supervisor /run/nginx

# Expose ports
EXPOSE 80 9000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:80/acs/dashboard || exit 1

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
