FROM php:8.4-fpm

# 1. Instal library 
# 1. Instal library pendukung level OS + Node.js + Depedencies Browser (Playwright/Chromium)
RUN apt-get update && apt-get install -y \
    nodejs \
    npm \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \

    # --------------------------------------------------
    && rm -rf /var/lib/apt/lists/*




# 2. Konfigurasi dan instal ekstensi PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip bcmath

# 3. Ambil Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 4. INSTAL NODEJS & NPM (Terbaru)
RUN curl -fsSL https://deb.nodesource.com/setup_current.x | bash - \
    && apt-get install -y nodejs

# 5. Daftarkan safe directory untuk Git
RUN git config --global --add safe.directory /var/www

WORKDIR /var/www

