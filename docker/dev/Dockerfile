# Dev-only image (see compose.yaml and the README "Development setup" section).
# The self-hosted reference image is a separate, future concern.
FROM dunglas/frankenphp:1-php8.4

# git/unzip: composer; pdo_pgsql: docker/pg-demo connection; pcntl: signal handling
# in long-running console commands (app:demo:run); redis: phpredis (predis stays a
# require-dev fallback for environments without the extension).
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# The container runs as root but the bind-mounted /app is owned by the host user
# (uid 1000), so git refuses it as "dubious ownership". composer install and several
# console commands run git against /app, so trust it image-wide (survives container
# recreation, unlike an ad-hoc `git config` inside a running container).
RUN git config --system --add safe.directory /app

RUN install-php-extensions \
    apcu \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    redis \
    zip

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    # php.ini-development's default 128M is too low for this app's dev-mode
    # cache:clear/warmup (full container build + debug); prod (repo php.ini) is
    # unaffected, it ships its own memory_limit for Clever Cloud.
    && sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$PHP_INI_DIR/php.ini"
