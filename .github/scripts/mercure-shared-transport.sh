#!/usr/bin/env bash
# A subscriber on the public site must receive an update published through the
# compose-internal :2021 site: both `mercure` blocks of the Caddyfile share one
# transport only while they carry the same `name`.
# Usage: MERCURE_JWT_SECRET=... PUBLIC_HUB_URL=https://dash.example.com/.well-known/mercure \
#        SUBSCRIBE_CURL_OPTS="-k --resolve dash.example.com:443:127.0.0.1" \
#        PUBLISH_CURL="docker compose exec -T app curl" mercure-shared-transport.sh
set -euo pipefail

: "${MERCURE_JWT_SECRET:?}" "${PUBLIC_HUB_URL:?}" "${PUBLISH_CURL:?}"
SUBSCRIBE_CURL_OPTS="${SUBSCRIBE_CURL_OPTS:-}"

b64url() { openssl base64 -A | tr '+/' '-_' | tr -d '='; }

# Mercure 0.x token (claim "mercure"), the shape the app still mints.
jwt() {
    local header payload signature
    header=$(printf '{"alg":"HS256","typ":"JWT"}' | b64url)
    payload=$(printf '%s' "$1" | b64url)
    signature=$(printf '%s.%s' "$header" "$payload" | openssl dgst -sha256 -hmac "$MERCURE_JWT_SECRET" -binary | b64url)
    printf '%s.%s.%s' "$header" "$payload" "$signature"
}

topic="https://example.com/smoke/$(date +%s)"
subscriber=$(jwt "{\"mercure\":{\"subscribe\":[\"$topic\"]}}")
publisher=$(jwt '{"mercure":{"publish":["*"]}}')
received=$(mktemp)

# shellcheck disable=SC2086
curl -sN $SUBSCRIBE_CURL_OPTS -H "Authorization: Bearer $subscriber" "$PUBLIC_HUB_URL?topic=$topic" > "$received" 2>/dev/null &
subscriber_pid=$!
trap 'kill $subscriber_pid 2>/dev/null || true' EXIT
sleep 2

$PUBLISH_CURL -sf -o /dev/null -X POST http://app:2021/.well-known/mercure \
    -H "Authorization: Bearer $publisher" \
    --data-urlencode "topic=$topic" --data-urlencode "data=shared-transport-ok" --data-urlencode "private=on"

for _ in $(seq 1 10); do
    if grep -q "shared-transport-ok" "$received"; then
        echo "Mercure shared transport: OK"
        exit 0
    fi
    sleep 1
done

echo "Mercure shared transport: the update published on :2021 never reached the public-site subscriber" >&2
cat "$received" >&2
exit 1
