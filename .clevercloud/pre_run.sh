#!/bin/bash -l

# https://www.clever.cloud/developers/doc/develop/build-hooks/#pre-run

set -e

echo "🛑 Stopping Messenger workers..."
frankenphp php-cli bin/console messenger:stop-workers

echo "📦 Running Doctrine migrations..."
frankenphp php-cli bin/console doctrine:migrations:migrate --no-interaction

# Idempotent, so it is safe to run on every deployment.
echo "🎭 Provisioning the demo project..."
frankenphp php-cli bin/console app:demo:provision || echo "⚠️  Demo provisioning failed (non-blocking)"
