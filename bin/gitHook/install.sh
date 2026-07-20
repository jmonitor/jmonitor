#!/bin/sh

cp ./bin/gitHook/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

echo "Pre-commit git hook installed (or updated)"
