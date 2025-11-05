# QrArt - Multi-stage Dockerfile
# Production-ready container for PHP 8.1 + CodeIgniter 4

# Build stage
FROM php:8.1-apache AS builder

LABEL maintainer="QrArt Team"
LABEL description="QrArt - Interactive QR Code Content Platform"

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy custom Apache configuration
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Production stage
FROM builder AS production

ENV APP_ENV=production
ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend/qrartApp/public

# Update Apache document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Create writable directories
RUN mkdir -p \
    backend/qrartApp/writable/cache \
    backend/qrartApp/writable/logs \
    backend/qrartApp/writable/session \
    backend/qrartApp/writable/uploads \
    backend/qrartApp/public/media \
    && chown -R www-data:www-data /var/www/html/backend/qrartApp/writable \
    && chown -R www-data:www-data /var/www/html/backend/qrartApp/public/media \
    && chmod -R 755 /var/www/html/backend/qrartApp/writable \
    && chmod -R 755 /var/www/html/backend/qrartApp/public/media

# Remove .env from container (use environment variables)
RUN rm -f backend/qrartApp/.env

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

# Development stage
FROM builder AS development

ENV APP_ENV=development
ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend/qrartApp/public

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Update Apache document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# In development, we'll mount volumes
CMD ["apache2-foreground"]
