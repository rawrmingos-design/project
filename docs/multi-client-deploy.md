# Multi-Client Deploy

Dokumen ini untuk menjalankan satu codebase Laravel Topup ke lebih dari satu client, dengan:

- codebase sama
- `.env` berbeda
- database berbeda
- assets `public` berbeda

Contoh:

- client utama: `istanatopup`
- client kedua: `egymarket`

## File Compose yang Dipakai

### Web utama

- file: [`docker-compose.yml`](d:/Backend-game-topup/web/project/docker-compose.yml)
- dipertahankan dalam mode legacy agar container live yang sekarang tidak berubah mendadak
- masih memakai:
  - `container_name`
  - named volume assets

### Client egymarket

- file: [`docker-compose.egymarket.yml`](d:/Backend-game-topup/web/project/docker-compose.egymarket.yml)
- dipakai khusus untuk client kedua
- memakai:
  - service name otomatis dari Compose
  - bind mount assets per client
  - volume DB/Redis/storage yang terpisah

Jadi:

- `docker-compose.yml` jangan dipakai untuk eksperimen client kedua
- `docker-compose.egymarket.yml` jangan dipakai untuk mengganti web utama

## Prinsip

Jangan pisahkan logic per client di branch yang berbeda kalau targetnya fiturnya sama.

Pisahkan per client lewat:

- `COMPOSE_PROJECT_NAME`
- `.env`
- database MySQL
- asset path bind mount
- domain / callback URL

## Struktur VPS yang disarankan

```text
/srv/topup/
  master/
    docker-compose.yml
    .env
    client-assets/
      istanatopup/
        public/assets/product_logo/
        public/assets/thumbnail/
        public/assets/banner/
        public/assets/banner_game/
        public/assets/logo/
        public/assets/media/
        public/articles/thumbnails/
      egymarket/
        public/assets/product_logo/
        public/assets/thumbnail/
        public/assets/banner/
        public/assets/banner_game/
        public/assets/logo/
        public/assets/media/
        public/articles/thumbnails/
```

## Env per client

### Client utama

```env
APP_NAME="Istana Topup"
APP_URL=https://istanatopup.com
APP_URL_CALLBACK=https://istanatopup.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=istana_topup_prod
DB_USERNAME=laravel
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

NGINX_PORT=9000
PMA_PORT=9001
```

### Client egymarket test / staging

```env
COMPOSE_PROJECT_NAME=egymarkettest
APP_NAME="EgyMarket Test"
APP_URL=https://test.jasakoding.web.id
APP_URL_CALLBACK=https://test.jasakoding.web.id

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db_egymarket_id_test
DB_USERNAME=laravel
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

NGINX_PORT=8088
PMA_PORT=8089

PUBLIC_PRODUCT_LOGO_PATH=./client-assets/egymarket/public/assets/product_logo
PUBLIC_THUMBNAIL_PATH=./client-assets/egymarket/public/assets/thumbnail
PUBLIC_BANNER_PATH=./client-assets/egymarket/public/assets/banner
PUBLIC_BANNER_GAME_PATH=./client-assets/egymarket/public/assets/banner_game
PUBLIC_LOGO_PATH=./client-assets/egymarket/public/assets/logo
PUBLIC_MEDIA_PATH=./client-assets/egymarket/public/assets/media
PUBLIC_ARTICLE_THUMBNAILS_PATH=./client-assets/egymarket/public/articles/thumbnails
```

## Kenapa `COMPOSE_PROJECT_NAME` penting

Untuk client kedua, `COMPOSE_PROJECT_NAME` tetap penting karena:

- nama container akan otomatis diprefix
- volume internal Compose tidak bentrok
- stack `egymarkettest` tidak mengganggu stack utama

Karena nama volume, network, dan service Compose akan diprefix otomatis berdasarkan project name.

Dengan begitu:

- container client utama tidak bentrok dengan client egymarket
- volume DB/Redis tidak tercampur
- kamu bisa menjalankan dua stack di VPS yang sama

## Import database shared hosting

Misal dump SQL client kedua bernama `egymarke_topup.sql`.

Urutan aman:

