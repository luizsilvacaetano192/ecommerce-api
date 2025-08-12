FROM php:8.4.10-fpm

# Instalar dependências e extensões
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip

# Instalar extensão redis via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar diretório de trabalho
WORKDIR /var/www/html

# Copiar o código (se quiser copiar no build, ou pode montar volume)
# COPY . .

# Rodar composer install (opcional se montar volume)
# RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Expor porta para php-fpm
EXPOSE 9000

CMD ["php-fpm"]
