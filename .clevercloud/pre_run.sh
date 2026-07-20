#!/bin/bash -l

# https://www.clever.cloud/developers/doc/develop/build-hooks/#pre-run

set -e

echo "🛑 Arrêt des workers Messenger..."
frankenphp php-cli bin/console messenger:stop-workers

# Migrations Doctrine
echo "📦 Exécution des migrations Doctrine..."
frankenphp php-cli bin/console doctrine:migrations:migrate --no-interaction

# Provisionnement idempotent du projet démo
echo "🎭 Provisionnement du projet démo..."
frankenphp php-cli bin/console app:demo:provision || echo "⚠️  Provisionnement démo échoué (non bloquant)"
