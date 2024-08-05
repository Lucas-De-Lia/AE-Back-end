# Usa la imagen base de Ubuntu
FROM ubuntu:22.04

# Establece el entorno no interactivo para evitar preguntas durante la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Actualiza el sistema e instala las herramientas necesarias
RUN apt-get update && apt-get install -y \
    software-properties-common \
    lsb-release \
    apt-transport-https \
    ca-certificates \
    wget

# Instala PHP 8.1 y las extensiones necesarias
RUN apt-get install -y \
    php8.1 \
    php8.1-cli \
    php8.1-fpm \
    php8.1-json \
    php8.1-pdo \
    php8.1-mysql \
    php8.1-pgsql \
    php8.1-zip \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-curl \
    php8.1-xml \
    php8.1-bcmath \
    php8.1-intl \
    php8.1-soap \
    php8.1-imagick \
    php8.1-sqlite3 \
    php8.1-xdebug \
    php8.1-opcache \
    php8.1-readline \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    zlib1g-dev \
    libssl-dev \
    libonig-dev \
    libpq-dev \
    git \
    unzip \
    imagemagick \
    libmagickwand-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install zip \
    && docker-php-ext-install intl \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install exif \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mysqli \
    && docker-php-ext-install calendar bcmath \
    && docker-php-ext-install soap \
    && docker-php-ext-install sockets \
    && docker-php-ext-install sysvmsg sysvsem sysvshm \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && pecl install xdebug && docker-php-ext-enable xdebug
# Instala Composer
COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

# Configura el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos de la aplicación en el contenedor
COPY . .

# Da permisos a los directorios de almacenamiento y caché
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Instala las dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Configura Apache
RUN a2enmod rewrite
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Define el comando de inicio
CMD ["apache2-foreground"]