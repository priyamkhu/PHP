# FROM php:8.1-cli

# # Install mysqli extension
# RUN docker-php-ext-install mysqli

# WORKDIR /var/www/html

# COPY . .

# EXPOSE 10000

# CMD ["php", "-S", "0.0.0.0:10000"]






FROM php:8.1-cli

# Install mysqli and zip (PHPMailer needs zip sometimes)
RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY . .

# Install Composer (to get PHPMailer)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000"]
