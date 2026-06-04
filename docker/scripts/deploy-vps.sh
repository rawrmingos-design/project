#!/bin/sh

set -eu

: "${IMAGE_NAME:?IMAGE_NAME is required}"
: "${GHCR_USERNAME:?GHCR_USERNAME is required}"
: "${GHCR_TOKEN:?GHCR_TOKEN is required}"
: "${IMAGE_TAG:=latest}"

export APP_IMAGE="${IMAGE_NAME}:${IMAGE_TAG}"

echo "$GHCR_TOKEN" | docker login ghcr.io -u "$GHCR_USERNAME" --password-stdin

docker pull "$APP_IMAGE"
docker compose pull app
docker compose up -d --remove-orphans
docker compose exec -T app sh /var/www/html/docker/scripts/post-deploy-app.sh
docker image prune -f

echo "Deploy selesai pada $(date)"
