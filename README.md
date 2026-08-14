# Laravel Topup Platform

Platform digital game top-up dan reseller berbasis Laravel. Aplikasi ini menyediakan storefront publik, checkout dan invoice, panel admin, reseller H2H API, integrasi provider, payment gateway, callback/webhook, affiliate, dan notifikasi.

## Fitur utama

- Storefront publik untuk katalog game, produk, harga, checkout, invoice, dan pelacakan transaksi.
- Panel admin berbasis Filament untuk produk, kategori, order, user, provider, payment method, voucher, media, affiliate, callback, dan laporan.
- Reseller H2H API untuk cek saldo, kategori, varian, membuat order, dan cek status order.
- Sandbox API untuk pengujian alur reseller tanpa transaksi live.
- Provider routing dan fallback untuk Digiflazz, VIP Reseller, API Games, BangJeff, Topupedia, manual, dan joki.
- Integrasi payment gateway dan callback pembayaran.
- Tier pricing, saldo reseller, affiliate, komisi, points, voucher, review, artikel, SEO, dan PWA.
- Queue worker, scheduler, webhook worker, dan Laravel Reverb untuk kebutuhan realtime.
- Docker Compose untuk aplikasi, MySQL, Redis, phpMyAdmin, storage, log, dan asset publik.

## Stack

- PHP `^8.4`
- Laravel `^12.0`
- Filament `^4.11`
- Inertia.js dan React 19
- Vite 6
- Laravel Sanctum
- MySQL 8
- Redis 7.2 pada deployment Docker
- Composer dan Node.js/npm
- PHPUnit/Pest serta Jest dan Playwright

## Prerequisites

Untuk setup lokal:

- PHP 8.4 atau versi yang memenuhi constraint Composer
- Composer
- MySQL 8 atau database yang kompatibel dengan konfigurasi aplikasi
- Node.js dan npm
- Redis hanya diperlukan bila konfigurasi lokal memakai Redis

Untuk setup Docker:

- Docker Engine
- Docker Compose v2

## Setup lokal

Clone repository, lalu masuk ke folder project:

```bash
git clone <repository-url>
cd project
```

Install dependency PHP:

```bash
composer install
```

Buat file environment. Gunakan salah satu command berikut.

Linux/macOS/Git Bash:

```bash
cp .env.example .env
```

PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Edit `.env`, minimal isi konfigurasi berikut sebelum menjalankan migration:

```env
APP_NAME="Nama Toko"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_topup
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration:

```bash
php artisan migrate
```

Untuk database baru atau disposable, seed data bila memang diperlukan:

```bash
php artisan db:seed
```

> Jangan menjalankan `db:seed` pada database existing tanpa memahami isi seeder dan dampaknya terhadap data.

Install dan jalankan frontend dalam terminal terpisah:

```bash
npm install
npm run dev
```

Jalankan server Laravel dalam terminal lain:

```bash
php artisan serve
```

Aplikasi tersedia di `http://127.0.0.1:8000` selama kedua proses development berjalan.

## Environment configuration

`.env.example` adalah baseline konfigurasi. Sesuaikan variable berdasarkan kebutuhan deployment.

