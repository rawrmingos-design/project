#!/bin/sh

set -eu

: "${IMAGE_NAME:?IMAGE_NAME is required}"
: "${GHCR_USERNAME:?GHCR_USERNAME is required}"
: "${GHCR_TOKEN:?GHCR_TOKEN is required}"
: "${IMAGE_TAG:=latest}"

export APP_IMAGE="${IMAGE_NAME}:${IMAGE_TAG}"

echo "$GHCR_TOKEN" | docker login ghcr.io -u "$GHCR_USERNAME" --password-stdin

# Automatically remove any local bind mount for source code to ensure the container uses the image code
if [ -f docker-compose.yml ]; then
    sed -i '/- \.:\/var\/www\/html/d' docker-compose.yml
fi

# `docker pull` tidak punya timeout sendiri. Kalau koneksi ke GHCR stall di
# tengah transfer, perintahnya menggantung sampai SSH action membunuhnya
# (command_timeout) dan seluruh deploy gagal.
#
# Terbukti 2026-09-23: pull aktif hanya 10-17 detik lalu diam ~9m45s tanpa
# progres sampai kena batas 10m (run 35862993385 attempt 1, 35865408113
# attempt 1 dan 2). Retry menyelesaikannya: staging attempt 2 sukses, karena
# layer yang sudah terunduh tetap di cache lokal sehingga percobaan berikutnya
# MELANJUTKAN, bukan mulai dari nol.
#
# Catatan: dijalankan lewat `sh -c` karena `timeout` hanya bisa mengeksekusi
# program eksternal, bukan shell function.
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

run_with_retry "image aplikasi ${APP_IMAGE}" "docker pull ${APP_IMAGE}"
run_with_retry "compose pull app" "docker compose pull app"

docker compose up -d --remove-orphans
docker compose exec -T app sh /var/www/html/docker/scripts/post-deploy-app.sh
docker image prune -f

echo "Deploy selesai pada $(date)"
