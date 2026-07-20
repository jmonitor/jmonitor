#!/bin/bash -l

# https://www.clever.cloud/developers/doc/develop/build-hooks/#post-build
# This hook is not ran during deployments from cache.

set -e

composer dump-env prod

# notez que les script post-install de composer ne s'execute pas, je les remets ici donc
frankenphp php-cli bin/console cache:clear
frankenphp php-cli bin/console assets:install
frankenphp php-cli bin/console importmap:install
frankenphp php-cli bin/console asset-map:compile
