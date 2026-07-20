#!/bin/sh
# Entrypoint of the self-hosted image.
#
# The install sequence (cache warmup, migrations, initial admin — see
# src/Command/InstallCommand.php) only runs when this container starts the web
# server. Containers reusing the image with another command (the messenger
# worker in compose.yaml) skip it and wait for the app to be healthy instead,
# so the install never runs twice concurrently.
set -e

# PHP cannot read the timezone from the environment: materialize TZ (IANA
# identifier, validated by app:install) as date.timezone. Runs for every
# container using the image — the worker needs it too.
echo "date.timezone = ${TZ:-UTC}" > /usr/local/etc/php/conf.d/zz-timezone.ini

if [ "$1" = "frankenphp" ]; then
    php bin/console cache:warmup
    php bin/console app:install --no-interaction
fi

exec "$@"
