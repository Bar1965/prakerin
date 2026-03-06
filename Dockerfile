FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    a2enmod rewrite

# Install additional utilities
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# PHP config: upload size, timezone
RUN echo "upload_max_filesize = 10M\npost_max_size = 10M\nmax_execution_time = 60\ndate.timezone = Asia/Jakarta" \
    > /usr/local/etc/php/conf.d/siprakerin.ini

# Apache config: allow .htaccess overrides
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/siprakerin.conf && \
    a2enconf siprakerin

# Copy app files
COPY . /var/www/html/

# Fix permissions for uploads folder
RUN mkdir -p /var/www/html/uploads/absensi /var/www/html/uploads/profil && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod -R 755 /var/www/html/uploads

EXPOSE 80
