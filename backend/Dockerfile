FROM php:8.2-apache

# Extensions the app needs (MySQL access)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Apache mod_rewrite is used for the Authorization header passthrough in .htaccess
RUN a2enmod rewrite

# App code
COPY . /var/www/html/
WORKDIR /var/www/html

# uploads/ needs to be writable by the web server
RUN chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads

# Apache must serve from PHP files directly (no front controller / router),
# and must listen on Railway's dynamically assigned $PORT.
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Railway injects PORT at runtime; Apache's default config only knows about
# port 80 at build time, so listen/port substitution happens in the entrypoint.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]