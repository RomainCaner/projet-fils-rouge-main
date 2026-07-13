# ===========================================================================
# Image applicative : PHP 8.2 + Apache.
# La racine web est public/ (Front Controller), et mod_rewrite est activé
# pour la réécriture d'URL définie dans public/.htaccess.
# ===========================================================================
FROM php:8.2-apache

# Extensions PHP requises par le projet (PDO MySQL + mbstring).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Racine web = sous-dossier public/ du projet.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Autorise les règles du .htaccess (mod_rewrite) dans la racine publique.
RUN printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/app.conf \
    && a2enconf app

EXPOSE 80
