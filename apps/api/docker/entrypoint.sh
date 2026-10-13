#!/bin/sh
set -eu

KEY_FILE=/run/app-key/app_key

if [ -f "$KEY_FILE" ]; then
    persisted_key="$(cat "$KEY_FILE")"

    if [ -z "$persisted_key" ]; then
        echo "Persistent APP_KEY file is empty; refusing to start." >&2
        exit 1
    fi

    if [ -n "${APP_KEY:-}" ] && [ "$APP_KEY" != "$persisted_key" ]; then
        echo "APP_KEY differs from the persistent key. Refusing an implicit key rotation." >&2
        exit 1
    fi

    export APP_KEY="$persisted_key"
elif [ "${1:-}" != "/usr/local/bin/alamia-setup" ]; then
    echo "Persistent APP_KEY is missing. The setup service must complete successfully first." >&2
    exit 1
fi

exec "$@"
