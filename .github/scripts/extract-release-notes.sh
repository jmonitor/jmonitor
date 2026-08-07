#!/usr/bin/env bash
# Print one version's section of CHANGELOG.md, for use as GitHub release notes.
# Usage: extract-release-notes.sh 1.2.3
set -euo pipefail

version="${1:?usage: extract-release-notes.sh <version>}"
changelog="$(dirname "$0")/../../CHANGELOG.md"

# The version is interpolated into a regex, so its dots are escaped first.
# Extraction stops at the next heading or at the first link reference
# definition, which keeps the trailing "[1.0.0]: https://..." lines out of the
# release body when the requested version is the last section of the file.
notes="$(
    awk -v ver="$version" '
        BEGIN { gsub(/\./, "\\.", ver) }
        $0 ~ "^## \\[" ver "\\]" { inside = 1; next }
        inside && /^## / { exit }
        inside && /^\[[^]]+\]: / { exit }
        inside && !started && /^[[:space:]]*$/ { next }
        inside { started = 1; print }
    ' "$changelog"
)"

if [ -z "$(printf '%s' "$notes" | tr -d '[:space:]')" ]; then
    echo "CHANGELOG.md has no entry for version $version" >&2
    exit 1
fi

printf '%s\n' "$notes"
