#!/bin/sh
set -eu
# A local demo key survives container recreation through the storage volume.
# An explicit APP_KEY from the environment takes priority.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s storage/app/.app-key ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/.app-key
    fi
    APP_KEY="$(cat storage/app/.app-key)"
    export APP_KEY
fi
exec "$@"
