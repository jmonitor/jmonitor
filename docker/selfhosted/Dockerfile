# Self-hosted production image. Build from the REPOSITORY ROOT:
#   docker build -t jmonitor/jmonitor:dev -f docker/selfhosted/Dockerfile .
# Published to Docker Hub (+ ghcr.io mirror) by .github/workflows/docker-publish.yml on version tags.
# The dev image is separate (docker/dev/, bind-mounted sources, no worker mode).

FROM dunglas/frankenphp:1-php8.4 AS base

# Same extensions as dev minus pdo_pgsql (only used by the dev pg-demo stack).
RUN install-php-extensions \
    apcu \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    redis \
    zip

# ---- build stage: vendors + compiled assets ----
FROM base AS build

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

# git/unzip: composer dist installs.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
ENV APP_ENV=prod

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist

COPY . .

RUN composer dump-autoload --no-dev --classmap-authoritative

# AssetMapper: download the importmap packages and compile the assets.
# Boots the kernel on the committed .env defaults — asset compilation does not
# touch external services, so empty DSNs are fine here.
# Exception: config/packages/mercure.yaml's %env(default::MERCURE_URL)% resolves
# to null (not '') when the var is merely empty, and Hub::__construct() requires
# a string — so the kernel can't boot at all in prod without it. Build-only dummy,
# never reachable and never baked into the runtime stage (real value comes from
# the container's actual MERCURE_URL/MERCURE_JWT_SECRET at runtime).
ENV MERCURE_URL=https://localhost/.well-known/mercure
RUN php bin/console importmap:install \
    && php bin/console asset-map:compile \
    # The build-time cache resolved nothing real: the entrypoint warms a fresh
    # one at runtime, when the actual env vars exist.
    && rm -rf var/cache var/log

# ---- runtime stage ----
FROM base

WORKDIR /app

ENV APP_ENV=prod
# This image IS the self-hosted edition (the cloud edition deploys from source
# on Clever Cloud). Overridable at run time, but you should not need to.
ENV APP_EDITION=selfhosted
# PHP's embed SAPI checks cwd for php.ini before the compiled-in path; pin the
# compiled path so a php.ini at the repo root (/app) could never shadow this
# image's /usr/local/etc/php/php.ini. Same fix as docker/dev's compose.yaml.
ENV PHPRC=/usr/local/etc/php

COPY docker/selfhosted/php.ini /usr/local/etc/php/php.ini
COPY docker/selfhosted/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/selfhosted/entrypoint.sh /usr/local/bin/jmonitor-entrypoint
RUN chmod +x /usr/local/bin/jmonitor-entrypoint

COPY --from=build /app /app

ENTRYPOINT ["jmonitor-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
