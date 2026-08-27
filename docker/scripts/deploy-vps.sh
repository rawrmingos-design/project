#!/bin/sh

set -eu

: "${IMAGE_NAME:?IMAGE_NAME is required}"
: "${GHCR_USERNAME:?GHCR_USERNAME is required}"
: "${GHCR_TOKEN:?GHCR_TOKEN is required}"
: "${IMAGE_TAG:?IMAGE_TAG is required}"
: "${COMPOSE_FILE:=docker-compose.yml}"
: "${COMPOSE_OVERRIDE:=}"

export APP_IMAGE="${IMAGE_NAME}:${IMAGE_TAG}"

compose() {
    if [ -n "$COMPOSE_OVERRIDE" ]; then
        docker compose -f "$COMPOSE_FILE" -f "$COMPOSE_OVERRIDE" "$@"
    else
        docker compose -f "$COMPOSE_FILE" "$@"
    fi
}

echo "$GHCR_TOKEN" | docker login ghcr.io -u "$GHCR_USERNAME" --password-stdin

docker pull "$APP_IMAGE"
compose pull app
compose up -d --no-deps --force-recreate --remove-orphans app
compose exec -T app sh /var/www/html/docker/scripts/post-deploy-app.sh
docker image prune -f

echo "Deploy selesai pada $(date)"
