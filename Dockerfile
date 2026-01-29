FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# -----------------------------
# System deps
# -----------------------------
RUN apt update && apt install -y \
  software-properties-common lsb-release curl gnupg unzip git ca-certificates \
  && rm -rf /var/lib/apt/lists/*

# -----------------------------
# PHP 8.2 + Apache
# -----------------------------
RUN add-apt-repository -y ppa:ondrej/php \
  && apt update \
  && apt install -y \
    apache2 libapache2-mod-php8.2 \
    php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd \
    php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-opcache \
  && a2enmod rewrite headers expires \
  && rm -rf /var/lib/apt/lists/*

# Set DocumentRoot to Laravel public
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf \
  && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
  && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# -----------------------------
# Composer
# -----------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# -----------------------------
# Node 20
# -----------------------------
RUN mkdir -p /etc/apt/keyrings \
  && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
    | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
  && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list \
  && apt update && apt install -y nodejs \
  && rm -rf /var/lib/apt/lists/*

# -----------------------------
# App
# -----------------------------
WORKDIR /var/www/html
COPY . /var/www/html

# Ensure writable dirs exist (image-time)
RUN mkdir -p storage/logs storage/framework/{sessions,views,cache} bootstrap/cache \
  && chown -R www-data:www-data /var/www/html \
  && chmod -R ug+rwX storage bootstrap/cache

# Install PHP deps
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

# Build frontend assets
# RUN npm ci && npm run build

# (Optional) Clear caches at build-time; runtime entrypoint should also clear safely
RUN php artisan optimize:clear || true

# -----------------------------
# Entrypoint (runtime fixer)
# -----------------------------
COPY docker/php-apache/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]
