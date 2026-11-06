# Único lugar da versão do PHP. Atualizar exige rodar a suíte no container.
ARG PHP_VERSION=8.5

FROM composer:2.11 AS composer

FROM php:${PHP_VERSION}-apache-trixie

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY php/php.ini /usr/local/etc/php/conf.d/zz-template.ini
COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY apache/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
COPY php/entrypoint.sh /usr/local/bin/template-entrypoint

RUN sed -i 's/\r$//' /usr/local/bin/template-entrypoint \
    && chmod +x /usr/local/bin/template-entrypoint \
    && echo 'ServerName localhost' > /etc/apache2/conf-enabled/server-name.conf

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html

ENTRYPOINT ["template-entrypoint"]
CMD ["apache2-foreground"]
