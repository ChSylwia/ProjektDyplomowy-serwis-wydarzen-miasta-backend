FROM php:8.2-apache

# Systemowe zależności
RUN apt-get update && apt-get install -y \
    gnupg2 unzip git curl libssl-dev libxml2-dev libzip-dev libpng-dev libonig-dev \
    libpq-dev libicu-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libxpm-dev \
    && apt-get clean

# Dodanie repozytorium Microsoft
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable pdo_sqlsrv sqlsrv

# Apache - mod_rewrite
RUN a2enmod rewrite

# Skopiuj aplikację
WORKDIR /var/www/html
COPY . .

# Instalacja Composera i zależności
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader

EXPOSE 80
CMD ["apache2-foreground"]
