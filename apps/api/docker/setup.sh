#!/bin/sh
set -eu
umask 077

KEY_DIR=/run/app-key
KEY_FILE="$KEY_DIR/app_key"
mkdir -p "$KEY_DIR"

if [ -f "$KEY_FILE" ]; then
    app_key="$(cat "$KEY_FILE")"

    if [ -n "${APP_KEY:-}" ] && [ "$APP_KEY" != "$app_key" ]; then
        echo "APP_KEY differs from the persistent key. Refusing an implicit key rotation." >&2
        exit 1
    fi
else
    if [ -n "${APP_KEY:-}" ]; then
        app_key="$APP_KEY"
    else
        # If migrations already ran, a missing key volume may mean the original
        # key was lost. Never silently generate a replacement for an existing app.
        migration_status="$(frankenphp php-cli artisan migrate:status --no-interaction 2>/dev/null || true)"
        if printf '%s\n' "$migration_status" | grep -Eq '(^|[[:space:]])Yes([[:space:]]|$)'; then
            echo "The database already has applied migrations but the persistent APP_KEY is missing. Restore the app_key volume or configure the original APP_KEY in Portainer; refusing to generate a replacement." >&2
            exit 1
        fi

        app_key="$(frankenphp php-cli artisan key:generate --show)"
    fi

    case "$app_key" in
        base64:*) ;;
        *)
            echo "APP_KEY must be a Laravel base64: key. Refusing to persist an invalid key." >&2
            exit 1
            ;;
    esac

    encoded_key="$(printf '%s' "$app_key" | sed 's/^base64://')"
    key_bytes="$(printf '%s' "$encoded_key" | base64 -d 2>/dev/null | wc -c | tr -d '[:space:]')"
    if [ "$key_bytes" != "32" ]; then
        echo "APP_KEY must decode to exactly 32 bytes for the configured AES-256 cipher." >&2
        exit 1
    fi

    temporary_file="$KEY_FILE.tmp.$$"
    printf '%s' "$app_key" > "$temporary_file"
    chmod 600 "$temporary_file"
    mv "$temporary_file" "$KEY_FILE"
    echo "Persisted the initial Laravel application key in the dedicated Docker volume."
fi

export APP_KEY="$app_key"

frankenphp php-cli artisan migrate --force
frankenphp php-cli artisan db:seed --force
