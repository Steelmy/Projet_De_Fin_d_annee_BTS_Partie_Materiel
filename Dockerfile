FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

# Configuration PHP de production : les avertissements vont dans les logs du conteneur
# (docker compose logs web) au lieu d'être affichés, ce qui corromprait le JSON et les PDF.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Seuls les fichiers servis sont copiés : ni .env, ni dump SQL, ni .git dans l'image.
COPY index.php login.php logout.php /var/www/html/
COPY app/ /var/www/html/app/
COPY php/ /var/www/html/php/
COPY javascript/ /var/www/html/javascript/
COPY css/ /var/www/html/css/
