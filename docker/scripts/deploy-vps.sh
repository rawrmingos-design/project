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

# `docker pull` tidak punya timeout sendiri. Kalau koneksi ke GHCR stall di
# tengah transfer, perintahnya menggantung sampai SSH action membunuhnya
# (command_timeout) dan seluruh deploy gagal.
#
# Terbukti 2026-09-23: pull aktif hanya 10-17 detik, lalu diam ~9m40s tanpa
# progres sama sekali sampai kena batas 10m - berulang di staging (attempt 1)
# dan production (attempt 1 dan 2). Retry menyelesaikannya: staging attempt 2
# sukses, karena layer yang sudah terunduh tetap di cache lokal sehingga
# percobaan berikutnya MELANJUTKAN, bukan mulai dari nol.
#
# Catatan: perintah dijalankan lewat `sh -c` karena `timeout` hanya bisa
# mengeksekusi program eksternal, bukan shell function.
run_with_retry() {
    _label="$1"
    _command="$2"
    _attempt=1
    _max="${PULL_MAX_ATTEMPTS:-5}"
    _stall="${PULL_STALL_TIMEOUT:-420}"

    while [ "$_attempt" -le "$_max" ]; do
        echo "[pull] ${_label} - percobaan ${_attempt}/${_max} (batas stall ${_stall}s)"
        if timeout "$_stall" sh -c "$_command"; then
            echo "[pull] ${_label} berhasil pada percobaan ${_attempt}"
            return 0
        fi
        echo "[pull] PERINGATAN: ${_label} percobaan ${_attempt} gagal/stall; layer ter-cache, mencoba lagi"
        _attempt=$((_attempt + 1))
        sleep 5
    done

    echo "[pull] GAGAL: ${_label} tidak selesai setelah ${_max} percobaan" >&2
    return 1
}

# Susun perintah compose sebagai string (dipakai lewat `sh -c`).
if [ -n "$COMPOSE_OVERRIDE" ]; then
    COMPOSE_CMD="docker compose -f ${COMPOSE_FILE} -f ${COMPOSE_OVERRIDE}"
else
    COMPOSE_CMD="docker compose -f ${COMPOSE_FILE}"
fi

run_with_retry "image aplikasi ${APP_IMAGE}" "docker pull ${APP_IMAGE}"
run_with_retry "compose pull app" "${COMPOSE_CMD} pull app"

compose up -d --no-deps --force-recreate --remove-orphans app
compose exec -T app sh /var/www/html/docker/scripts/post-deploy-app.sh
docker image prune -f

echo "Deploy selesai pada $(date)"
