#!/bin/bash
set -eu

IMAGE="itaeris/product_claim_app:latest"
NAME="product_claim_app"
ENV_FILE="${HOME}/product_claim_app.env"
NETWORK="product-network"
HOST_DATA="/DATA/AppData/productclaim"

if ! docker network inspect "$NETWORK" >/dev/null 2>&1; then
    echo "Network ${NETWORK} belum ada. Container yang sedang berjalan tidak diubah."
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    echo "File environment ${ENV_FILE} tidak ditemukan."
    exit 1
fi

chmod 600 "$ENV_FILE"

mkdir -p "${HOST_DATA}/pdf" "${HOST_DATA}/katalog"

DOCKERHUB_FILE="${HOME}/.product_claim_dockerhub.env"
DOCKERHUB_USERNAME="$(sed -n 's/^DOCKERHUB_USERNAME=//p' "$DOCKERHUB_FILE")"
DOCKERHUB_TOKEN="$(sed -n 's/^DOCKERHUB_TOKEN=//p' "$DOCKERHUB_FILE")"
rm -f "$DOCKERHUB_FILE"

echo "$DOCKERHUB_TOKEN" | docker login -u "$DOCKERHUB_USERNAME" --password-stdin >/dev/null

if docker container inspect "$NAME" >/dev/null 2>&1; then
    echo "Menghentikan container ${NAME}."
    docker stop "$NAME"
    echo "Menghapus container ${NAME}."
    docker rm "$NAME"
fi

EXISTING="$(docker images itaeris/product_claim_app -q | sort -u)"
if [ -n "$EXISTING" ]; then
    echo "Menghapus image lama itaeris/product_claim_app."
    # shellcheck disable=SC2086
    docker rmi -f $EXISTING
fi

echo "Menarik image baru."
docker pull "$IMAGE"
docker logout >/dev/null 2>&1 || true

echo "Menjalankan container ${NAME} pada port 2029."
docker run -d \
    --name "$NAME" \
    --restart unless-stopped \
    --network "$NETWORK" \
    -p 2029:80 \
    -v "${ENV_FILE}:/run/secrets/backend.env:ro" \
    -v "${HOST_DATA}/pdf:/var/www/html/storage/app/private" \
    -v "${HOST_DATA}/katalog:/var/www/html/storage/app/public" \
    "$IMAGE"

ready=0
for _ in $(seq 1 40); do
    if docker exec "$NAME" curl -fsS http://127.0.0.1/up >/dev/null 2>&1; then
        ready=1
        break
    fi
    sleep 3
done

if [ "$ready" -ne 1 ]; then
    echo "Container belum siap. Log proses terakhir:"
    docker logs "$NAME" --tail 120 || true
    echo "Log Laravel terakhir:"
    docker exec "$NAME" sh -c 'tail -n 120 storage/logs/laravel.log 2>/dev/null || true' || true
    exit 1
fi

docker ps --filter "name=^/${NAME}$"
echo "Deploy selesai. Migrasi dijalankan saat container mulai."
