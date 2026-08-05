#!/bin/sh

set -eu

mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    vendor

chown -R www-data:www-data bootstrap/cache storage vendor

runuser --user www-data -- \
    composer install \
        --no-interaction \
        --no-progress \
        --prefer-dist