1. jalankan stack kosong client kedua
2. import dump SQL ke DB client kedua
3. jalankan migration dari code terbaru
4. verifikasi login admin, homepage, order, invoice, callback

Contoh untuk `egymarket` test / staging:

```bash
docker compose -f docker-compose.egymarket.yml up -d db redis
docker compose -f docker-compose.egymarket.yml exec -T db mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_DATABASE" < egymarke_topup.sql
docker compose -f docker-compose.egymarket.yml up -d app
docker compose -f docker-compose.egymarket.yml exec -T app php artisan migrate --force
docker compose -f docker-compose.egymarket.yml exec -T app php artisan db:seed --class=LegacyImportBootstrapSeeder --force
```

Catatan:

- kalau dump shared hosting punya tabel lama yang belum kompatibel, audit dulu sebelum migrate
- jangan jalankan `migrate:fresh` untuk client existing
- jangan jalankan `db:seed` full untuk client existing karena bisa menimpa data
- gunakan `LegacyImportBootstrapSeeder` hanya untuk mengisi tabel baru yang belum ada di dump lama

## Assets public

Untuk web utama, assets masih mengikuti named volume lama.

Untuk `egymarket`, assets memakai bind mount, jadi kamu tinggal copy gambar client kedua ke folder yang sesuai.

Contoh:

```text
client-assets/egymarket/public/assets/logo
client-assets/egymarket/public/assets/banner
client-assets/egymarket/public/assets/media
```

Container akan membaca isi folder itu langsung sebagai `public/assets/...`.

## Deploy pattern

Untuk tiap client, pakai folder deploy yang punya `.env` sendiri.

Contoh:

- `/srv/topup/istanatopup`
- `/srv/topup/egymarket`

Keduanya bisa memakai image Docker yang sama dari branch `master`.

### Web utama

```bash
docker compose up -d
```

### Client egymarket

```bash
docker compose -f docker-compose.egymarket.yml up -d
```

## Update app tanpa restart DB/Redis

Kalau hanya update code/image app, pakai script:

```bash
sh docker/scripts/update-app.sh <compose-file>
```

Script ini akan:

1. pull image `app` terbaru
2. recreate service `app` saja dengan `--no-deps`
3. menjalankan `post-deploy-app.sh`
4. menjalankan migration, clear cache, publish Livewire assets, optimize, dan repair permission

### Web utama

```bash
cd /www/wwwroot/istanatopup.com
sh docker/scripts/update-app.sh docker-compose.yml
```

### Egymarket test / staging

```bash
cd /www/wwwroot/test.jasakoding.web.id
sh docker/scripts/update-app.sh docker-compose.egymarket.yml
```

Kalau tidak ingin prune image lama setelah update:

```bash
SKIP_IMAGE_PRUNE=true sh docker/scripts/update-app.sh docker-compose.egymarket.yml
```

## Optimasi gambar legacy

Setelah import dump lama atau migrasi asset client, jalankan backfill WebP responsive sekali per client:

```bash
docker compose -f <compose-file> exec app php artisan images:optimize-existing --dry-run
docker compose -f <compose-file> exec app php artisan images:optimize-existing
```

Untuk proses bertahap di server kecil:

```bash
docker compose -f <compose-file> exec app php artisan images:optimize-existing --limit=50
```

Command ini aman diulang. Original image tetap dipertahankan, sementara varian WebP dibuat di `public/assets/optimized/...`.

## Catatan web utama

Web utama masih memakai compose legacy, jadi:

- named volume assets lama tetap dipakai
- `container_name` lama tetap dipakai
- deploy script utama tidak diubah dalam batch ini

Kalau nanti mau migrasi web utama ke bind mount assets, lakukan sebagai batch terpisah agar tidak mengganggu layanan yang sudah live.

## Checklist sebelum go-live client kedua

1. `.env` client kedua sudah benar
2. domain dan SSL sudah aktif
3. callback URL payment gateway sudah diarahkan ke domain client kedua
4. webhook secret provider sesuai env client kedua
5. asset branding client kedua sudah lengkap
6. dump database shared hosting sudah terimport
7. migration terbaru sudah jalan
8. test order sandbox/manual sudah lolos
