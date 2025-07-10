# FROM php:8.1-cli

# # Install mysqli extension
# RUN docker-php-ext-install mysqli

# WORKDIR /var/www/html

# COPY . .

# EXPOSE 10000

# CMD ["php", "-S", "0.0.0.0:10000"]






FROM php:8.1-cli

# Install necessary PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    libssl-dev \
    libcurl4-openssl-dev \
    zlib1g-dev \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install sockets \
    && docker-php-ext-install curl \
    && docker-php-ext-install openssl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy all files into container
COPY . .

# Install PHPMailer and other dependencies
RUN composer install

# Expose desired port (for PHP built-in server)
EXPOSE 10000

# Start PHP built-in web server
CMD ["php", "-S", "0.0.0.0:10000"]


CMD ["php", "-S", "0.0.0.0:10000"]
