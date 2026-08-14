FROM php:8.2-apache

# Extensions the app needs: mysqli/pdo_mysql for the DB, curl for Cloudinary
# image uploads (libcurl4-openssl-dev is needed for ext-curl; kept installed
# since removing it can also drop the runtime lib curl actually needs).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql curl \
    && rm -rf /var/lib/apt/lists/*

# Apache mod_php only works with mpm_prefork. On some base-image layers more
# than one MPM ends up enabled ("AH00534: More than one MPM loaded"), so
# force it explicitly: disable the others, enable prefork, then rewrite.
RUN a2dismod mpm_event mpm_worker >/dev/null 2>&1; \
    a2enmod mpm_prefork; \
    a2enmod rewrite

# App code
COPY . /var/www/html/
WORKDIR /var/www/html

# uploads/ needs to be writable (still used as a transient scratch dir by
# PHP for $_FILES tmp handling; final images now go to Cloudinary)
RUN chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads

# Allow .htaccess overrides across the app (no front controller / router here,
# every .php file is served directly)
COPY apache-app.conf /etc/apache2/conf-available/app.conf
RUN a2enconf app

# Railway injects PORT at runtime; Apache's default config only knows about
# port 80 at build time, so listen/port substitution happens in the entrypoint.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]