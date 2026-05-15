# Playwright E2E Quick Start

## 1) Install once

```bash
npm install -D @playwright/test
npx playwright install chromium
```

## 2) Run smoke test with existing local server

Pastikan app kamu sudah jalan dulu (contoh Laragon + domain lokal):

- `http://istanatopup.test:8000`
- `http://admin.istanatopup.test:8000`

Lalu jalankan:

```bash
npm run test:e2e:smoke
```

`test:e2e:smoke` sekarang menjalankan:
- `tests/e2e/smoke.spec.js` (public storefront)
- `tests/e2e/admin-onboarding.spec.js` (admin onboarding scope flow)

Catatan: smoke suite dijalankan serial (`--workers=1`) supaya flow login admin tetap stabil.

## 2b) Rekomendasi: run smoke lokal tanpa set env manual

```bash
npm run test:e2e:smoke:local
```

Script ini otomatis:
- menyalakan managed web server Playwright,
- set host test ke `127.0.0.1`,
- pakai port default `8001` (biar aman kalau `8000` dipakai Laragon),
- set base URL public + admin ke port yang sama.

Opsional override port:

```bash
E2E_SERVER_PORT=8010 npm run test:e2e:smoke:local
```

Atau override base URL:

```bash
E2E_BASE_URL=http://127.0.0.1:8000 npm run test:e2e:smoke
```

## 3) Optional: biarkan Playwright start server otomatis

Gunakan mode managed server:

```bash
E2E_MANAGED_SERVER=1 E2E_BASE_URL=http://127.0.0.1:8000 npm run test:e2e:smoke
```

Catatan: mode ini butuh koneksi DB aktif, karena `php artisan serve` akan bootstrap aplikasi penuh.

## 4) Useful commands

```bash
npm run test:e2e
npm run test:e2e:headed
npm run test:e2e:ui
npm run test:e2e:debug
npm run test:e2e:report
```

## 5) Authenticated flow (dashboard + affiliate)

Set credential env dulu:

```bash
E2E_USERNAME=demo_user E2E_PASSWORD=demo_pass npm run test:e2e:auth
```

Test ini akan:
- login lewat `/id/sign-in`
- verifikasi halaman `/id/dashboard`
- verifikasi halaman `/id/affiliate`

Untuk mode lokal tanpa set env manual:

```bash
E2E_USERNAME=demo_user E2E_PASSWORD=demo_pass npm run test:e2e:auth:local
```

## 6) Admin onboarding smoke env (opsional override)

Secara default, test onboarding admin pakai:

- `E2E_ADMIN_BASE_URL=http://admin.istanatopup.test:8000`
- `E2E_ADMIN_EMAIL=owner@olfastore-demo.test`
- `E2E_ADMIN_PASSWORD=DemoTopup123!`

Kalau mau override:

```bash
E2E_ADMIN_BASE_URL=http://admin.domainmu.test:8000 E2E_ADMIN_EMAIL=admin@example.com E2E_ADMIN_PASSWORD=secret npm run test:e2e:smoke
```
