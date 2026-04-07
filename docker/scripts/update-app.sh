#!/bin/sh

set -eu

COMPOSE_FILE_PATH="${1:-${COMPOSE_FILE:-docker-compose.yml}}"

compose() {
    docker compose -f "$COMPOSE_FILE_PATH" "$@"
}

echo "Updating app service with compose file: $COMPOSE_FILE_PATH"

compose pull app
compose up -d --no-deps --force-recreate app
compose exec -T app sh /var/www/html/docker/scripts/post-deploy-app.sh

if [ "${SKIP_IMAGE_PRUNE:-false}" != "true" ]; then
    docker image prune -f
fi

echo "App update selesai pada $(date)"