| Area | Variable utama | Keterangan |
| --- | --- | --- |
| Aplikasi | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`, `APP_URL_CALLBACK` | Identitas, URL, dan encryption key aplikasi |
| Host | `FILAMENT_ADMIN_DOMAIN`, `DOCS_DOMAIN`, `SESSION_DOMAIN` | Domain admin, dokumentasi, dan cookie |
| Database | `DB_*` | Koneksi MySQL |
| Cache/session/queue | `CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION` | Default lokal dapat memakai file/sync; Docker memakai Redis |
| Redis | `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` | Cache, session, queue, atau worker berbasis Redis |
| Asset | `UPLOADS_DISK`, `R2_*`, `PUBLIC_*_PATH` | Local asset, Cloudflare R2, dan asset branding client |
| Mail | `MAIL_*` | SMTP untuk reset password dan notifikasi |
| Realtime | `REVERB_*`, `PUSHER_*`, `VITE_PUSHER_*` | Laravel Reverb/Pusher bila realtime digunakan |
| Web push | `WEBPUSH_VAPID_*` | PWA push notification |
| Notifikasi | `WA_*`, `FONNTE_*`, `TELEGRAM_*` | WhatsApp dan Telegram |
| Provider | `DIGIFLAZZ_*`, `BANGJEFF_*`, `TOPUPEDIA_*`, `APIGAMES_*`, `VIP_*` | Credential dan secret provider |
| Payment | Setting payment gateway dan variable terkait | Konfigurasi gateway/payment callback |
| Security | `TRUSTED_PROXIES`, `NOCAPTCHA_*`, rate-limit variables | Proxy tepercaya, CAPTCHA, dan rate limit |

### Aturan keamanan

- Jangan commit `.env`, API key, password database, credential provider, payment credential, atau webhook secret.
- Gunakan database, Redis, domain, callback URL, asset path, dan credential yang terpisah untuk setiap client/deployment.
- Set `APP_DEBUG=false` di staging/production.
- Isi webhook secret provider sebelum mengaktifkan callback. Secret kosong dapat membuat callback ditolak.
- Isi `TRUSTED_PROXIES` hanya dengan reverse proxy/load balancer yang benar-benar dipercaya. Jangan memakai `*` pada production.
- Jangan menaruh secret pada variable `VITE_*`; variable tersebut dapat masuk ke bundle browser.

## Area aplikasi dan URL

URL dapat berubah berdasarkan `APP_URL`, domain tenant, `FILAMENT_ADMIN_DOMAIN`, dan `DOCS_DOMAIN`.

- Storefront utama: `/id`
- Login/register dan halaman publik legacy: prefix `/id`
- API publik catalog/content: `/api/home`, `/api/categories`, `/api/price-list`, `/api/articles`, dan endpoint terkait
- API order headless: `/api/v2/order/*`
- Reseller H2H API: `/api/v1/*`
- Reseller sandbox API: `/api/v1/sandbox/*`
- Dokumentasi API: `/id/docs` atau domain yang dikonfigurasi melalui `DOCS_DOMAIN`
- Panel admin: domain yang dikonfigurasi melalui `FILAMENT_ADMIN_DOMAIN`
- Provider webhook: `/api/webhooks/*`
- Bot webhook: `/api/webhooks/bot/*`

## Reseller H2H API

Endpoint utama berada di bawah `/api/v1` dan memakai API authentication, rate limit, serta reseller IP whitelist enforcement.

| Method | Endpoint | Fungsi |
| --- | --- | --- |
| `POST` | `/api/v1/balance` | Cek saldo reseller |
| `POST` | `/api/v1/category` | Ambil kategori/produk |
| `POST` | `/api/v1/variant` | Ambil varian produk |
| `POST` | `/api/v1/order` | Membuat order |
| `POST` | `/api/v1/status-order/{invoice}` | Cek status order |

Untuk pengujian sandbox tersedia endpoint sejenis di `/api/v1/sandbox`. Detail credential, integration code, outbound callback, dan pengujian Postman ada di:

- [Inbound whitelist Postman guide](docs/inbound-whitelist-postman-guide.md)
- [Outbound H2H MVP Postman guide](docs/outbound-h2h-mvp-postman-guide.md)
- [Reseller API regression guide](docs/reseller-api-regression-guide.md)

Webhook provider dan payment harus memakai signature/secret serta kebijakan inbound whitelist yang sesuai. Jangan membuka endpoint callback ke traffic production sebelum konfigurasi tersebut diverifikasi.

## WhatsApp account linking

WhatsApp deposits require a verified number linked to the account. Open **Pengaturan**, create a linking code in the **WhatsApp Gateway** section, then send `LINK <kode>` from that number. The code is single-use and expires according to the configured challenge lifetime. See [docs/whatsapp-account-linking.md](docs/whatsapp-account-linking.md) for endpoint, security, and recovery details.

## WhatsApp deposit rollout checklist

Sebelum mengaktifkan deposit WhatsApp di production:

1. Audit `users.no_wa` untuk nomor kosong, format legacy, dan duplicate canonical number.
2. Jangan menambahkan unique index sebelum duplicate number selesai ditangani secara manual.
3. Pastikan `FONNTE_DEVICE_TOKEN` terisi dan inbound whitelist Fonnte memakai mode `enforce`.
4. Pastikan cache production memakai Redis agar rate limit dan idempotency lock konsisten antar worker.
5. Jalankan migration dalam maintenance window setelah backup database.
6. Verifikasi linking, unlink, revoked challenge, duplicate webhook, QR media, VA/payment-code text, dan tenant isolation.
7. Monitor log berbasis correlation ID tanpa menyimpan challenge code, token, credential, atau raw sender number.
8. Siapkan rollback aplikasi dan backup database; jangan memakai `migrate:fresh` pada database existing.

## Testing dan build

### Backend

```bash
php artisan test
```

Atau jalankan PHPUnit/Pest secara langsung:

```bash
vendor/bin/phpunit
vendor/bin/pest
```

Test backend menggunakan konfigurasi `phpunit.xml`, termasuk SQLite in-memory, cache array, queue sync, dan session array.

### Frontend

Script Jest yang tersedia:

```bash
npm run test:ui
npm run test:watch
npm run test:coverage
```

Project saat ini tidak memiliki script `npm test`. Gunakan `npm run test:ui` untuk test frontend standar.

### End-to-end

```bash
npm run test:e2e
npm run test:e2e:app
npm run test:e2e:tracking
npm run test:e2e:auth
```

Mode tambahan:

```bash
npm run test:e2e:headed
npm run test:e2e:ui
npm run test:e2e:debug
npm run test:e2e:report
```

### Production asset build

```bash
npm run build
```

## Menjalankan dengan Docker

Salin dan konfigurasi `.env` terlebih dahulu, lalu start stack utama:

```bash
docker compose up -d
```

Stack utama berisi:

- `app`: image aplikasi Laravel dengan Nginx, PHP-FPM, Supervisor, worker, scheduler, webhook worker, dan Reverb.
- `db`: MySQL 8.
- `redis`: Redis 7.2 Alpine.
- `phpmyadmin`: akses administrasi database lokal/development.

Periksa log aplikasi:

```bash
docker compose logs -f app
```

Jalankan migration di dalam container:

```bash
docker compose exec app php artisan migrate --force
```

Matikan stack tanpa menghapus volume:

```bash
docker compose down
```

Port utama dikontrol oleh `NGINX_PORT`, `PMA_PORT`, dan konfigurasi Reverb. Service Docker memakai `DB_HOST=db` dan `REDIS_HOST=redis`, bukan `127.0.0.1`.

### Client kedua atau deployment terpisah

Gunakan compose file dan `.env` yang sesuai client. Repository ini menyediakan compose utama; deployment client kedua dapat memakai compose file terpisah yang disiapkan di server deployment.

```bash
docker compose -f <compose-file> up -d
docker compose -f <compose-file> logs -f app
docker compose -f <compose-file> exec app php artisan migrate --force
docker compose -f <compose-file> down
```

Setiap client harus memiliki `COMPOSE_PROJECT_NAME`, database, Redis, domain, callback URL, dan asset path yang berbeda. Baca [panduan multi-client deployment](docs/multi-client-deploy.md) sebelum mengimport database atau menjalankan client kedua. Jangan mengasumsikan `docker-compose.egymarket.yml` tersedia di checkout ini; gunakan compose file yang benar-benar disediakan oleh deployment target.

### Peringatan volume Docker

```bash
docker compose down -v
```

Command tersebut menghapus named volume yang dapat berisi database, Redis, storage, asset, dan log. Jalankan hanya setelah backup dan konfirmasi bahwa data memang boleh dihapus.

## Production deployment

`Dockerfile` membangun image production dengan PHP 8.4 FPM, Composer dependency tanpa package development, dependency Node, dan asset frontend. Container menjalankan Supervisor yang mengelola:

- Nginx
- PHP-FPM
- dua Laravel queue worker utama
- scheduler Laravel
- Laravel Reverb
- dua webhook worker pada queue `webhook`

Sebelum deployment:

1. Backup database dan asset client.
2. Verifikasi `.env`, domain, `APP_KEY`, database, Redis, provider credential, payment credential, webhook secret, dan callback URL.
3. Pastikan migration kompatibel dengan database target.
4. Deploy image/application.
5. Jalankan migration sesuai prosedur deployment.
6. Clear/rebuild cache dan publish asset yang diperlukan.
7. Verifikasi worker, scheduler, Reverb, storefront, admin, checkout, callback, dan log.
8. Simpan image sebelumnya untuk rollback.

Untuk update app tanpa restart DB/Redis, gunakan script deployment yang sudah tersedia:

```bash
sh docker/scripts/update-app.sh docker-compose.yml
```

Detail per-client, import database existing, asset bind mount, dan checklist go-live ada di [docs/multi-client-deploy.md](docs/multi-client-deploy.md).

## Operasional dan troubleshooting

Log yang perlu diperiksa:

```bash
docker compose logs -f app
docker compose exec app tail -f storage/logs/laravel.log
docker compose exec app tail -f storage/logs/worker.log
docker compose exec app tail -f storage/logs/scheduler.log
docker compose exec app tail -f storage/logs/webhook-worker.log
docker compose exec app tail -f storage/logs/reverb.log
```

Command diagnostik umum:

```bash
php artisan about
php artisan route:list
php artisan config:clear
php artisan cache:clear
```

Jika aplikasi memakai queue Redis, pastikan service Redis sehat dan worker aktif. Jika callback provider menerima `403`, periksa inbound whitelist. Jika callback menerima `401`, periksa signature dan webhook secret. Jika asset tidak muncul, periksa `PUBLIC_*_PATH`, volume/bind mount, permission, dan hasil `npm run build`.

## Command berisiko

Jalankan command berikut hanya setelah backup dan memahami dampaknya:

- `php artisan migrate:fresh` menghapus seluruh tabel lalu membuat ulang schema.
- `php artisan db:seed` dapat memasukkan atau mengubah data sesuai isi seeder.
- `php artisan migrate --force` melewati konfirmasi production.
- `docker compose down -v` menghapus named volume dan dapat menghilangkan database, Redis, storage, asset, serta log.

Untuk database client existing hasil import, jangan gunakan `migrate:fresh` atau full seed. Ikuti prosedur pada [docs/multi-client-deploy.md](docs/multi-client-deploy.md).

## Struktur direktori penting

```text
app/                    Domain logic, controllers, jobs, services, models
app/Filament/Admin/     Panel admin dan resources
config/                 Konfigurasi Laravel, provider, queue, cache, filesystem
 database/migrations/   Schema database
resources/js/           React/Inertia frontend
resources/views/        Blade views dan dokumentasi
routes/                 Web, API, callback, dan webhook routes
tests/                  Unit dan feature tests
docs/                   Panduan API, deployment, testing, dan operasional
docker/                 Nginx, PHP, Supervisor, dan deployment scripts
```

## Contribution checklist

Sebelum membuat commit atau pull request:

1. Jalankan test backend yang relevan.
2. Jalankan test frontend/E2E yang relevan.
3. Jalankan `npm run build` bila mengubah frontend.
4. Periksa migration, route, callback, dan perubahan environment.
5. Pastikan tidak ada secret atau data production dalam commit.
6. Update README atau dokumentasi terkait bila command, environment variable, compose file, atau deployment behavior berubah.

Branch `staging` adalah source of truth development terbaru project ini. Gunakan `master` sebagai branch default/deployment sesuai kebijakan repository.
