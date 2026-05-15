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

## 6) Validasi pasca deploy

- Cek strict redirect:
  - saat `public_theme=bangjeff`, route legacy non-exception harus `301 -> /id`
- Cek checkout sampai invoice.
- Cek login normal dan Google login.
- Cek menu dashboard user (dashboard, riwayat, deposit, affiliate, withdrawal).

## 7) Rollback trigger

Rollback segera jika ada:

- gagal login massal
- invoice/create order gagal
- redirect loop pada `/id/*`
- callback status tidak sinkron

