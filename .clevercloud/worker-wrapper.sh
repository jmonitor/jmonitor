#!/bin/bash

# Worker wrapper for Clever Cloud deployments.
#
# Distinguishes between natural worker stops and intentional stops,
# to work correctly with CC_WORKER_RESTART=on-failure:
#   exit 0 → stopped by SIGTERM (deployment) or messenger:stop-workers — CC does NOT restart
#   exit 1 → natural stop (time-limit, memory-limit, limit) — CC restarts
#
# Usage: CC_WORKER_COMMAND_<n> (requires CC_WORKER_RESTART=on-failure), e.g.:
#   .clevercloud/worker-wrapper.sh frankenphp php-cli bin/console app:demo:run --time-limit=3600

SIGTERM_RECEIVED=0
SENTINEL_FILE="/tmp/worker-restart-signal.$$"
export CC_WORKER_SENTINEL_FILE="$SENTINEL_FILE"

on_sigterm() {
    SIGTERM_RECEIVED=1
    [ -n "$CHILD_PID" ] && kill -TERM "$CHILD_PID" 2>/dev/null
}

trap on_sigterm TERM

"$@" &
CHILD_PID=$!

# Keep waiting even if interrupted by a signal
while kill -0 "$CHILD_PID" 2>/dev/null; do
    wait "$CHILD_PID"
done

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
PREFIX="[${TIMESTAMP}] [worker-wrapper pid=$$]"

if [ "$SIGTERM_RECEIVED" = "1" ]; then
    echo "${PREFIX} Stopped by SIGTERM (deployment) — not restarting"
    rm -f "$SENTINEL_FILE"
    exit 0
fi

if [ -f "$SENTINEL_FILE" ]; then
    echo "${PREFIX} Stopped by messenger:stop-workers — not restarting"
    rm -f "$SENTINEL_FILE"
    exit 0
fi

echo "${PREFIX} Natural stop (time-limit/memory-limit/limit) — restarting"
exit 1
