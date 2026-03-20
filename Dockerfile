FROM php:8.2-apache

# Enable PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy all project files into the web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/

# Apache config to allow .htaccess
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/fastline.conf \
    && a2enconf fastline

EXPOSE 80
