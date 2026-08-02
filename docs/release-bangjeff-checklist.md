# Bangjeff Release Checklist

Checklist ini dipakai untuk cut release theme Bangjeff (public + dashboard user family) dengan pola deploy staged.

## 1) Scope lock sebelum commit

- Masukkan hanya perubahan Bangjeff/public/dashboard/auth/settings split.
- Tunda perubahan reseller/H2H jika tidak termasuk batch ini.
- Pastikan route strict redirect aktif hanya saat `public_theme=bangjeff`.

## 2) Gate local (wajib hijau)

```bash
npm run build
```

```bash
php artisan test tests/Feature/Filament/SettingsSplitPageTest.php
php artisan test tests/Feature/PublicInertiaPagesTest.php --filter="bangjeff_theme_redirects_legacy_blade_pages_to_home_with_301|bangjeff_theme_keeps_signin_and_signup_accessible"
php artisan test tests/Feature/TrackingTemplateTest.php
php artisan test tests/Feature/InvoicePageControllerRealtimePropsTest.php
php artisan test tests/Feature/TransactionDataLayerTest.php
php artisan test tests/Feature/P04PasswordRecoveryTest.php
```

Opsional smoke E2E:

```bash
npm run test:e2e:smoke:local
npm run test:e2e:auth:local
```

## 3) Gate SEO

```bash
npm run audit:pagespeed
```

Target minimal:

- SEO mobile >= 80
- SEO desktop >= 80

## 4) Pre-deploy snapshot

```bash
php artisan affiliate:audit --json
```

Simpan output sebagai baseline sebelum deploy.

## 5) Deploy staged

1. Backup DB + snapshot row `setting_webs` (`id=1`).
2. Deploy code.
3. Jalankan:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

4. Restart worker queue (jika aktif).
5. Verifikasi endpoint:
   - `/id`
   - `/id/sign-in`
   - `/id/sign-up`
   - `/id/invoices`
   - `/id/dashboard`
   - `/id/affiliate`
   - `/id/price-list`
   - `/id/reviews`
   - `/id/forgot-password`

## 6) Validasi pasca deploy

- Cek strict redirect:
  - saat `public_theme=bangjeff`, route legacy non-exception harus `301 -> /id`
- Cek checkout sampai invoice; pastikan root Inertia memuat GTM/GA/Meta Pixel sesuai konfigurasi dan `invoice.gtmEvents` tidak mengandung PII.
- Untuk konfigurasi GTM, pastikan event purchase terdeduplikasi setelah refresh; GA direct tidak boleh termuat ketika GTM/custom GTM aktif.
- Cek artikel dan popup dengan rich HTML: markup aman tampil, script/event handler/unsafe URL tidak masuk ke prop Inertia.
- Cek lookup transaksi: akun hanya melihat transaksi sendiri, guest hanya melihat invoice pada session browser aktif.
- Cek login normal dan Google login.
- Cek menu dashboard user (dashboard, riwayat, deposit, affiliate, withdrawal).
- Set `DOCS_DOMAIN` ke hostname portal dokumentasi API sebelum deploy. Link footer, CTA sales reseller, dan redirect panel reseller hanya aktif ke URL HTTPS canonical dari nilai ini; jika kosong, link disembunyikan dan `/id/reseller/docs` merespons 404.
- Verifikasi `docs.index` hanya dapat diakses pada `DOCS_DOMAIN`. `/id/docs`, `/api-documentation`, dan `/id/cek-region` sudah dipensiunkan dan harus mengikuti kebijakan unknown-route host publik; jangan membuat redirect, exception Bangjeff, atau integrasi provider untuk `cek-region`.
- Smoke artikel Bangjeff `/id/artikel/{slug}` pada desktop dan mobile untuk layout `default` dan `modern`. Pastikan warna artikel valid diterapkan lokal, layout/warna invalid memakai fallback tema, focus share/related link terlihat, dan HTML rich content tidak menampilkan script, event handler, atau URL tidak aman.
- Password recovery preflight: audit duplicate normalized `users.username` dan `users.email`; akun ambigu harus ditangani support/deduplicated sebelum recovery dapat dipakai. Pastikan migration `password_resets` sudah ada, SMTP dan WhatsApp fallback teruji, reset URL memakai HTTPS canonical, dan reset sukses mencabut seluruh Sanctum token.

## 7) Rollback trigger

Rollback segera jika ada:

- gagal login massal
- invoice/create order gagal
- redirect loop pada `/id/*`
- callback status tidak sinkron
