#!/bin/sh
set -eu

cd /var/www/html

# Em Linux, a pasta montada tem o dono da máquina: o www-data passa a ser ele.
owner="$(stat -c %u .)"
group="$(stat -c %g .)"

if [ "$owner" != "0" ]; then
    groupmod -o -g "$group" www-data
    usermod -o -u "$owner" -g "$group" www-data
fi

if [ "${1:-}" != "apache2-foreground" ]; then
    exec runuser -u www-data -- "$@"
fi

mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

# Sem .env, o assistente de instalação assume, já apontando para o banco do ambiente.
if [ ! -f .env ]; then
    cp .env.example .env
    sed -i \
        -e "s|^APP_URL=.*|APP_URL=http://localhost:${DOCKER_HTTP_PORT:-8080}|" \
        -e "s|^DB_HOST=.*|DB_HOST=${DOCKER_DB_HOST:-mysql}|" \
        -e "s|^DB_DATABASE=.*|DB_DATABASE=${DOCKER_DB_DATABASE:-template}|" \
        -e "s|^DB_USERNAME=.*|DB_USERNAME=${DOCKER_DB_USERNAME:-template}|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD=\"${DOCKER_DB_PASSWORD:-template}\"|" \
        .env
    chown www-data:www-data .env
fi

if [ ! -f vendor/autoload.php ]; then
    runuser -u www-data -- composer install --no-interaction --prefer-dist
fi

# O teste de saúde passa pelas rotas web, que não funcionam sem a chave.
if grep -qE '^APP_KEY=\s*$' .env; then
    runuser -u www-data -- php artisan key:generate --force --no-interaction
fi

if [ "${DOCKER_SSL_ENABLED:-false}" = "true" ]; then
    for file in server.crt server.key; do
        if [ ! -f "/etc/apache2/certs/$file" ]; then
            echo "HTTPS ligado, mas falta docker/certs/$file. Coloque o certificado ou use DOCKER_SSL_ENABLED=false." >&2
            exit 1
        fi
    done

    a2enmod ssl >/dev/null
    a2ensite default-ssl >/dev/null

    exec apache2-foreground -DFORCE_HTTPS
fi

exec "$@"
