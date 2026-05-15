# Bangjeff Final Cutover (Step 5)

Tanggal: 2026-05-15  
Branch: `release/bangjeff-prod`  
HEAD: `e4af13e`

## 1. Scope Freeze (Bangjeff Release)

Release ini dibatasi ke commit berikut:

1. `befd529` feat(public): add bangjeff inertia storefront with theme-aware routing
2. `3431ec3` feat(auth-affiliate): add google login and affiliate access hardening
3. `f51f3af` feat(admin): split settings into sub-pages with scoped onboarding guides
4. `ebe6843` feat(blade): align dashboard and account pages with bangjeff layout
5. `e4af13e` test(release): add bangjeff smoke gates and affiliate audit coverage

## 2. Final Gate Result (GO/NO-GO)

Status: **GO**

Gate yang sudah lulus:

- `php artisan migrate --force`
- `npm run build`
- `php artisan test tests/Feature/Filament/SettingsSplitPageTest.php`
- `php artisan test tests/Feature/PublicInertiaPagesTest.php --filter="bangjeff_theme_redirects_legacy_blade_pages_to_home_with_301|bangjeff_theme_keeps_signin_and_signup_accessible"`
- `npm run test:e2e:smoke:local`
- `npm run test:e2e:auth:local`
- `php artisan affiliate:audit --json` -> `status: ok`
- `npm run audit:pagespeed` (PSI)
  - Mobile: SEO `92`, Performance `56`
  - Desktop: SEO `92`, Performance `74`

## 3. Pre-Deploy Snapshot

Sudah dibuat snapshot:

- `storage/app/release-setting_webs-id1.json`

Tambahan yang wajib di production:

1. Backup DB penuh.
2. Snapshot row `setting_webs` id=1.

## 4. Production Deploy Sequence

Jalankan dari root project:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

## 5. Post-Deploy Verification

Verifikasi endpoint berikut:

- `/id`
- `/id/sign-in`
- `/id/sign-up`
- `/id/invoices`
- `/id/dashboard`
- `/id/affiliate`

Checklist functional:

- Login biasa + Google login.
- Checkout -> invoice flow.
- Deposit flow (non-affiliate).
- Withdrawal flow (affiliate).
- Onboarding guide admin per-page scope tetap terisolasi.

## 6. Rollback Trigger

Rollback segera jika:

- Login gagal massal.
- Redirect loop pada `/id/*`.
- Create order/invoice gagal.
- Callback status payment tidak sinkron.

## 7. Fast Rollback (Theme-Level)

Jika incident hanya di theme Bangjeff dan ingin cepat balik:

1. Ubah `setting_webs.public_theme` ke default/legacy.
2. Jalankan:

```bash
php artisan optimize:clear
php artisan optimize
```

Ini menjaga backend data tetap aman sambil memulihkan storefront lama.
