# FROM php:8.1-cli

# # Install mysqli extension
# RUN docker-php-ext-install mysqli

# WORKDIR /var/www/html

# COPY . .

# EXPOSE 10000

# CMD ["php", "-S", "0.0.0.0:10000"]






FROM php:8.1-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    zlib1g-dev \
    && docker-php-ext-install mysqli mbstring sockets curl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy all project files
COPY . .

# Install PHP dependencies (e.g. PHPMailer)
RUN composer install

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000"]
